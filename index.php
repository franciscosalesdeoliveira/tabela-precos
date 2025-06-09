<?php

// Importante: Nenhum espaço, comentário ou código HTML antes desta linha
// Primeiro inclua o arquivo de conexão que contém session_start()
require_once 'connection.php';

// Depois o header e qualquer outra configuração necessária
$titulo = "Página Inicial";
require_once 'header.php';

// Todas as inclusões devem ser feitas antes de qualquer saída HTML
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabela de Preços - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.2);
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .dashboard {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
        }

        .dashboard-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .dashboard-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            animation: fadeIn 1s ease;
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 15px;
            animation: fadeIn 1s ease 0.3s both;
        }

        .dashboard-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }

        .dashboard-wave svg {
            display: block;
            width: calc(100% + 1.3px);
            height: 46px;
            transform: scaleY(-1);
        }

        .dashboard-wave .shape-fill {
            fill: #FFFFFF;
        }

        .dashboard-content {
            padding: 40px 30px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .menu-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: var(--dark);
            position: relative;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .menu-card:nth-child(1) {
            animation-delay: 0.3s;
        }

        .menu-card:nth-child(2) {
            animation-delay: 0.5s;
        }

        .menu-card:nth-child(3) {
            animation-delay: 0.7s;
        }

        .menu-card:nth-child(4) {
            animation-delay: 0.9s;
        }

        .menu-card:nth-child(5) {
            animation-delay: 1.1s;
        }

        .menu-card:nth-child(6) {
            animation-delay: 1.3s;
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 14px 22px rgba(67, 97, 238, 0.15);
        }

        .menu-icon {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--primary);
            background-color: var(--gray-100);
            transition: all 0.3s ease;
        }

        .menu-card:hover .menu-icon {
            background-color: var(--primary);
            color: white;
        }

        .menu-content {
            padding: 20px;
            text-align: center;
        }

        .menu-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--gray-800);
        }

        .menu-description {
            font-size: 0.9rem;
            color: #6c757d;
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            padding: 15px 0;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 30px;
            border-top: 1px solid var(--gray-200);
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
            .dashboard-title {
                font-size: 1.8rem;
            }

            .dashboard-content {
                padding: 30px 20px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: var(--shadow-hover) !important;
            transition: all 0.3s ease !important;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Dashboard de Preços</h1>
            <p class="dashboard-subtitle">Gerencie sua tabela de preços de forma simples e eficiente</p>
            <div class="dashboard-wave">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                </svg>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="menu-grid">
                <div class="stat-card">
                    <a href="tabela_precos.php" target="_blank" class="menu-card">
                        <div class="menu-icon">
                            <i class="fas fa-table"></i>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title">Tabela de Preços</h3>
                            <p class="menu-description">Visualize e gerencie todos os preços cadastrados no sistema</p>
                        </div>
                    </a>
                </div>

                <div class="stat-card">
                    <a href="cadastro_grupos.php" class="menu-card">
                        <div class="menu-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title">Cadastro de Grupos</h3>
                            <p class="menu-description">Crie e organize grupos para classificar seus produtos</p>
                        </div>
                    </a>
                </div>

                <div class="stat-card">
                    <a href="cadastro_produtos.php" class="menu-card">
                        <div class="menu-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title">Cadastro de Produtos</h3>
                            <p class="menu-description">Adicione novos produtos e gerencie os existentes</p>
                        </div>
                    </a>
                </div>

                <div class="stat-card">
                    <a href="excel.php" class="menu-card">
                        <div class="menu-icon">
                            <i class="fas fa-file-import"></i>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title">Importar CSV</h3>
                            <p class="menu-description">Importe produtos em massa a partir de arquivos CSV</p>
                        </div>
                    </a>
                </div>

                <div class="stat-card">
                    <a href="configuracoes.php" class="menu-card">
                        <div class="menu-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title">Configurações</h3>
                            <p class="menu-description">Personalize as configurações da sua tabela de preços</p>
                        </div>
                    </a>
                </div>

                <div class="stat-card">
                    <a href="contato.php" class="menu-card">
                        <div class="menu-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title">Fale Conosco</h3>
                            <p class="menu-description">Entre em contato para suporte e atendimento</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="footer">
                <p>Sistema de Tabela de Preços &copy; <?php echo date('Y'); ?> <br><br>
                    <i class="fab fa-whatsapp" style="color: green"></i>
                    <a href="https://wa.me/5515981813900" class="text-decoration-none text-dark" target="_blank">(15) 98181-3900</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>