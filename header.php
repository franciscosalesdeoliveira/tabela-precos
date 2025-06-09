<?php
// header.php - Header melhorado com navegação completa
require_once 'auth_check.php';
require_once 'connection.php';

// Verificar autenticação
verificarAutenticacao();

// Obter dados do usuário
$usuario = obterUsuarioLogado();

// Atualizar último acesso (opcional)
atualizarUltimoAcesso();

// Definir página atual
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Configurar menu dinâmico baseado nas permissões
$menu_items = [
    'tabela_precos.php' => [
        'titulo' => 'Tabela de Preços',
        'icone' => 'fas fa-table',
        'permissao' => null // Todos podem acessar
    ],
    'produtos.php' => [
        'titulo' => 'Produtos',
        'icone' => 'fas fa-box',
        'permissao' => null
    ],
    'relatorios.php' => [
        'titulo' => 'Relatórios',
        'icone' => 'fas fa-chart-bar',
        'permissao' => null
    ],
    'empresas.php' => [
        'titulo' => 'Empresas',
        'icone' => 'fas fa-building',
        'permissao' => 'admin'
    ],
    'cadastro_usuarios.php' => [
        'titulo' => 'Usuários',
        'icone' => 'fas fa-users',
        'permissao' => 'admin'
    ],
    'configuracoes.php' => [
        'titulo' => 'Configurações',
        'icone' => 'fas fa-cog',
        'permissao' => 'admin'
    ]
];

// Função para verificar se o usuário tem permissão para acessar uma página
function temPermissao($permissao_necessaria)
{
    if ($permissao_necessaria === null) {
        return true; // Todos podem acessar
    }

    // Verificar se existe função de verificação de permissão
    if (function_exists('verificarPermissao')) {
        return verificarPermissao($permissao_necessaria);
    }

    // Fallback: verificar se é admin baseado na sessão
    return isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'admin';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo) ? htmlspecialchars($titulo) : 'Sistema de Preços'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .navbar-custom {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: white;
        }

        .user-details {
            color: white;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 0;
        }

        .user-company {
            opacity: 0.8;
            font-size: 0.8rem;
            margin-bottom: 0;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-1px);
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            margin: 0 5px;
            padding: 8px 12px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .navbar-nav .nav-link.active {
            color: white !important;
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        /* Dropdown do menu em mobile */
        .navbar-collapse {
            background: rgba(67, 97, 238, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            margin-top: 10px;
            padding: 10px;
        }

        @media (max-width: 768px) {
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
            }

            .user-details {
                display: flex;
                flex-direction: column;
            }

            .navbar-nav .nav-link {
                margin: 2px 0;
            }
        }

        .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 10px 0;
        }

        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: rgba(67, 97, 238, 0.1);
            transform: translateX(5px);
        }

        /* Indicador de notificações */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .nav-item {
            position: relative;
        }

        /* Breadcrumb style */
        .page-header {
            padding: 20px 0;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="tabela_precos.php">
                <i class="fas fa-chart-line me-2"></i>
                Sistema de Preços
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($menu_items as $arquivo => $item): ?>
                        <?php if (temPermissao($item['permissao'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($pagina_atual == $arquivo) ? 'active' : ''; ?>"
                                    href="<?php echo $arquivo; ?>">
                                    <i class="<?php echo $item['icone']; ?> me-1"></i>
                                    <?php echo $item['titulo']; ?>

                                    <?php
                                    // Exemplo de notificações (você pode personalizar)
                                    if ($arquivo == 'cadastro_usuarios.php' && temPermissao('admin')):
                                        // Aqui você pode verificar se há usuários pendentes, etc.
                                    ?>
                                        <!-- <span class="notification-badge">3</span> -->
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                    </div>

                    <div class="user-details d-none d-md-block">
                        <div class="user-name"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                        <div class="user-company"><?php echo htmlspecialchars($usuario['empresa_nome']); ?></div>
                    </div>

                    <div class="dropdown">
                        <button class="btn logout-btn dropdown-toggle" type="button" id="userDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i>
                            <span class="d-none d-md-inline">Conta</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="dropdown-item-text">
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['empresa_nome']); ?></small>
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="perfil.php">
                                    <i class="fas fa-user-edit me-2"></i> Meu Perfil
                                </a>
                            </li>
                            <?php if (temPermissao('admin')): ?>
                                <li>
                                    <a class="dropdown-item" href="configuracoes.php">
                                        <i class="fas fa-cog me-2"></i> Configurações
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="confirmarLogout()">
                                    <i class="fas fa-sign-out-alt me-2"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header da página (opcional - pode ser usado em cada página) -->
    <?php if (isset($mostrar_header_pagina) && $mostrar_header_pagina): ?>
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="page-title">
                            <?php
                            if (isset($menu_items[$pagina_atual])) {
                                echo '<i class="' . $menu_items[$pagina_atual]['icone'] . ' me-2"></i>';
                                echo $menu_items[$pagina_atual]['titulo'];
                            } else {
                                echo isset($titulo) ? $titulo : 'Sistema de Preços';
                            }
                            ?>
                        </h1>
                        <?php if (isset($subtitulo)): ?>
                            <p class="page-subtitle"><?php echo htmlspecialchars($subtitulo); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($botoes_header)): ?>
                        <div class="col-auto">
                            <?php echo $botoes_header; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function confirmarLogout() {
            if (confirm('Tem certeza que deseja sair do sistema?')) {
                window.location.href = 'auth_check.php?logout=1';
            }
        }

        // Adicionar indicador de conexão
        function verificarConexao() {
            if (navigator.onLine) {
                console.log('Sistema online');
            } else {
                alert('Conexão com a internet perdida. Algumas funcionalidades podem não funcionar corretamente.');
            }
        }

        window.addEventListener('online', verificarConexao);
        window.addEventListener('offline', verificarConexao);

        // Auto-logout por inatividade (opcional - 30 minutos)
        let tempoInatividade = 30 * 60 * 1000; // 30 minutos
        let timerInatividade;

        function resetarTimerInatividade() {
            clearTimeout(timerInatividade);
            timerInatividade = setTimeout(function() {
                if (confirm('Sua sessão expirará em breve devido à inatividade. Deseja continuar?')) {
                    resetarTimerInatividade();
                } else {
                    window.location.href = 'auth_check.php?logout=1';
                }
            }, tempoInatividade);
        }

        // Resetar timer em atividade do usuário
        document.addEventListener('mousemove', resetarTimerInatividade);
        document.addEventListener('keypress', resetarTimerInatividade);
        document.addEventListener('click', resetarTimerInatividade);

        // Iniciar timer
        resetarTimerInatividade();

        // Highlight da página atual
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar classe active ao link da página atual se não estiver presente
            const currentPage = '<?php echo $pagina_atual; ?>';
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>