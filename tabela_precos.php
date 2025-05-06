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
                max-height: 65vh;
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
                z-index: 10;
            }

            .tabela-grande td {
                padding: 8px 15px;
            }
        </style>
    <?php endif; ?>
</head>

<body class="<?= $estiloAtual['background'] ?> <?= $estiloAtual['text'] ?>">
    <?php if (!$isAjax): ?>
        <header class="<?= $estiloAtual['header_bg'] ?> text-center py-3">
            <h1 class="display-4"><?= htmlspecialchars($titulo) ?></h1>
        </header>
    <?php endif; ?>

    <div class="container-fluid p-2">
        <?php
        try {
            $dbType = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

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

            // Consulta SQL
            if ($dbType == 'pgsql') {
                $sql = "SELECT p.nome as produto, p.preco, g.nome as grupo, 
                              p.id as produto_id, g.id as grupo_id" .
                    ($temColuna ? ", p.updated_at as ultima_atualizacao" : ", NULL as ultima_atualizacao") . "
                       FROM produtos p
                       JOIN grupos g ON p.grupo_id = g.id
                       ORDER BY g.nome, p.nome";
            } else {
                $sql = "SELECT p.nome as produto, p.preco, g.nome as grupo, 
                              p.id as produto_id, g.id as grupo_id" .
                    ($temColuna ? ", p.updated_at as ultima_atualizacao" : ", NULL as ultima_atualizacao") . "
                       FROM produtos p
                       JOIN grupos g ON p.grupo_id = g.id
                       ORDER BY g.nome, p.nome";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($produtos)) {
                echo '<div class="alert alert-warning m-5">Nenhum produto disponível para exibição.</div>';
            } else {
                $grupos = [];
                foreach ($produtos as $produto) {
                    $grupos[$produto['grupo']][] = $produto;
                }

                // ... (código anterior permanece igual)

                echo '<div id="grupoCarousel" class="carousel slide" data-bs-ride="carousel">';
                echo '<div class="carousel-inner">';

                $index = 0; // Inicializa o índice
                $primeiro = true;

                foreach ($grupos as $nomeGrupo => $listaProdutos) {
                    $numProdutos = count($listaProdutos);
                    $tempoGrupo = $tempoSlide + ($numProdutos * $tempoExtraPorProduto);
                    $tempoBaseRolagem = $tempoRolagem * 1000;
                    $modoExibicao = $numProdutos > 10 ? 'grande' : 'normal';

                    // Item do carrossel
                    echo '<div class="carousel-item ' . ($primeiro ? 'active' : '') . '" data-bs-interval="' . ($modoExibicao == 'grande' ? max($tempoGrupo, $tempoBaseRolagem) : $tempoGrupo) . '">';

                    // Cabeçalho do grupo
                    echo '<div class="grupo-header ' . $estiloAtual['header_bg'] . ' ' . $estiloAtual['text'] . '">';
                    echo '<h2 class="text-center fs-1 fw-bold">' . htmlspecialchars($nomeGrupo) . '</h2>';
                    echo '</div>';

                    // Container da tabela
                    if ($modoExibicao == 'grande') {
                        echo '<div id="tabela-container-' . $index . '" class="tabela-container tabela-grande">';
                        echo '<div id="tabela-scroll-' . $index . '" class="tabela-scroll" data-total-produtos="' . $numProdutos . '" data-tempo-rolagem="' . $tempoBaseRolagem . '">';
                    } else {
                        echo '<div class="table-container mx-auto" style="max-width: 90%;">';
                    }

                    // Tabela de produtos
                    echo '<table class="table table-striped table-hover border">';
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
                    if ($modoExibicao == 'grande') {
                        echo '</div></div>'; // fecha tabela-scroll e tabela-container
                    } else {
                        echo '</div>'; // fecha table-container
                    }

                    echo '</div>'; // fecha carousel-item

                    $primeiro = false;
                    $index++;
                }

                echo '</div>'; // fecha carousel-inner
                echo '</div>'; // fecha grupoCarousel

                // ... (código posterior permanece igual)
            }

            $stmt = null;
            $pdo = null;
        } catch (PDOException $e) {
            $isDev = true;
            if ($isDev) {
                echo '<div class="alert alert-danger m-4">
                    <h4>Erro ao carregar a tabela de preços:</h4>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Arquivo: ' . htmlspecialchars($e->getFile()) . ' (linha ' . $e->getLine() . ')</p>
                </div>';
            } else {
                echo '<div class="alert alert-danger m-4">
                    Ocorreu um erro ao carregar a tabela de preços. Por favor, tente novamente mais tarde.
                </div>';
            }
        }
        ?>
    </div>

    <footer class="footer <?= $estiloAtual['header_bg'] ?> <?= $estiloAtual['text'] ?>">
        <div class="d-flex justify-content-between align-items-center">
            <div><?= date('d/m/Y') ?></div>
            <div id="relogio"><?= date('H:i:s') ?></div>
            <div id="hora-atualizacao">
                Atualizado às <span id="hora-local"></span>
            </div>
        </div>
    </footer>

    <?php if (!$isAjax): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            setInterval(function() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('pt-BR');
                document.getElementById('relogio').innerText = timeString;
            }, 1000);

            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    try {
                        const carouselElement = document.getElementById('grupoCarousel');
                        if (carouselElement) {
                            const tempoBase = <?= $tempoSlide ?>;
                            const carousel = new bootstrap.Carousel(carouselElement, {
                                interval: tempoBase,
                                ride: 'carousel',
                                wrap: true
                            });
                        }
                    } catch (e) {
                        console.error("Erro ao inicializar o carrossel:", e);
                    }
                }, 500);

                setupAutoScroll();

                document.getElementById('grupoCarousel').addEventListener('slide.bs.carousel', function() {
                    resetAllScrolls();
                });
            });

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
                    if (container.scrollData) {
                        clearInterval(container.scrollData.interval);
                        container.scrollData.currentPosition = 0;
                        container.style.top = '0px';

                        setTimeout(() => {
                            if (container.offsetHeight > container.parentElement.offsetHeight) {
                                startScroll(container);
                            }
                        }, 500);
                    }
                });
            }

            setInterval(function() {
                fetch('tabela_precos.php?limite=<?= $limiteGrupo ?>&tempo=<?= $tempoSlide / 1000 ?>&tema=<?= $tema ?>&rolagem=<?= $tempoRolagem ?>', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        document.querySelector('.container-fluid').innerHTML = html;
                        const carousel = document.querySelector('#grupoCarousel');
                        if (carousel) {
                            const bsCarousel = new bootstrap.Carousel(carousel);
                            bsCarousel.cycle();
                        }
                        setupAutoScroll();
                    })
                    .catch(error => console.error('Erro ao atualizar preços:', error));
            }, 10 * 60 * 1000);
        </script>
    <?php endif; ?>
    <?php if (!$isAjax): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Inicializa o carrossel
                const carousel = new bootstrap.Carousel(document.getElementById('grupoCarousel'), {
                    interval: false // Desativa a rotação automática
                });

                // Navegação por clique em qualquer área do carrossel
                document.getElementById('grupoCarousel').addEventListener('click', function(e) {
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
                });

                // Configura rolagem automática para tabelas grandes
                setupAutoScroll();

                // Atualiza o relógio
                setInterval(function() {
                    document.getElementById('relogio').innerText = new Date().toLocaleTimeString('pt-BR');
                }, 1000);

                // Funções setupAutoScroll() e resetAllScrolls() permanecem iguais
                function setupAutoScroll() {
                    // ... [mantenha o código existente] ...
                }

                function resetAllScrolls() {
                    // ... [mantenha o código existente] ...
                }
            });

            // Atualizar horário local
            function atualizarHoraLocal() {
                const agora = new Date();
                const horaFormatada = agora.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
                });
                document.getElementById('hora-local').textContent = horaFormatada;
            }

            // Chamar a função imediatamente e a cada minuto
            atualizarHoraLocal();
            setInterval(atualizarHoraLocal, 60000);
        </script>
    <?php endif; ?>
</body>

</html>