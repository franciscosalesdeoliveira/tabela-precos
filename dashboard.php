<?php
$titulo = "Painel Administrativo";
require_once 'connection.php';
require_once 'header.php';

// Estatísticas do sistema
try {
    // Contar empresas ativas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_empresas FROM empresas WHERE ativo = 'S'");
    $stmt->execute();
    $empresas_ativas = $stmt->fetch()['total_empresas'] ?? 0;

    // Contar empresas inativas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_empresas FROM empresas WHERE ativo = 'N'");
    $stmt->execute();
    $empresas_inativas = $stmt->fetch()['total_empresas'] ?? 0;

    // Contar total de empresas
    $total_empresas = $empresas_ativas + $empresas_inativas;

    // Contar usuários (preparando para quando a tabela existir)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_usuarios FROM usuarios WHERE ativo = 'S'");
        $stmt->execute();
        $usuarios_ativos = $stmt->fetch()['total_usuarios'] ?? 0;
    } catch (PDOException $e) {
        $usuarios_ativos = 0; // Tabela ainda não existe
    }

    // Últimas empresas cadastradas
    $stmt = $pdo->prepare("SELECT razao_social, fantasia, created_at FROM empresas ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $ultimas_empresas = $stmt->fetchAll();

} catch (PDOException $e) {
    $empresas_ativas = 0;
    $empresas_inativas = 0;
    $total_empresas = 0;
    $usuarios_ativos = 0;
    $ultimas_empresas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--dark-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .welcome-title {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 300;
        }

        .welcome-subtitle {
            color: var(--dark-color);
            opacity: 0.8;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            color: var(--dark-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--dark-color);
            opacity: 0.7;
            font-size: 0.9rem;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .modules-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .module-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.5s;
            opacity: 0;
        }

        .module-card:hover::before {
            animation: shine 0.5s ease-in-out;
        }

        .module-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }

        .module-card.empresas {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
        }

        .module-card.usuarios {
            background: linear-gradient(135deg, var(--info-color), #138496);
        }

        .module-card.relatorios {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
        }

        .module-card.configuracoes {
            background: linear-gradient(135deg, var(--dark-color), #23272b);
        }

        .module-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .module-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .module-description {
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 1rem;
            border-left: 3px solid var(--primary-color);
            margin-bottom: 1rem;
            background: rgba(52, 152, 219, 0.05);
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: rgba(52, 152, 219, 0.1);
            transform: translateX(5px);
        }

        .activity-company {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .activity-fantasy {
            color: var(--dark-color);
            opacity: 0.8;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .activity-date {
            color: var(--dark-color);
            opacity: 0.6;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--dark-color);
            opacity: 0.6;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                🏢 Sistema Administrativo
            </div>
            <div class="user-info">
                <span>👤 Administrador</span>
                <span>|</span>
                <span><?= date('d/m/Y H:i') ?></span>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h1 class="welcome-title">Bem-vindo ao Painel Administrativo</h1>
            <p class="welcome-subtitle">Gerencie empresas, usuários e configurações do sistema</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Empresas Ativas</span>
                    <div class="stat-icon" style="background: var(--success-color);">🏢</div>
                </div>
                <div class="stat-number"><?= number_format($empresas_ativas) ?></div>
                <div class="stat-label">Cadastradas e ativas</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Empresas Inativas</span>
                    <div class="stat-icon" style="background: var(--warning-color);">⏸️</div>
                </div>
                <div class="stat-number"><?= number_format($empresas_inativas) ?></div>
                <div class="stat-label">Temporariamente inativas</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total de Empresas</span>
                    <div class="stat-icon" style="background: var(--primary-color);">📊</div>
                </div>
                <div class="stat-number"><?= number_format($total_empresas) ?></div>
                <div class="stat-label">Cadastros no sistema</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Usuários Ativos</span>
                    <div class="stat-icon" style="background: var(--info-color);">👥</div>
                </div>
                <div class="stat-number"><?= number_format($usuarios_ativos) ?></div>
                <div class="stat-label">Usuários do sistema</div>
            </div>
        </div>

        <div class="main-content">
            <div class="modules-section">
                <h2 class="section-title">🚀 Módulos do Sistema</h2>
                <div class="modules-grid">
                    <a href="cadastro_empresa.php" class="module-card empresas">
                        <div class="module-icon">🏢</div>
                        <div class="module-title">Cadastro de Empresas</div>
                        <div class="module-description">
                            Cadastre e gerencie empresas utilizando consulta automática de CNPJ
                        </div>
                    </a>

                    <a href="cadastro_usuario.php" class="module-card usuarios">
                        <div class="module-icon">👤</div>
                        <div class="module-title">Cadastro de Usuários</div>
                        <div class="module-description">
                            Gerencie usuários do sistema e suas permissões de acesso
                        </div>
                    </a>

                    <a href="listar_empresas.php" class="module-card empresas">
                        <div class="module-icon">📋</div>
                        <div class="module-title">Listar Empresas</div>
                        <div class="module-description">
                            Visualize, edite e gerencie todas as empresas cadastradas
                        </div>
                    </a>

                    <a href="relatorios.php" class="module-card relatorios">
                        <div class="module-icon">📈</div>
                        <div class="module-title">Relatórios</div>
                        <div class="module-description">
                            Gere relatórios detalhados e análises do sistema
                        </div>
                    </a>

                    <a href="configuracoes.php" class="module-card configuracoes">
                        <div class="module-icon">⚙️</div>
                        <div class="module-title">Configurações</div>
                        <div class="module-description">
                            Configure parâmetros gerais e preferências do sistema
                        </div>
                    </a>

                    <a href="backup.php" class="module-card configuracoes">
                        <div class="module-icon">💾</div>
                        <div class="module-title">Backup</div>
                        <div class="module-description">
                            Realize backup e restauração dos dados do sistema
                        </div>
                    </a>
                </div>
            </div>

            <div class="recent-activity">
                <h2 class="section-title">📅 Atividade Recente</h2>
                <?php if (!empty($ultimas_empresas)): ?>
                    <ul class="activity-list">
                        <?php foreach ($ultimas_empresas as $empresa): ?>
                            <li class="activity-item">
                                <div class="activity-company"><?= htmlspecialchars($empresa['razao_social']) ?></div>
                                <?php if ($empresa['fantasia']): ?>
                                    <div class="activity-fantasy"><?= htmlspecialchars($empresa['fantasia']) ?></div>
                                <?php endif; ?>
                                <div class="activity-date">
                                    Cadastrada em <?= $empresa['created_at'] ? date('d/m/Y H:i', strtotime($empresa['created_at'])) : 'Data não disponível' ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nenhuma empresa cadastrada recentemente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Animações e interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Animação de entrada para os cards
            const cards = document.querySelectorAll('.stat-card, .module-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Atualização automática do horário
            function atualizarHorario() {
                const agora = new Date();
                const horarioElement = document.querySelector('.user-info span:last-child');
                if (horarioElement) {
                    horarioElement.textContent = agora.toLocaleString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }

            // Atualizar a cada minuto
            setInterval(atualizarHorario, 60000);

            // Efeito de hover nos módulos
            const moduleCards = document.querySelectorAll('.module-card');
            moduleCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>

</html>