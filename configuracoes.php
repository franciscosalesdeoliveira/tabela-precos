<?php
$titulo = "Configurações Tabela";
include_once 'connection.php';
include_once 'header.php';


// Recuperar valores anteriores (se existirem) para preencher os campos
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
$tempo = isset($_GET['tempo']) ? intval($_GET['tempo']) : 10;
$tema = isset($_GET['tema']) ? $_GET['tema'] : 'padrao';
$grupo_selecionado = isset($_GET['grupo']) ? $_GET['grupo'] : 'todos';
$atualizacao_auto = isset($_GET['atualizacao_auto']) ? intval($_GET['atualizacao_auto']) : 10; // Valor padrão: 10 minutos

// Novos parâmetros para propagandas
// Importante: Mudamos para verificar se o parâmetro existe em $_GET, não apenas o valor
// $propagandas_ativas = 1; // Sempre ativado por padrão
$propagandas_ativas = isset($_GET['propagandas_ativas']) && $_GET['propagandas_ativas'] == '1' ? 1 : 0;
$tempo_propagandas = isset($_GET['tempo_propagandas']) ? intval($_GET['tempo_propagandas']) : 5; // Tempo em segundos

// Lista de temas disponíveis
$temas = [
    'padrao' => 'Padrão <br> (Azul)',
    'supermercado' => 'Supermercado <br> (Verde)',
    'padaria' => 'Padaria <br> (Amarelo)'
];

// Opções de tempo de atualização automática (em minutos)
$opcoes_atualizacao = [
    1 => '1 minuto',
    5 => '5 minutos',
    10 => '10 minutos',
    15 => '15 minutos',
    30 => '30 minutos',
    60 => '1 hora',
    0 => 'Desativar'
];

// Buscar grupos disponíveis no banco de dados
$grupos = ['todos' => 'Todos os Grupos'];
try {
    $stmt = $pdo->query("SELECT id, nome FROM grupos ORDER BY nome");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grupos[$row['id']] = $row['nome'];
    }
} catch (PDOException $e) {
    // Se ocorrer erro, mantém apenas a opção "Todos"
}
?>

<style>
    :root {
        --primary: #4361ee;
        --primary-hover: #3a56d4;
        --secondary: #3f37c9;
        --accent: #4895ef;
        --light: #f8f9fa;
        --dark: #212529;
        --success: #4cc9f0;
        --gray-100: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-300: #dee2e6;
        --gray-800: #343a40;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        border-left: 4px solid #dc3545;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        /* Alterado para column para empilhar os elementos */
        padding: 20px 20px 0 20px;
        /* Removido padding do fundo */
        overflow-x: hidden;
        /* Evita scroll horizontal */
        margin: 0;
        box-sizing: border-box;
    }

    .main-content {
        flex: 1;
        /* Adicionado para ocupar o espaço disponível */
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin-bottom: 10px;
    }

    /* Estilo para garantir que o footer ocupe toda a largura */
    footer {
        width: 100vw !important;
        /* Usa a largura total da viewport */
        max-width: 100vw !important;
        margin-left: calc(-20px - 1rem) !important;
        /* Compensa o padding do body e margens extras */
        margin-right: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        box-sizing: border-box !important;
        position: relative !important;
        left: 0 !important;
    }

    footer .container {
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 20px !important;
        padding-right: 20px !important;
    }

    .config-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: 900px;
        display: flex;
        flex-direction: column;
        animation: fadeIn 0.6s ease;
        margin-bottom: 30px;
        /* Adicionado espaço abaixo do container */
    }

    .config-header {
        background: linear-gradient(to right, var(--primary), var(--secondary));
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
    }

    .config-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .config-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 15px;
    }

    .config-wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    .config-wave svg {
        display: block;
        width: calc(100% + 1.3px);
        height: 46px;
        transform: scaleY(-1);
    }

    .config-wave .shape-fill {
        fill: #FFFFFF;
    }

    .config-content {
        padding: 40px 30px;
    }

    .config-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .config-form-container {
        display: flex;
        flex-direction: column;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: var(--dark);
        text-align: center;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--gray-800);
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        outline: none;
    }

    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .btn {
        display: inline-block;
        padding: 12px 24px;
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        text-decoration: none;
    }

    .btn:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
    }

    .btn-block {
        width: 100%;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        margin-top: 30px;
        transition: all 0.3s ease;
    }

    .back-link i {
        margin-right: 8px;
    }

    .back-link:hover {
        color: var(--primary-hover);
        transform: translateX(-5px);
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        border-left: 4px solid #28a745;
    }


    /* Animações */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .contact-title {
            font-size: 1.8rem;
        }

        .contact-content {
            padding: 30px 20px;
        }

        .contact-options {
            grid-template-columns: 1fr;
        }
    }
</style>

<body>

    <div class="main-content">
        <div class="config-container mt-5">
            <div class="config-header">
                <h1 class="config-title">Configurações da Tabela de Preços</h1>
                <p class="config-subtitle">Ajuste as configurações da tabela de preços</p>
                <div class="config-wave">
                    <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                        <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                    </svg>
                </div>
            </div>

            <div class="row justify-content-center config-content">
                <div class="col-md-8 config-form-container">
                    <!-- <div class="card shadow"> -->
                    <div class="card-body">
                        <!-- Formulário Unificado -->
                        <form id="formConfiguracoes" action="tabela_precos.php" method="GET">
                            <!-- Seleção de Grupo -->
                            <div class="mb-3">
                                <label for="grupo" class="form-label fw-bold">Grupo a ser exibido:</label>
                                <select class="form-select" id="grupo" name="grupo">
                                    <?php foreach ($grupos as $id => $nome): ?>
                                        <option value="<?= $id ?>" <?= ($grupo_selecionado == $id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nome) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Escolha um grupo específico ou todos os grupos.</div>
                            </div>

                            <!-- Limite de Itens -->
                            <div class="mb-3">
                                <label for="limite" class="form-label fw-bold">Quantidade de itens por grupo:</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="limite" name="limite"
                                        min="1" value="<?php echo $limite; ?>" placeholder="Ex: 10" required>
                                    <span class="input-group-text">itens</span>
                                </div>
                                <div class="form-text">Defina quantos itens serão exibidos em cada grupo.</div>
                            </div>

                            <!-- Tempo por Slide -->
                            <div class="mb-3">
                                <label for="tempo" class="form-label fw-bold">Tempo por slide (segundos):</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tempo" name="tempo"
                                        min="1" value="<?php echo $tempo; ?>" placeholder="Ex: 60" required>
                                    <span class="input-group-text">segundos</span>
                                </div>
                                <div class="form-text">Defina o tempo em segundos que cada slide ficará visível.</div>
                            </div>

                            <!-- Controle de Propagandas-->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Exibição de Propagandas:</label>
                                <!-- Importante: Alteramos para usar um campo oculto que sempre será enviado -->
                                <input type="hidden" name="propagandas_ativas" value="0">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="propagandas_ativas" name="propagandas_ativas" value="1"
                                        <?= $propagandas_ativas ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="propagandas_ativas">Ativar exibição de propagandas</label>
                                </div>
                                <div class="mt-2">
                                    <label for="tempo_propagandas" class="form-label">Tempo de exibição (segundos):</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="tempo_propagandas" name="tempo_propagandas"
                                            min="1" value="<?= $tempo_propagandas; ?>" placeholder="Ex: 5"
                                            <?= $propagandas_ativas ? '' : 'disabled' ?>>
                                        <span class="input-group-text">segundos</span>
                                    </div>
                                </div>

                                <div class="form-text">Defina se as propagandas serão exibidas e por quanto tempo.</div>
                            </div>

                            <!-- Tempo de Atualização Automática -->
                            <div class="mb-3">
                                <label for="atualizacao_auto" class="form-label fw-bold">Atualização automática:</label>
                                <select class="form-select" id="atualizacao_auto" name="atualizacao_auto">
                                    <?php foreach ($opcoes_atualizacao as $valor => $texto): ?>
                                        <option value="<?= $valor ?>" <?= ($atualizacao_auto == $valor) ? 'selected' : '' ?>>
                                            <?= $texto ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Defina com que frequência a tabela será atualizada automaticamente.</div>
                            </div>

                            <!-- Seleção de Tema -->
                            <div class="mb-4">
                                <label for="tema" class="form-label fw-bold">Tema visual:</label>
                                <select class="form-select" id="tema" name="tema">
                                    <?php foreach ($temas as $valor => $nome): ?>
                                        <option value="<?= $valor ?>" <?= ($tema == $valor) ? 'selected' : '' ?>>
                                            <?= $nome ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Escolha o estilo visual para a tabela de preços.</div>
                            </div>

                            <!-- Botão de Visualização -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-eye"></i> Visualizar Tabela
                                </button>
                            </div>
                        </form>

                        <div class="mt-4">
                            <h5 class="border-bottom pb-2">Pré-visualização dos temas</h5>
                            <div class="row mt-3">
                                <?php foreach ($temas as $valor => $nome):
                                    // Define as cores do tema para a prévia
                                    $corFundo = $valor == 'padrao' ? 'bg-primary' : ($valor == 'supermercado' ? 'bg-success' : 'bg-warning');
                                    $corTexto = $valor == 'padaria' ? 'text-dark' : 'text-white';
                                ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="card border h-100"> <!-- Adicionando h-100 para igualar altura -->
                                            <div class="card-header h-100 <?= $corFundo ?> <?= $corTexto ?> text-center">
                                                <?= $nome ?>
                                            </div>
                                            <div class="card-body p-2 text-center" style="font-size: 0.8rem;">
                                                <span class="badge <?= $corFundo ?> <?= $corTexto ?> d-block mb-1">Amostra</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Botões adicionais -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i> Página Inicial
                            </a>
                            <a href="propagandas.php" class="btn btn-outline-success">
                                <i class="bi bi-image"></i> Gerenciar Propagandas
                            </a>
                        </div>
                    </div>
                    <!-- </div> -->
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validação do formulário
            document.getElementById('formConfiguracoes').addEventListener('submit', function(event) {
                const limite = document.getElementById('limite').value;
                const tempo = document.getElementById('tempo').value;
                const propagandasAtivas = document.getElementById('propagandas_ativas').checked;
                const tempoPropagandas = document.getElementById('tempo_propagandas').value;

                if (!limite || parseInt(limite) <= 0) {
                    event.preventDefault();
                    alert('Por favor, insira um número válido de itens maior que zero.');
                    document.getElementById('limite').focus();
                    return false;
                }

                if (!tempo || parseInt(tempo) <= 0) {
                    event.preventDefault();
                    alert('Por favor, insira um tempo válido em segundos maior que zero.');
                    document.getElementById('tempo').focus();
                    return false;
                }

                if (propagandasAtivas && (!tempoPropagandas || parseInt(tempoPropagandas) <= 0)) {
                    event.preventDefault();
                    alert('Por favor, insira um tempo válido para as propagandas em segundos maior que zero.');
                    document.getElementById('tempo_propagandas').focus();
                    return false;
                }

                // Se tudo estiver correto, o formulário será enviado normalmente
                return true;
            });

            // Habilitar/desabilitar campo de tempo de propagandas
            document.getElementById('propagandas_ativas').addEventListener('change', function() {
                document.getElementById('tempo_propagandas').disabled = !this.checked;
            });

            // Visualização rápida do tema selecionado
            document.getElementById('tema').addEventListener('change', function() {
                const temaAtual = this.value;
                const exemplos = document.querySelectorAll('.card-header');

                exemplos.forEach(function(exemplo) {
                    exemplo.classList.remove('bg-primary', 'bg-success', 'bg-warning', 'text-white', 'text-dark');

                    if (temaAtual === 'padrao') {
                        exemplo.classList.add('bg-primary', 'text-white');
                    } else if (temaAtual === 'supermercado') {
                        exemplo.classList.add('bg-success', 'text-white');
                    } else if (temaAtual === 'padaria') {
                        exemplo.classList.add('bg-warning', 'text-dark');
                    } else if (temaAtual === 'informatica') {
                        exemplo.classList.add('bg-secondary', 'text-white');
                    }
                });
            });
        });
    </script>
</body>
<!-- Container para o footer com largura total -->
<div class="footer-wrapper" style="width: 100vw;">
    <?php
    // Incluindo o footer após o conteúdo principal
    include_once 'footer.php';
    ?>
</div>