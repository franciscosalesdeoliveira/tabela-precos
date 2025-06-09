<?php
// Proteção de autenticação - deve ser a primeira coisa no arquivo
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_uuid'])) {
    // Se não estiver logado, redirecionar para o login
    header("Location: login.php");
    exit;
}

// Verificar se a sessão não expirou (opcional - definir tempo limite)
$tempo_limite_sessao = 8 * 60 * 60; // 8 horas em segundos
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $tempo_limite_sessao)) {
    // Sessão expirada, limpar e redirecionar
    session_unset();
    session_destroy();
    header("Location: login.php?msg=sessao_expirada");
    exit;
}

// Atualizar o tempo da sessão (renovar automaticamente)
$_SESSION['login_time'] = time();

$titulo = "Tabela de Preços";
require_once 'header.php';
require_once 'connection.php';

// Verificar se é uma atualização via AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Configurações da tabela
$limiteGrupo = isset($_GET['limite']) && is_numeric($_GET['limite']) ? (int)$_GET['limite'] : 5;
$tempoSlide = isset($_GET['tempo']) && is_numeric($_GET['tempo']) ? (int)$_GET['tempo'] * 1000 : 5000;
$tempoExtraPorProduto = 500; // Adiciona 0.5s por produto
$tempoRolagem = isset($_GET['rolagem']) && is_numeric($_GET['rolagem']) ? (int)$_GET['rolagem'] : 20; // Tempo de rolagem em segundos
$grupoSelecionado = isset($_GET['grupo']) ? $_GET['grupo'] : 'todos';

// Tempo de propaganda - AJUSTADO
$tempo_propagandas = isset($_GET['tempo_propagandas']) && is_numeric($_GET['tempo_propagandas'])
    ? (int)$_GET['tempo_propagandas'] * 1000
    : 5000; // Converte para milissegundos

// Configuração de atualização automática (em minutos)
$atualizacao_auto = isset($_GET['atualizacao_auto']) && is_numeric($_GET['atualizacao_auto']) ? (int)$_GET['atualizacao_auto'] : 10;

// Configurações de propagandas
$propagandas_ativas = isset($_GET['propagandas_ativas']) ? (int)$_GET['propagandas_ativas'] : 1;
if ($propagandas_ativas !== 0) { // Considera qualquer valor diferente de 0 como ativo
    $propagandas_ativas = 1;
}

// Definir estilo de tema (pode vir do banco de dados ou configurações)
$tema = isset($_GET['tema']) ? $_GET['tema'] : 'padrao';
$temas = [
    'padrao' => [
        'background' => 'bg-dark',
        'text' => 'text-white',
        'header_bg' => 'bg-primary',
        'table_header' => 'table-primary'
    ],
    'supermercado' => [
        'background' => 'bg-success bg-gradient',
        'text' => 'text-white',
        'header_bg' => 'bg-success',
        'table_header' => 'table-success'
    ],
    'padaria' => [
        'background' => 'bg-warning bg-gradient',
        'text' => 'text-dark',
        'header_bg' => 'bg-warning',
        'table_header' => 'table-warning'
    ]
];

// Usar o tema selecionado ou o padrão se não existir
$estiloAtual = isset($temas[$tema]) ? $temas[$tema] : $temas['padrao'];

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php if (!$isAjax): ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($titulo) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #000;
                color: #fff;
                font-family: 'Arial', sans-serif;
                overflow: hidden;
            }

            .carousel {
                cursor: pointer;
            }

            .carousel-inner {
                pointer-events: none;
            }

            .carousel-item {
                min-height: 80vh;
                transition: transform 1s ease-in-out;
            }

            .grupo-header {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            .table {
                margin-bottom: 0;
                font-size: 1.25rem;
            }

            th {
                font-size: 1.5rem;
            }

            .preco-destaque {
                font-weight: bold;
                color: #ffc107;
                font-size: 1.3rem;
            }

            .footer {
                padding: 10px;
                font-size: 1.2rem;
                position: fixed;
                bottom: 0;
                width: 100%;
                z-index: 100;
            }

            .preco-novo {
                animation: highlight 5s;
            }

            @keyframes highlight {
                0% {
                    background-color: rgba(255, 193, 7, 0.8);
                }

                100% {
                    background-color: transparent;
                }
            }

            .tabela-container {
                position: relative;
                overflow: hidden;
                max-height: 65vh;
                margin-bottom: 20px;
            }

            .tabela-scroll {
                position: relative;
                width: 100%;
                transition: top 0.5s ease-in-out;
            }

            .tabela-grande {
                max-height: 95vh;
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
            }

            .tabela-grande::-webkit-scrollbar {
                width: 6px;
            }

            .tabela-grande::-webkit-scrollbar-track {
                background: transparent;
            }

            .tabela-grande::-webkit-scrollbar-thumb {
                background-color: rgba(255, 255, 255, 0.3);
                border-radius: 10px;
            }

            @media (min-width: 1200px) {
                .table-responsive {
                    font-size: 1rem;
                }
            }

            .tabela-grande table {
                margin-bottom: 0;
            }

            .tabela-grande th {
                position: sticky;
                top: 0;
                z-index: 999;

            }

            .tabela-grande td {
                padding: 8px 15px;
            }

            /* Estilos para as propagandas */
            .propaganda-item {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 80vh;
                padding: 20px;
                text-align: center;
            }

            .propaganda-imagem {
                max-width: 100%;
                max-height: 70vh;
                border-radius: 10px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            }

            .propaganda-titulo {
                position: absolute;
                bottom: 15%;
                left: 0;
                right: 0;
                background-color: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 15px;
                margin: 0 auto;
                max-width: 80%;
                border-radius: 8px;
            }

            /* Estilos para o botão de logout */
            .logout-btn {
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 1000;
                background: rgba(220, 53, 69, 0.9);
                border: none;
                color: white;
                padding: 8px 15px;
                border-radius: 5px;
                font-size: 0.9rem;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .logout-btn:hover {
                background: rgba(220, 53, 69, 1);
                color: white;
            }

            .logout-btn.show {
                opacity: 1;
            }

            /* Informações do usuário no footer */
            .user-info {
                font-size: 0.9rem;
                opacity: 0.8;
            }
        </style>
    <?php endif; ?>
</head>

<body class="<?= $estiloAtual['background'] ?> <?= $estiloAtual['text'] ?>">

    <!-- Botão de logout (aparece ao mover o mouse) -->
    <button id="logoutBtn" class="logout-btn" onclick="logout()" title="Sair do sistema">
        <i class="fas fa-sign-out-alt"></i> Sair
    </button>

    <div class="container-fluid p-2">
        <?php
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbType = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            // Verificar se as colunas 'ativo' existem nas tabelas produtos e grupos
            $temColunaAtivoProdutos = false;
            $temColunaAtivoGrupos = false;

            try {
                // Verificar coluna 'ativo' em produtos
                $checkStmt = $pdo->prepare("SELECT * FROM produtos LIMIT 1");
                $checkStmt->execute();
                $colunas = [];
                for ($i = 0; $i < $checkStmt->columnCount(); $i++) {
                    $colMeta = $checkStmt->getColumnMeta($i);
                    $colunas[] = strtolower($colMeta['name']);
                }
                $temColunaAtivoProdutos = in_array('ativo', $colunas);

                // Verificar coluna 'ativo' em grupos
                $checkStmt = $pdo->prepare("SELECT * FROM grupos LIMIT 1");
                $checkStmt->execute();
                $colunas = [];
                for ($i = 0; $i < $checkStmt->columnCount(); $i++) {
                    $colMeta = $checkStmt->getColumnMeta($i);
                    $colunas[] = strtolower($colMeta['name']);
                }
                $temColunaAtivoGrupos = in_array('ativo', $colunas);
            } catch (Exception $e) {
                // Se ocorrer um erro, assumimos que as colunas não existem
                $temColunaAtivoProdutos = false;
                $temColunaAtivoGrupos = false;
            }

            // Verificar se a coluna updated_at existe
            $temColuna = false;
            try {
                $checkStmt = $pdo->prepare("SELECT * FROM produtos LIMIT 1");
                $checkStmt->execute();
                $colunas = [];
                for ($i = 0; $i < $checkStmt->columnCount(); $i++) {
                    $colMeta = $checkStmt->getColumnMeta($i);
                    $colunas[] = strtolower($colMeta['name']);
                }
                $temColuna = in_array('updated_at', $colunas);
            } catch (Exception $e) {
                $temColuna = false;
            }

            // Filtrar produtos por empresa do usuário logado
            // Montar a consulta SQL com filtro de grupo se necessário e incluindo filtros de ativo
            if ($dbType == 'pgsql') {
                $sql = "SELECT p.nome as produto, p.preco, g.nome as grupo, 
                      p.id as produto_id, g.id as grupo_id" .
                    ($temColuna ? ", p.updated_at as ultima_atualizacao" : ", NULL as ultima_atualizacao") . "
               FROM produtos p
               JOIN grupos g ON p.grupo_id = g.id
               WHERE p.empresa_id = :empresa_id";

                // Adicionar filtro de itens ativos se as colunas existirem
                if ($temColunaAtivoGrupos) {
                    $sql .= " AND g.ativo = TRUE";
                }
                if ($temColunaAtivoProdutos) {
                    $sql .= " AND p.ativo = TRUE";
                }
            } else {
                $sql = "SELECT p.nome as produto, p.preco, g.nome as grupo, 
                      p.id as produto_id, g.id as grupo_id" .
                    ($temColuna ? ", p.updated_at as ultima_atualizacao" : ", NULL as ultima_atualizacao") . "
               FROM produtos p
               JOIN grupos g ON p.grupo_id = g.id
               WHERE p.empresa_id = :empresa_id";

                // Adicionar filtro de itens ativos se as colunas existirem
                if ($temColunaAtivoGrupos) {
                    $sql .= " AND g.ativo = 1";
                }
                if ($temColunaAtivoProdutos) {
                    $sql .= " AND p.ativo = 1";
                }
            }

            // Adicionar filtro de grupo se não for "todos"
            if ($grupoSelecionado !== 'todos') {
                $sql .= " AND g.id = :grupo_id";
            }

            $sql .= " ORDER BY g.nome, p.nome";

            $stmt = $pdo->prepare($sql);

            // Bind do parâmetro empresa_id
            $stmt->bindParam(':empresa_id', $_SESSION['empresa_id'], PDO::PARAM_INT);

            // Bind do parâmetro grupo se necessário
            if ($grupoSelecionado !== 'todos') {
                $stmt->bindParam(':grupo_id', $grupoSelecionado, PDO::PARAM_INT);
            }

            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar propagandas ativas se configurado para exibir (filtradas por empresa)
            $propagandas = [];
            if ($propagandas_ativas) {
                // Modificar a consulta SQL para respeitar os tipos do PostgreSQL e filtrar por empresa
                if ($dbType == 'pgsql') {
                    $sqlPropagandas = "SELECT id, titulo, descricao, imagem, ordem FROM propagandas 
                                      WHERE ativo = TRUE AND empresa_id = :empresa_id 
                                      ORDER BY ordem, id";
                } else {
                    // Para MySQL e outros que aceitam 1/0 como boolean
                    $sqlPropagandas = "SELECT id, titulo, descricao, imagem, ordem FROM propagandas 
                                      WHERE ativo = 1 AND empresa_id = :empresa_id 
                                      ORDER BY ordem, id";
                }
                $stmtPropagandas = $pdo->prepare($sqlPropagandas);
                $stmtPropagandas->bindParam(':empresa_id', $_SESSION['empresa_id'], PDO::PARAM_INT);
                $stmtPropagandas->execute();
                $propagandas = $stmtPropagandas->fetchAll(PDO::FETCH_ASSOC);
            }

            if (!empty($produtos)) {
                $grupos = [];
                foreach ($produtos as $produto) {
                    $grupos[$produto['grupo']][] = $produto;
                }

                // Se foi selecionado apenas um grupo, desativa o carrossel
                $mostrarCarrossel = ($grupoSelecionado === 'todos') || !empty($propagandas);

                if ($mostrarCarrossel) {
                    echo '<div id="grupoCarousel" class="carousel slide" data-bs-ride="carousel">';
                    echo '<div class="carousel-inner">';
                }

                $index = 0;
                $primeiro = true;
                $slideIndex = 0;

                // Função para misturar propagandas entre grupos
                function intercalarPropagandas($indiceGrupo, $totalGrupos, $propagandas)
                {
                    if (empty($propagandas)) return false;

                    // Exibe propaganda após cada 2 grupos (ajuste conforme necessário)
                    $frequencia = 2;

                    return ($indiceGrupo > 0 && $indiceGrupo % $frequencia === 0);
                }

                $totalGrupos = count($grupos);
                $grupoAtual = 0;

                foreach ($grupos as $nomeGrupo => $listaProdutos) {
                    $numProdutos = count($listaProdutos);
                    $tempoGrupo = $tempoSlide + ($numProdutos * $tempoExtraPorProduto);
                    $tempoBaseRolagem = $tempoRolagem * 1000;
                    $modoExibicao = $numProdutos > 10 ? 'grande' : 'normal';

                    if ($mostrarCarrossel) {
                        echo '<div class="carousel-item ' . ($primeiro ? 'active' : '') . '" data-bs-interval="' . ($modoExibicao == 'grande' ? max($tempoGrupo, $tempoBaseRolagem) : $tempoGrupo) . '">';
                    }

                    // Cabeçalho do grupo
                    echo '<div class="grupo-header ' . $estiloAtual['header_bg'] . ' ' . $estiloAtual['text'] . '">';
                    echo '<h2 class="text-center fs-1 fw-bold">' . htmlspecialchars($nomeGrupo) . '</h2>';
                    echo '</div>';

                    // Container da tabela
                    if ($modoExibicao != 'grande') {
                        echo '<div class="table-container mx-auto" style="max-width: 100%;">';
                    }
                    echo '<div id="tabela-container-' . $index . '" class="tabela-container tabela-grande">';
                    echo '<div id="tabela-scroll-' . $index . '" class="tabela-scroll" data-total-produtos="' . $numProdutos . '" data-tempo-rolagem="' . $tempoBaseRolagem . '">';

                    // Tabela de produtos
                    echo '<table class="table  table-striped table-hover border">';
                    echo '<thead class="' . $estiloAtual['table_header'] . '">';
                    echo '<tr>';
                    echo '<th class="text-center fs-2" style="width: 70%;">Produto</th>';
                    echo '<th class="text-center fs-2" style="width: 30%;">Preço</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    // Itens da tabela
                    foreach ($listaProdutos as $item) {
                        $recemAtualizado = false;
                        if (isset($item['ultima_atualizacao']) && !empty($item['ultima_atualizacao'])) {
                            $dataAtualizacao = new DateTime($item['ultima_atualizacao']);
                            $agora = new DateTime();
                            $diferenca = $agora->diff($dataAtualizacao);
                            $recemAtualizado = $diferenca->days < 1;
                        }

                        echo '<tr' . ($recemAtualizado ? ' class="preco-novo"' : '') . '>';
                        echo '<td class="text-center fs-4">' . htmlspecialchars($item['produto']) . '</td>';
                        echo '<td class="text-center ' . ($recemAtualizado ? 'preco-destaque' : 'fs-4 fw-bold') . '">';
                        echo 'R$ ' . number_format($item['preco'], 2, ',', '.') . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';

                    // Fechar containers
                    if ($modoExibicao != 'grande') {
                        echo '</div>'; // fecha table-container                        
                    }
                    echo '</div></div>'; // fecha tabela-scroll e tabela-container

                    if ($mostrarCarrossel) {
                        echo '</div>'; // fecha carousel-item
                    }

                    $primeiro = false;
                    $index++;
                    $slideIndex++;
                    $grupoAtual++;

                    // Inserir propaganda após alguns grupos se configurado e tiver propagandas disponíveis
                    if ($propagandas_ativas && !empty($propagandas)) {
                        $deveExibirPropaganda = intercalarPropagandas($grupoAtual, $totalGrupos, $propagandas);

                        if ($deveExibirPropaganda) {
                            // Pega uma propaganda da lista (de forma circular)
                            $indicePropaganda = ($grupoAtual / floor($totalGrupos / count($propagandas)) - 1) % count($propagandas);
                            $propaganda = $propagandas[intval($indicePropaganda)];

                            // Adiciona o slide de propaganda
                            echo '<div class="carousel-item" data-bs-interval="' . $tempo_propagandas . '">';
                            echo '<div class="propaganda-item">';
                            echo '<div class="position-relative">';

                            // Imagem da propaganda
                            echo '<img src="uploads/propagandas/' . htmlspecialchars($propaganda['imagem']) . '" 
                                     class="propaganda-imagem" alt="' . htmlspecialchars($propaganda['titulo']) . '">';

                            // Título/descrição na parte inferior
                            if (!empty($propaganda['titulo']) || !empty($propaganda['descricao'])) {
                                echo '<div class="propaganda-titulo">';
                                if (!empty($propaganda['titulo'])) {
                                    echo '<h3>' . htmlspecialchars($propaganda['titulo']) . '</h3>';
                                }
                                if (!empty($propaganda['descricao'])) {
                                    echo '<p class="mb-0">' . htmlspecialchars($propaganda['descricao']) . '</p>';
                                }
                                echo '</div>';
                            }

                            echo '</div>'; // fecha position-relative
                            echo '</div>'; // fecha propaganda-item
                            echo '</div>'; // fecha carousel-item

                            $slideIndex++;
                        }
                    }
                }

                if ($mostrarCarrossel) {
                    echo '</div>'; // fecha carousel-inner

                    // Adiciona controles de navegação se houver mais de um slide
                    if ($slideIndex > 1) {
                        // Controles de navegação comentados
                        // echo '<button class="carousel-control-prev" type="button" data-bs-target="#grupoCarousel" data-bs-slide="prev">';
                        // echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
                        // echo '<span class="visually-hidden">Anterior</span>';
                        // echo '</button>';
                        // echo '<button class="carousel-control-next" type="button" data-bs-target="#grupoCarousel" data-bs-slide="next">';
                        // echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
                        // echo '<span class="visually-hidden">Próximo</span>';
                        // echo '</button>';
                    }

                    echo '</div>'; // fecha grupoCarousel
                }
            } else {
                echo '<div class="alert alert-warning m-5">Nenhum produto disponível para exibição.</div>';
            }

            $stmt = null;
            $pdo = null;
        } catch (PDOException $e) {
            $isDev = false;
            if ($isDev) {
                echo '<div class="alert alert-danger m-4">
                    Ocorreu um erro ao carregar a tabela de preços. Por favor, tente novamente mais tarde.
                </div>';
            }
            echo '<div class="alert alert-danger m-4">
                    <h4>Erro ao carregar a tabela de preços:</h4>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Arquivo: ' . htmlspecialchars($e->getFile()) . ' (linha ' . $e->getLine() . ')</p>
                </div>';
        }
        ?>
    </div>
    <footer class="footer <?= $estiloAtual['header_bg'] ?> <?= $estiloAtual['text'] ?>">
        <div class="d-flex justify-content-between align-items-center" style="max-width: 100%;">
            <div><?= date('d/m/Y') ?></div>
            <div id="relogio"><?= date('H:i:s') ?></div>
            <div id="hora-atualizacao">
                Atualizado às <span id="hora-local"></span>
                <?php if ($atualizacao_auto > 0): ?>
                    <span id="proxima-atualizacao" class="ms-2">
                        (Próxima: <span id="tempo-restante"><?= $atualizacao_auto ?></span> min)
                    </span>
                <?php endif; ?>
                <div class="user-info mt-1">
                    Usuário: <?= htmlspecialchars($_SESSION['user_name']) ?> |
                    Empresa: <?= htmlspecialchars($_SESSION['empresa_nome']) ?>
                </div>
            </div>
        </div>
    </footer>

    <?php if (!$isAjax): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Armazenar o valor de atualização automática para uso no JavaScript
            const atualizacaoAuto = <?= $atualizacao_auto ?>;
            let tempoRestante = atualizacaoAuto * 60; // Convertendo para segundos
            let intervalAtualizacao = null;

            document.addEventListener('DOMContentLoaded', function() {
                // Inicializa o relógio (mantém o relógio atual sendo atualizado a cada segundo)
                setInterval(function() {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString('pt-BR');
                    document.getElementById('relogio').innerText = timeString;
                }, 1000);

                // Mostrar botão de logout ao mover o mouse
                let logoutTimer;
                document.addEventListener('mousemove', function() {
                    const logoutBtn = document.getElementById('logoutBtn');
                    logoutBtn.classList.add('show');

                    clearTimeout(logoutTimer);
                    logoutTimer = setTimeout(function() {
                        logoutBtn.classList.remove('show');
                    }, 3000); // Esconde após 3 segundos sem movimento
                });

                // Inicializa o carrossel
                setTimeout(function() {
                    try {
                        const carouselElement = document.getElementById('grupoCarousel');
                        if (carouselElement) {
                            // MUDANÇA: Obter o tempo do primeiro slide ativo se disponível
                            const activeItem = document.querySelector('.carousel-item.active');
                            const initialInterval = activeItem ?
                                parseInt(activeItem.dataset.bsInterval) :
                                <?= $tempoSlide ?>;

                            const carousel = new bootstrap.Carousel(carouselElement, {
                                interval: initialInterval, // Usar o intervalo do slide ativo
                                ride: 'carousel',
                                wrap: true
                            });

                            // Navegação por clique em qualquer área do carrossel
                            carouselElement.addEventListener('click', function(e) {
                                // Verifica se não está clicando em um link ou elemento interativo
                                if (e.target.tagName === 'A' || e.target.closest('a')) return;

                                // Obtém a posição do clique para determinar direção
                                const rect = this.getBoundingClientRect();
                                const clickPosition = (e.clientX - rect.left) / rect.width;

                                // Decide a direção baseada na posição do clique
                                if (clickPosition < 0.3) {
                                    carousel.prev(); // Clique na esquerda - volta
                                } else if (clickPosition > 0.7) {
                                    carousel.next(); // Clique na direita - avança
                                } else {
                                    carousel.next(); // Clique no meio - avança (comportamento padrão)
                                }

                                // Reinicia o intervalo do carrossel
                                carousel._config.interval = false; // Desativa o intervalo automático
                                setTimeout(() => {
                                    const activeItem = document.querySelector('.carousel-item.active');
                                    const interval = activeItem ? parseInt(activeItem.dataset.bsInterval) : <?= $tempoSlide ?>;
                                    console.log("Novo intervalo:", interval, "ms");
                                    carousel._config.interval = interval;
                                    carousel._cycle();
                                }, 100);
                            });

                            // Adicionar evento para logging e debug dos tempos do slide
                            carouselElement.addEventListener('slide.bs.carousel', function(e) {
                                resetAllScrolls();

                                // NOVO: Log para debug dos tempos do slide
                                const proximoSlide = e.relatedTarget;
                                const intervaloProximo = proximoSlide ? parseInt(proximoSlide.dataset.bsInterval) : <?= $tempoSlide ?>;
                                console.log("Mudando para slide com intervalo:", intervaloProximo, "ms");
                            });
                        }
                    } catch (e) {
                        console.error("Erro ao inicializar o carrossel:", e);
                    }
                    // Inicializar rolagem automática para tabelas grandes
                    iniciarRolagem();

                    // Inicializar hora de atualização
                    atualizarHoraLocal();

                    // Configurar atualização automática se habilitada
                    if (atualizacaoAuto > 0) {
                        iniciarContagemRegressiva();
                        iniciarAtualizacaoAutomatica();
                    }

                }, 1000); // Espera 1 segundo para garantir que o DOM esteja pronto 


            });

            // Função para resetar todas as rolagens quando mudar de slide
            function resetAllScrolls() {
                const containers = document.querySelectorAll('[id^="tabela-scroll-"]');
                containers.forEach(container => {
                    container.style.top = '0px';
                });
            }

            // Função para iniciar rolagem automática em tabelas grandes
            function iniciarRolagem() {
                const containers = document.querySelectorAll('[id^="tabela-scroll-"]');

                containers.forEach(container => {
                    const totalProdutos = parseInt(container.dataset.totalProdutos);
                    const tempoRolagem = parseInt(container.dataset.tempoRolagem);

                    if (totalProdutos > 10) {
                        // Configurar rolagem automática
                        const tabela = container.querySelector('table');
                        const containerHeight = container.parentElement.offsetHeight;
                        const tabelaHeight = tabela.offsetHeight;

                        if (tabelaHeight > containerHeight) {
                            const distanciaRolagem = tabelaHeight - containerHeight;
                            const velocidadeRolagem = distanciaRolagem / (tempoRolagem / 1000);

                            // Iniciar rolagem suave
                            let posicaoAtual = 0;
                            const intervaloRolagem = setInterval(() => {
                                posicaoAtual += velocidadeRolagem / 60; // 60 FPS

                                if (posicaoAtual >= distanciaRolagem) {
                                    posicaoAtual = distanciaRolagem;
                                    clearInterval(intervaloRolagem);
                                }

                                container.style.top = `-${posicaoAtual}px`;
                            }, 1000 / 60);
                        }
                    }
                });
            }

            // Função para atualizar a hora local
            function atualizarHoraLocal() {
                const now = new Date();
                const horaLocal = now.toLocaleTimeString('pt-BR');
                const elementoHora = document.getElementById('hora-local');
                if (elementoHora) {
                    elementoHora.innerText = horaLocal;
                }
            }

            // Função para iniciar contagem regressiva
            function iniciarContagemRegressiva() {
                const elementoTempo = document.getElementById('tempo-restante');
                if (!elementoTempo) return;

                intervalAtualizacao = setInterval(() => {
                    tempoRestante--;

                    if (tempoRestante <= 0) {
                        // Recarregar a página
                        window.location.reload();
                        return;
                    }

                    // Atualizar display da contagem regressiva
                    const minutos = Math.floor(tempoRestante / 60);
                    const segundos = tempoRestante % 60;

                    if (minutos > 0) {
                        elementoTempo.innerText = `${minutos}:${segundos.toString().padStart(2, '0')}`;
                    } else {
                        elementoTempo.innerText = `${segundos}s`;
                    }
                }, 1000);
            }

            // Função para iniciar atualização automática
            function iniciarAtualizacaoAutomatica() {
                // Atualizar via AJAX a cada intervalo definido
                setInterval(() => {
                    fetch(window.location.href, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro na resposta do servidor');
                            }
                            return response.text();
                        })
                        .then(data => {
                            // Atualizar apenas o conteúdo da tabela se necessário
                            // Opcional: implementar comparação de dados para atualização seletiva
                            console.log('Dados atualizados automaticamente');
                            atualizarHoraLocal();
                        })
                        .catch(error => {
                            console.error('Erro na atualização automática:', error);
                        });
                }, atualizacaoAuto * 60 * 1000); // Converter minutos para milissegundos
            }

            // Função de logout
            function logout() {
                if (confirm('Tem certeza que deseja sair do sistema?')) {
                    // Limpar intervalos
                    if (intervalAtualizacao) {
                        clearInterval(intervalAtualizacao);
                    }

                    // Redirecionar para logout
                    window.location.href = 'logout.php';
                }
            }

            // Função para pausar/retomar carrossel com tecla de espaço
            document.addEventListener('keydown', function(e) {
                if (e.code === 'Space') {
                    e.preventDefault();
                    const carouselElement = document.getElementById('grupoCarousel');
                    if (carouselElement) {
                        const carousel = bootstrap.Carousel.getInstance(carouselElement);
                        if (carousel) {
                            // Alternar entre pause e cycle
                            if (carousel._isPaused) {
                                carousel.cycle();
                            } else {
                                carousel.pause();
                            }
                        }
                    }
                }
            });

            // Função para detectar inatividade e mostrar protetor de tela
            let inactivityTimer;
            let inactivityTime = 30 * 60 * 1000; // 30 minutos em milissegundos

            function resetInactivityTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(() => {
                    // Opcional: Implementar protetor de tela ou logout automático
                    console.log('Sistema inativo por muito tempo');
                }, inactivityTime);
            }

            // Eventos para detectar atividade do usuário
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
                document.addEventListener(event, resetInactivityTimer, true);
            });

            // Inicializar timer de inatividade
            resetInactivityTimer();

            // Função para detectar mudanças de conexão
            window.addEventListener('online', function() {
                console.log('Conexão restaurada');
                // Opcional: Mostrar notificação de conexão restaurada
            });

            window.addEventListener('offline', function() {
                console.log('Conexão perdida');
                // Opcional: Mostrar notificação de conexão perdida
            });

            // Função para lidar com erros globais
            window.addEventListener('error', function(e) {
                console.error('Erro global capturado:', e.error);
                // Opcional: Implementar logging de erros
            });

            // Função para otimizar performance em dispositivos móveis
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                // Configurações específicas para dispositivos móveis
                document.body.style.webkitTextSizeAdjust = '100%';
                document.body.style.webkitFontSmoothing = 'antialiased';
            }
        </script>

        <!-- Script adicional para funcionalidades específicas -->
        // ... existing JavaScript ...

        // Function to initiate automatic update
        function iniciarAtualizacaoAutomatica() {
        setInterval(() => {
        fetch(window.location.href + '?isAjax=1', { // Add a flag to indicate AJAX request
        method: 'GET',
        headers: {
        'X-Requested-With': 'XMLHttpRequest'
        }
        })
        .then(response => {
        if (!response.ok) {
        throw new Error('Erro na resposta do servidor');
        }
        return response.text(); // Expecting HTML content for the carousel-inner
        })
        .then(data => {
        // Create a temporary div to parse the incoming HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = data;

        const newCarouselInner = tempDiv.querySelector('.carousel-inner');
        const currentCarouselInner = document.querySelector('.carousel-inner');

        if (newCarouselInner && currentCarouselInner) {
        // To avoid flickering, you might want to stop the carousel first
        const carouselElement = document.getElementById('grupoCarousel');
        const carouselInstance = bootstrap.Carousel.getInstance(carouselElement);
        if (carouselInstance) {
        carouselInstance.pause(); // Pause before updating content
        }

        currentCarouselInner.innerHTML = newCarouselInner.innerHTML; // Replace content

        // Re-initialize carousel (needed if slides change)
        if (carouselInstance) {
        carouselInstance.dispose(); // Dispose old instance
        }
        new bootstrap.Carousel(carouselElement, {
        interval: document.querySelector('.carousel-item.active') ?
        parseInt(document.querySelector('.carousel-item.active').dataset.bsInterval) :
        <?= $tempoSlide ?>,
        ride: 'carousel',
        wrap: true
        });

        // Re-start scrolling for new content
        resetAllScrolls(); // Ensure all scrolls are reset
        iniciarRolagem(); // Start new scrolling processes

        console.log('Conteúdo da tabela de preços atualizado via AJAX.');
        atualizarHoraLocal();
        // Reset countdown for next automatic refresh
        tempoRestante = atualizacaoAuto * 60;
        // If you want to visually restart the countdown, you'd update elementoTempo immediately
        const elementoTempo = document.getElementById('tempo-restante');
        if (elementoTempo) {
        const minutos = Math.floor(tempoRestante / 60);
        const segundos = tempoRestante % 60;
        elementoTempo.innerText = `${minutos}:${segundos.toString().padStart(2, '0')}`;
        }

        } else {
        console.warn('Não foi possível encontrar .carousel-inner no conteúdo AJAX.');
        }
        })
        .catch(error => {
        console.error('Erro na atualização automática:', error);
        // Consider showing a user-friendly error message
        });
        }, atualizacaoAuto * 60 * 1000); // Convert minutes to milliseconds
        }
    <?php endif; ?>

</body>

</html>