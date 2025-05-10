<?php
include_once 'connection.php';

// Inicia a sessão para manter os dados entre requisições
//session_start();

// Verifica se é uma requisição AJAX para atualização de progresso
if (isset($_GET['check_progress'])) {
    // Retorna o progresso atual se existir na sessão
    $response = [
        'status' => isset($_SESSION['import_status']) ? $_SESSION['import_status'] : 'waiting',
        'progress' => isset($_SESSION['import_progress']) ? $_SESSION['import_progress'] : 0,
        'total_linhas' => isset($_SESSION['total_linhas']) ? $_SESSION['total_linhas'] : 0,
        'linhas_processadas' => isset($_SESSION['linhas_processadas']) ? $_SESSION['linhas_processadas'] : 0,
        'linhas_importadas' => isset($_SESSION['linhas_importadas']) ? $_SESSION['linhas_importadas'] : 0,
        'linhas_falha' => isset($_SESSION['linhas_falha']) ? $_SESSION['linhas_falha'] : 0
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Verifica se é uma solicitação de conclusão (usuário clicou em "Concluir")
if (isset($_GET['concluir'])) {
    // Limpa os dados da sessão
    unset($_SESSION['import_status']);
    unset($_SESSION['import_progress']);
    unset($_SESSION['total_linhas']);
    unset($_SESSION['linhas_processadas']);
    unset($_SESSION['linhas_importadas']);
    unset($_SESSION['linhas_falha']);
    unset($_SESSION['produtos_nao_importados']);
    unset($_SESSION['info_grupo']);
    unset($_SESSION['tem_descricao']);

    // Redireciona para a página de importação
    header('Location: excel.php');
    exit;
}

// Flag para verificar se é para mostrar apenas o resultado
$mostrar_resultado = false;

// Se houver um POST, processe o arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    // Inicializa variáveis na sessão
    $_SESSION['import_status'] = 'processing';
    $_SESSION['import_progress'] = 0;
    $_SESSION['linhas_importadas'] = 0;
    $_SESSION['linhas_falha'] = 0;
    $_SESSION['produtos_nao_importados'] = "";

    $arquivo = $_FILES['arquivo'];
    // Captura o grupo selecionado pelo usuário (se houver)
    $grupo_selecionado = isset($_POST['grupo_selecionado']) && !empty($_POST['grupo_selecionado']) ?
        (int)$_POST['grupo_selecionado'] : null;

    if ($arquivo['type'] == "text/csv") {
        $dados_arquivo = fopen($arquivo['tmp_name'], "r");

        // Conta o número total de linhas para a barra de progresso
        $total_linhas = 0;
        while (fgetcsv($dados_arquivo, 1000, ";")) {
            $total_linhas++;
        }
        $_SESSION['total_linhas'] = $total_linhas - 1; // Desconta a linha de cabeçalho

        // Reseta o ponteiro do arquivo
        rewind($dados_arquivo);

        // Pular a primeira linha (cabeçalho)
        $primeira_linha = true;
        $tem_descricao = false;
        $indice_grupo_id = 2;
        $indice_preco = 3;
        $_SESSION['linhas_processadas'] = 0;

        while ($linha = fgetcsv($dados_arquivo, 1000, ";")) {
            if ($primeira_linha) {
                // Verifica a estrutura do cabeçalho para determinar se há coluna de descrição
                $tem_descricao = false;
                for ($i = 0; $i < count($linha); $i++) {
                    $cabecalho = mb_strtolower(trim($linha[$i]));
                    if ($cabecalho === 'descricao' || $cabecalho === 'descrição' || $cabecalho === 'descr') {
                        $tem_descricao = true;
                        $indice_descricao = $i;
                        // Ajusta os índices de grupo_id e preço
                        $indice_grupo_id = $i + 1;
                        $indice_preco = $i + 2;
                        break;
                    }
                }
                $_SESSION['tem_descricao'] = $tem_descricao;
                $primeira_linha = false;
                continue; // Pula a primeira linha (cabeçalho)
            }

            // Incrementa o contador de linhas processadas
            $_SESSION['linhas_processadas']++;

            // Atualiza o progresso
            $_SESSION['import_progress'] = round(($_SESSION['linhas_processadas'] / $_SESSION['total_linhas']) * 100);

            // Converte todos os campos para UTF-8 se necessário
            array_walk_recursive($linha, 'converterParaUTF8');

            // Extrai os campos básicos que sempre existem
            $codigo = $linha[0] ?? null;
            $nome = $linha[1] ?? null;

            // Determina os valores de descrição, grupo_id e preço com base na estrutura detectada
            if ($tem_descricao) {
                // Se tiver coluna de descrição, usa os índices ajustados
                $descricao = isset($linha[$indice_descricao]) && !empty($linha[$indice_descricao]) ?
                    $linha[$indice_descricao] : null;
                $grupo_id_csv = isset($linha[$indice_grupo_id]) && !empty($linha[$indice_grupo_id]) ?
                    (int)$linha[$indice_grupo_id] : null;
                $preco = isset($linha[$indice_preco]) ? str_replace(',', '.', $linha[$indice_preco]) : null;
            } else {
                // Se não tiver coluna de descrição (como no seu CSV original)
                $descricao = null;
                $grupo_id_csv = isset($linha[2]) && !empty($linha[2]) ? (int)$linha[2] : null;
                $preco = isset($linha[3]) ? str_replace(',', '.', $linha[3]) : null;
            }

            // Prioriza o grupo selecionado pelo usuário, se existir
            $grupo_id = $grupo_selecionado !== null ? $grupo_selecionado : $grupo_id_csv;

            // Valida o preço
            if ($preco === null || !is_numeric($preco)) {
                $preco = 0.00; // Define um valor padrão para preco caso esteja ausente ou inválido
            }

            // Verifica se o campo 'ativo' existe
            $checkColumnQuery = "SELECT column_name 
                               FROM information_schema.columns 
                               WHERE table_name='produtos' AND column_name='ativo'";
            $checkStmt = $pdo->query($checkColumnQuery);
            $columnExists = $checkStmt->fetchColumn();

            // Prepara a query considerando se o campo 'ativo' existe
            if ($columnExists) {
                $query_produto = "INSERT INTO produtos (codigo, nome, descricao, grupo_id, preco, ativo) 
                                VALUES (:codigo, :nome, :descricao, :grupo_id, :preco, TRUE)";
            } else {
                $query_produto = "INSERT INTO produtos (codigo, nome, descricao, grupo_id, preco) 
                                VALUES (:codigo, :nome, :descricao, :grupo_id, :preco)";
            }

            $stmt = $pdo->prepare($query_produto);
            $stmt->bindValue(':codigo', $codigo);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':grupo_id', $grupo_id);
            $stmt->bindValue(':preco', (float)$preco); // Garante que seja tratado como float

            try {
                $stmt->execute();

                // Verifica se a inserção foi bem-sucedida
                if ($stmt->rowCount() > 0) {
                    $_SESSION['linhas_importadas']++;
                } else {
                    $_SESSION['linhas_falha']++;
                    $_SESSION['produtos_nao_importados'] .= "ID: " . $codigo . " - Nome: " . $nome . "<br>";
                }
            } catch (PDOException $e) {
                $_SESSION['linhas_falha']++;
                $_SESSION['produtos_nao_importados'] .= "Erro ao importar - ID: " . $codigo . " - Nome: " . $nome . " - Erro: " . $e->getMessage() . "<br>";
            }

            // Simular um pequeno atraso para processamento (remover em produção)
            // usleep(50000); // 50ms de atraso para demonstração
        }

        // Informações sobre o grupo utilizado
        if ($grupo_selecionado !== null) {
            // Busca o nome do grupo selecionado
            $query_grupo = "SELECT nome FROM grupos WHERE id = :id";
            $stmt_grupo = $pdo->prepare($query_grupo);
            $stmt_grupo->bindValue(':id', $grupo_selecionado);
            $stmt_grupo->execute();
            $grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);

            if ($grupo) {
                $_SESSION['info_grupo'] = "Todos os produtos foram importados para o grupo: " . $grupo['nome'] . " (ID: $grupo_selecionado)";
            } else {
                $_SESSION['info_grupo'] = "Grupo selecionado (ID: $grupo_selecionado) utilizado para todos os produtos";
            }
        } else {
            $_SESSION['info_grupo'] = "Utilizando os grupos definidos no arquivo CSV";
        }

        // Marca como concluído
        $_SESSION['import_status'] = 'completed';
        $_SESSION['import_progress'] = 100;

        // Define a flag para mostrar o resultado
        $mostrar_resultado = true;
    } else {
        $_SESSION['import_status'] = 'error';
        $_SESSION['erro_mensagem'] = "Arquivo não é um CSV";
    }
} elseif (isset($_SESSION['import_status']) && $_SESSION['import_status'] === 'completed') {
    // Se já concluiu o processamento e o usuário recarregou a página
    $mostrar_resultado = true;
}

// Função para converter o arquivo CSV para UTF-8
function converterParaUTF8(&$dados_arquivo)
{
    $dados_arquivo = mb_convert_encoding($dados_arquivo, 'UTF-8', 'ISO-8859-1');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processando Importação</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --text-color: #333;
            --light-gray: #f5f7fa;
            --border-color: #ddd;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            margin-bottom: 40px;
        }

        header .container {
            display: flex;
            align-items: center;
            padding-top: 0;
            padding-bottom: 0;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-left: 10px;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }

        .step-container {
            margin-bottom: 25px;
        }

        .progress-container {
            margin: 30px 0;
        }

        .progress-bar {
            height: 16px;
            background-color: #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 8px;
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-text {
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }

        .status-icon {
            font-size: 64px;
            text-align: center;
            margin: 20px 0;
        }

        .success-icon {
            color: var(--success-color);
        }

        .error-icon {
            color: var(--error-color);
        }

        .warning-icon {
            color: var(--warning-color);
        }

        .status-message {
            text-align: center;
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 30px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin: 5px 0;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .details-container {
            margin-top: 30px;
        }

        .details-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .details-content {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-align: center;
            text-decoration: none;
            margin-top: 30px;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        .pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .hidden {
            display: none;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .card {
                padding: 20px;
            }

            h1 {
                font-size: 20px;
            }

            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <i class="fas fa-file-csv" style="font-size: 24px; color: var(--primary-color);"></i>
            <h1>Processando Importação</h1>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <!-- Área de processamento (mostrada durante a importação) -->
            <div id="processing-area" class="<?php echo $mostrar_resultado ? 'hidden' : ''; ?>">
                <div class="step-container">
                    <h2>Importando dados do arquivo CSV</h2>
                    <p>Por favor, aguarde enquanto processamos seu arquivo. Não feche ou atualize esta página.</p>
                </div>

                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progressBar" style="width: 0%;"></div>
                    </div>
                    <div class="progress-text">
                        <span id="progressPercentage">0%</span>
                        <span id="progressCount">0 de 0 registros</span>
                    </div>
                </div>

                <div class="step-container">
                    <div id="currentActionText" class="pulse">Iniciando importação...</div>
                </div>
            </div>

            <!-- Área de resultado (mostrada após a conclusão) -->
            <div id="result-area" class="<?php echo $mostrar_resultado ? '' : 'hidden'; ?>">
                <?php if (isset($_SESSION['import_status']) && $_SESSION['import_status'] === 'completed'): ?>
                    <div class="status-icon success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="status-message">
                        Importação concluída com sucesso!
                    </div>

                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $_SESSION['total_linhas']; ?></div>
                            <div class="stat-label">Total de Registros</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $_SESSION['linhas_importadas']; ?></div>
                            <div class="stat-label">Importados com Sucesso</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $_SESSION['linhas_falha']; ?></div>
                            <div class="stat-label">Falhas</div>
                        </div>
                    </div>

                    <!-- Detalhes adicionais -->
                    <div class="details-container">
                        <div class="details-title">Detalhes da Importação</div>
                        <div class="details-content">
                            <p><strong>Grupo:</strong> <?php echo $_SESSION['info_grupo']; ?></p>
                            <p><strong>Estrutura:</strong> CSV <?php echo $_SESSION['tem_descricao'] ? 'COM' : 'SEM'; ?> campo de descrição</p>
                            <?php if ($_SESSION['linhas_falha'] > 0): ?>
                                <p><strong>Produtos não importados:</strong></p>
                                <div style="margin-top: 10px;">
                                    <?php echo $_SESSION['produtos_nao_importados']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="?concluir=1" class="btn">
                        <i class="fas fa-check"></i>
                        Concluir Importação
                    </a>
                <?php elseif (isset($_SESSION['import_status']) && $_SESSION['import_status'] === 'error'): ?>
                    <div class="status-icon error-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="status-message">
                        Erro na importação
                    </div>
                    <p><?php echo $_SESSION['erro_mensagem']; ?></p>

                    <a href="excel.php" class="btn">
                        <i class="fas fa-arrow-left"></i>
                        Voltar para Importação
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Script para atualizar o progresso
        const processingArea = document.getElementById('processing-area');
        const resultArea = document.getElementById('result-area');
        const progressBar = document.getElementById('progressBar');
        const progressPercentage = document.getElementById('progressPercentage');
        const progressCount = document.getElementById('progressCount');
        const currentActionText = document.getElementById('currentActionText');

        // Função para verificar o progresso
        function checkProgress() {
            fetch('processa_csv.php?check_progress=1')
                .then(response => response.json())
                .then(data => {
                    // Atualiza a barra de progresso
                    progressBar.style.width = data.progress + '%';
                    progressPercentage.textContent = data.progress + '%';
                    progressCount.textContent = data.linhas_processadas + ' de ' + data.total_linhas + ' registros';

                    // Atualiza o texto de ação
                    if (data.status === 'waiting') {
                        currentActionText.textContent = 'Aguardando início...';
                    } else if (data.status === 'processing') {
                        currentActionText.textContent = 'Processando registros...';
                    } else if (data.status === 'completed') {
                        currentActionText.textContent = 'Finalizado!';

                        // Mostra a área de resultado depois de um pequeno atraso
                        setTimeout(() => {
                            processingArea.classList.add('hidden');
                            resultArea.classList.remove('hidden');
                        }, 1000);

                        // Para de verificar o progresso
                        clearInterval(progressInterval);
                    } else if (data.status === 'error') {
                        currentActionText.textContent = 'Erro na importação!';

                        // Mostra a área de resultado com erro
                        setTimeout(() => {
                            processingArea.classList.add('hidden');
                            resultArea.classList.remove('hidden');
                        }, 1000);

                        // Para de verificar o progresso
                        clearInterval(progressInterval);
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar progresso:', error);
                });
        }

        // Verificar imediatamente e depois a cada 500ms
        checkProgress();
        const progressInterval = setInterval(checkProgress, 500);

        // Se o usuário tentar fechar ou recarregar a página durante o processamento
        window.addEventListener('beforeunload', function(e) {
            // Se estiver processando, mostra uma mensagem de confirmação
            if (processingArea.classList.contains('hidden') === false &&
                resultArea.classList.contains('hidden') === true) {
                e.preventDefault();
                e.returnValue = 'A importação está em andamento. Tem certeza que deseja sair?';
                return 'A importação está em andamento. Tem certeza que deseja sair?';
            }
        });
    </script>
</body>

</html>