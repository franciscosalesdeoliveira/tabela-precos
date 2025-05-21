<?php
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
        </style>
    <?php endif; ?>
</head>

<body class="<?= $estiloAtual['background'] ?> <?= $estiloAtual['text'] ?>">


    <div class="container-fluid p-2">
        <?php
        try {
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

            // Montar a consulta SQL com filtro de grupo se necessário e incluindo filtros de ativo
            if ($dbType == 'pgsql') {
                $sql = "SELECT p.nome as produto, p.preco, g.nome as grupo, 
                      p.id as produto_id, g.id as grupo_id" .
                    ($temColuna ? ", p.updated_at as ultima_atualizacao" : ", NULL as ultima_atualizacao") . "
               FROM produtos p
               JOIN grupos g ON p.grupo_id = g.id
               WHERE 1=1";

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
               WHERE 1=1";

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

            // Bind do parâmetro se necessário
            if ($grupoSelecionado !== 'todos') {
                $stmt->bindParam(':grupo_id', $grupoSelecionado, PDO::PARAM_INT);
            }

            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar propagandas ativas se configurado para exibir
            $propagandas = [];
            if ($propagandas_ativas) {
                // Modificar a consulta SQL para respeitar os tipos do PostgreSQL
                if ($dbType == 'pgsql') {
                    $sqlPropagandas = "SELECT id, titulo, descricao, imagem, ordem FROM propagandas WHERE ativo = TRUE ORDER BY ordem, id";
                } else {
                    // Para MySQL e outros que aceitam 1/0 como boolean
                    $sqlPropagandas = "SELECT id, titulo, descricao, imagem, ordem FROM propagandas WHERE ativo = 1 ORDER BY ordem, id";
                }
                $stmtPropagandas = $pdo->query($sqlPropagandas);
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
            </div>
        </div>

        <!-- adicionar o footer padrão depois -->
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
                }, 500);

                // Configurar rolagem automática para tabelas grandes
                setupAutoScroll();

                // Atualizar horário local - APENAS UMA VEZ NO CARREGAMENTO
                atualizarHoraLocal();

                // Iniciar contagem regressiva para próxima atualização
                if (atualizacaoAuto > 0) {
                    iniciarContagemRegressiva();
                }

                // Pré-carregar imagens de propagandas para transições suaves
                precarregarImagensPropagandas();
            });

            // Pré-carregar imagens de propagandas
            function precarregarImagensPropagandas() {
                const imagensPropagandas = document.querySelectorAll('.propaganda-imagem');
                imagensPropagandas.forEach(img => {
                    const imgPreload = new Image();
                    imgPreload.src = img.src;
                });
            }

            function setupAutoScroll() {
                const scrollContainers = document.querySelectorAll('.tabela-scroll');

                scrollContainers.forEach(container => {
                    const containerParent = container.parentElement;

                    if (container.offsetHeight > containerParent.offsetHeight) {
                        const totalProdutos = parseInt(container.dataset.totalProdutos || 0);
                        const tempoRolagem = parseInt(container.dataset.tempoRolagem || 20000);
                        const totalHeight = container.offsetHeight - containerParent.offsetHeight;

                        let scrollStep = 1;
                        if (totalProdutos > 30) {
                            scrollStep = Math.max(1, Math.floor(totalHeight / (tempoRolagem / 30)));
                        } else {
                            scrollStep = Math.max(1, Math.floor(totalHeight / (tempoRolagem / 50)));
                        }

                        container.scrollData = {
                            currentPosition: 0,
                            maxScroll: totalHeight,
                            step: scrollStep,
                            interval: null,
                            totalProdutos: totalProdutos
                        };

                        startScroll(container);
                    }
                });
            }

            function startScroll(container) {
                if (container.scrollData && container.scrollData.interval) {
                    clearInterval(container.scrollData.interval);
                }

                container.scrollData.interval = setInterval(() => {
                    container.scrollData.currentPosition += container.scrollData.step;

                    if (container.scrollData.currentPosition >= container.scrollData.maxScroll) {
                        container.scrollData.currentPosition = 0;
                        container.style.transition = 'none';
                        container.style.top = '0px';

                        setTimeout(() => {
                            container.style.transition = 'top 0.5s ease-in-out';
                        }, 50);
                    } else {
                        container.style.top = `-${container.scrollData.currentPosition}px`;
                    }
                }, 50);
            }

            function resetAllScrolls() {
                const scrollContainers = document.querySelectorAll('.tabela-scroll');
                scrollContainers.forEach(container => {
                    if (container.scrollData && container.scrollData.interval) {
                        clearInterval(container.scrollData.interval);
                        container.scrollData.currentPosition = 0;
                        container.style.top = '0px';
                        startScroll(container);
                    }
                });
            }

            function atualizarHoraLocal() {
                const now = new Date();
                const horaLocal = now.toLocaleTimeString('pt-BR');
                document.getElementById('hora-local').innerText = horaLocal;
            }

            function iniciarContagemRegressiva() {
                // Limpar qualquer intervalo existente
                if (intervalAtualizacao) {
                    clearInterval(intervalAtualizacao);
                }

                // Configurar o novo intervalo
                tempoRestante = atualizacaoAuto * 60; // Reiniciar contagem (em segundos)
                atualizarContador();

                intervalAtualizacao = setInterval(() => {
                    tempoRestante--;
                    atualizarContador();

                    // Quando chegar a zero, recarregar a página
                    if (tempoRestante <= 0) {
                        recarregarPagina();
                    }
                }, 1000);
            }

            function atualizarContador() {
                const minutos = Math.floor(tempoRestante / 60);
                const segundos = tempoRestante % 60;
                const formatado = `${minutos}:${segundos.toString().padStart(2, '0')}`;
                document.getElementById('tempo-restante').innerText = formatado;
            }

            function recarregarPagina() {
                // Mantém os parâmetros da URL atual
                window.location.reload();
            }

            // Função para recarregar conteúdo via AJAX
            function recarregarConteudoAjax() {
                const xhr = new XMLHttpRequest();
                const url = window.location.href + (window.location.href.includes('?') ? '&ajax=1' : '?ajax=1');

                xhr.onreadystatechange = function() {
                    if (this.readyState === 4 && this.status === 200) {
                        document.querySelector('.container-fluid').innerHTML = this.responseText;
                        setupAutoScroll();
                        atualizarHoraLocal();
                        iniciarContagemRegressiva();
                    }
                };

                xhr.open('GET', url, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send();
            }

            // Evento para teclas de atalho
            document.addEventListener('keydown', function(e) {
                const carouselElement = document.getElementById('grupoCarousel');
                if (!carouselElement) return;

                const carousel = bootstrap.Carousel.getInstance(carouselElement);
                if (!carousel) return;

                // Setas esquerda/direita para navegação
                if (e.key === 'ArrowLeft') {
                    carousel.prev();
                } else if (e.key === 'ArrowRight') {
                    carousel.next();
                } else if (e.key === 'r' || e.key === 'R') {
                    // Tecla 'r' para recarregar
                    recarregarPagina();
                }
            });

            // Adicionar uma função para verificar periodicamente se há atualizações no banco de dados
            // Esta é uma solução opcional que pode ser implementada posteriormente
        </script>
    <?php endif; ?>
</body>

</html>