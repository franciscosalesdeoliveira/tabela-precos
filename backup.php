<?php
$titulo = "Sistema de Backup";
require_once 'connection.php';
require_once 'header.php';

// Configurações de backup
$backup_dir = __DIR__ . '/backups/';
$max_backups = 10; // Máximo de backups a manter

// Criar diretório de backup se não existir
if (!is_dir($backup_dir)) {
    if (!mkdir($backup_dir, 0755, true)) {
        $erro = "Erro ao criar diretório de backup: $backup_dir";
    }
}

// Processar ações
$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'backup_completo':
            $resultado = criarBackupCompleto($backup_dir);
            if ($resultado['sucesso']) {
                $sucesso = $resultado['mensagem'];
            } else {
                $erro = $resultado['mensagem'];
            }
            break;
            
        case 'backup_estrutura':
            $resultado = criarBackupEstrutura($backup_dir);
            if ($resultado['sucesso']) {
                $sucesso = $resultado['mensagem'];
            } else {
                $erro = $resultado['mensagem'];
            }
            break;
            
        case 'backup_dados':
            $resultado = criarBackupDados($backup_dir);
            if ($resultado['sucesso']) {
                $sucesso = $resultado['mensagem'];
            } else {
                $erro = $resultado['mensagem'];
            }
            break;
            
        case 'restaurar':
            $arquivo = $_POST['arquivo'] ?? '';
            $resultado = restaurarBackup($backup_dir . $arquivo);
            if ($resultado['sucesso']) {
                $sucesso = $resultado['mensagem'];
            } else {
                $erro = $resultado['mensagem'];
            }
            break;
            
        case 'excluir':
            $arquivo = $_POST['arquivo'] ?? '';
            if (excluirBackup($backup_dir . $arquivo)) {
                $sucesso = "Backup excluído com sucesso!";
            } else {
                $erro = "Erro ao excluir backup.";
            }
            break;
            
        case 'limpeza_automatica':
            $resultado = limpezaAutomatica($backup_dir, $max_backups);
            $sucesso = $resultado['mensagem'];
            break;
    }
}

// Listar backups existentes
$backups = listarBackups($backup_dir);

/**
 * Função para criar backup completo (estrutura + dados)
 */
function criarBackupCompleto($dir) {
    global $servidor, $dbname, $usuario, $senha, $porta;
    
    $timestamp = date('Y-m-d_H-i-s');
    $arquivo = $dir . "backup_completo_{$dbname}_{$timestamp}.sql";
    
    // Comando pg_dump
    $comando = sprintf(
        'PGPASSWORD=%s pg_dump -h %s -p %d -U %s -d %s --verbose --clean --create --if-exists > %s 2>&1',
        escapeshellarg($senha),
        escapeshellarg($servidor),
        $porta,
        escapeshellarg($usuario),
        escapeshellarg($dbname),
        escapeshellarg($arquivo)
    );
    
    $output = [];
    $return_code = 0;
    exec($comando, $output, $return_code);
    
    if ($return_code === 0 && file_exists($arquivo)) {
        $tamanho = formatarTamanho(filesize($arquivo));
        return [
            'sucesso' => true,
            'mensagem' => "Backup completo criado com sucesso! Arquivo: " . basename($arquivo) . " ($tamanho)"
        ];
    } else {
        return [
            'sucesso' => false,
            'mensagem' => "Erro ao criar backup: " . implode('\n', $output)
        ];
    }
}

/**
 * Função para criar backup apenas da estrutura
 */
function criarBackupEstrutura($dir) {
    global $servidor, $dbname, $usuario, $senha, $porta;
    
    $timestamp = date('Y-m-d_H-i-s');
    $arquivo = $dir . "backup_estrutura_{$dbname}_{$timestamp}.sql";
    
    $comando = sprintf(
        'PGPASSWORD=%s pg_dump -h %s -p %d -U %s -d %s --schema-only --verbose --clean --create --if-exists > %s 2>&1',
        escapeshellarg($senha),
        escapeshellarg($servidor),
        $porta,
        escapeshellarg($usuario),
        escapeshellarg($dbname),
        escapeshellarg($arquivo)
    );
    
    $output = [];
    $return_code = 0;
    exec($comando, $output, $return_code);
    
    if ($return_code === 0 && file_exists($arquivo)) {
        $tamanho = formatarTamanho(filesize($arquivo));
        return [
            'sucesso' => true,
            'mensagem' => "Backup da estrutura criado com sucesso! Arquivo: " . basename($arquivo) . " ($tamanho)"
        ];
    } else {
        return [
            'sucesso' => false,
            'mensagem' => "Erro ao criar backup da estrutura: " . implode('\n', $output)
        ];
    }
}

/**
 * Função para criar backup apenas dos dados
 */
function criarBackupDados($dir) {
    global $servidor, $dbname, $usuario, $senha, $porta;
    
    $timestamp = date('Y-m-d_H-i-s');
    $arquivo = $dir . "backup_dados_{$dbname}_{$timestamp}.sql";
    
    $comando = sprintf(
        'PGPASSWORD=%s pg_dump -h %s -p %d -U %s -d %s --data-only --verbose > %s 2>&1',
        escapeshellarg($senha),
        escapeshellarg($servidor),
        $porta,
        escapeshellarg($usuario),
        escapeshellarg($dbname),
        escapeshellarg($arquivo)
    );
    
    $output = [];
    $return_code = 0;
    exec($comando, $output, $return_code);
    
    if ($return_code === 0 && file_exists($arquivo)) {
        $tamanho = formatarTamanho(filesize($arquivo));
        return [
            'sucesso' => true,
            'mensagem' => "Backup dos dados criado com sucesso! Arquivo: " . basename($arquivo) . " ($tamanho)"
        ];
    } else {
        return [
            'sucesso' => false,
            'mensagem' => "Erro ao criar backup dos dados: " . implode('\n', $output)
        ];
    }
}

/**
 * Função para restaurar backup
 */
function restaurarBackup($arquivo) {
    global $servidor, $dbname, $usuario, $senha, $porta;
    
    if (!file_exists($arquivo)) {
        return [
            'sucesso' => false,
            'mensagem' => "Arquivo de backup não encontrado."
        ];
    }
    
    // Confirmar se é um backup válido
    $conteudo = file_get_contents($arquivo, false, null, 0, 1000);
    if (strpos($conteudo, 'PostgreSQL database dump') === false) {
        return [
            'sucesso' => false,
            'mensagem' => "Arquivo não parece ser um backup válido do PostgreSQL."
        ];
    }
    
    $comando = sprintf(
        'PGPASSWORD=%s psql -h %s -p %d -U %s -d %s -f %s 2>&1',
        escapeshellarg($senha),
        escapeshellarg($servidor),
        $porta,
        escapeshellarg($usuario),
        escapeshellarg($dbname),
        escapeshellarg($arquivo)
    );
    
    $output = [];
    $return_code = 0;
    exec($comando, $output, $return_code);
    
    return [
        'sucesso' => true,
        'mensagem' => "Backup restaurado! Verifique se tudo está funcionando corretamente."
    ];
}

/**
 * Função para excluir backup
 */
function excluirBackup($arquivo) {
    if (file_exists($arquivo)) {
        return unlink($arquivo);
    }
    return false;
}

/**
 * Função para listar backups existentes
 */
function listarBackups($dir) {
    $backups = [];
    
    if (is_dir($dir)) {
        $arquivos = glob($dir . "backup_*.sql");
        
        foreach ($arquivos as $arquivo) {
            $info = [
                'nome' => basename($arquivo),
                'caminho' => $arquivo,
                'tamanho' => formatarTamanho(filesize($arquivo)),
                'data' => date('d/m/Y H:i:s', filemtime($arquivo)),
                'timestamp' => filemtime($arquivo)
            ];
            
            // Determinar tipo do backup pelo nome
            if (strpos($info['nome'], 'completo') !== false) {
                $info['tipo'] = 'Completo';
                $info['cor'] = '#2ecc71';
            } elseif (strpos($info['nome'], 'estrutura') !== false) {
                $info['tipo'] = 'Estrutura';
                $info['cor'] = '#3498db';
            } elseif (strpos($info['nome'], 'dados') !== false) {
                $info['tipo'] = 'Dados';
                $info['cor'] = '#f39c12';
            } else {
                $info['tipo'] = 'Desconhecido';
                $info['cor'] = '#95a5a6';
            }
            
            $backups[] = $info;
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    }
    
    return $backups;
}

/**
 * Função para limpeza automática de backups antigos
 */
function limpezaAutomatica($dir, $max_backups) {
    $backups = listarBackups($dir);
    $excluidos = 0;
    
    if (count($backups) > $max_backups) {
        $backups_para_excluir = array_slice($backups, $max_backups);
        
        foreach ($backups_para_excluir as $backup) {
            if (excluirBackup($backup['caminho'])) {
                $excluidos++;
            }
        }
    }
    
    return [
        'mensagem' => "Limpeza concluída. $excluidos backup(s) antigo(s) removido(s). Mantidos os $max_backups mais recentes."
    ];
}

/**
 * Função para formatar tamanho de arquivo
 */
function formatarTamanho($bytes) {
    if ($bytes == 0) return '0 B';
    
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $potencia = floor(log($bytes, 1024));
    
    return round($bytes / pow(1024, $potencia), 2) . ' ' . $unidades[$potencia];
}

/**
 * Função para verificar se o pg_dump está disponível
 */
function verificarPgDump() {
    $output = [];
    $return_code = 0;
    exec('pg_dump --version 2>&1', $output, $return_code);
    
    return $return_code === 0;
}

$pg_dump_disponivel = verificarPgDump();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Backup - PostgreSQL</title>
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

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-title {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 300;
        }

        .page-title p {
            color: var(--dark-color);
            opacity: 0.8;
            font-size: 1.1rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert-warning {
            background: rgba(243, 156, 18, 0.1);
            border: 1px solid var(--warning-color);
            color: var(--warning-color);
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .backup-section, .restore-section {
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

        .backup-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #138496);
            color: white;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .backups-list {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow);
            grid-column: 1 / -1;
        }

        .backup-item {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .backup-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .backup-info {
            flex: 1;
        }

        .backup-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .backup-details {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--dark-color);
            opacity: 0.7;
        }

        .backup-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }

        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            color: var(--dark-color);
            opacity: 0.6;
            padding: 2rem;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 1rem;
            color: var(--primary-color);
        }

        .status-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .status-item {
            text-align: center;
            padding: 1rem;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
        }

        .status-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .status-label {
            font-size: 0.9rem;
            color: var(--dark-color);
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .backup-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .backup-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .page-title h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                💾 Sistema de Backup
            </div>
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <span>></span>
                <span>Backup</span>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <h1>Sistema de Backup PostgreSQL</h1>
            <p>Faça backup e restaure dados do banco de dados com segurança</p>
        </div>

        <?php if (!$pg_dump_disponivel): ?>
            <div class="alert alert-warning">
                ⚠️ Aviso: pg_dump não foi encontrado no sistema. Instale o PostgreSQL client tools para usar este sistema.
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($sucesso) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <div class="status-info">
            <h3 class="section-title">📊 Status do Sistema</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-value"><?= count($backups) ?></div>
                    <div class="status-label">Backups Disponíveis</div>
                </div>
                <div class="status-item">
                    <div class="status-value"><?= $dbname ?></div>
                    <div class="status-label">Banco de Dados</div>
                </div>
                <div class="status-item">
                    <div class="status-value"><?= $max_backups ?></div>
                    <div class="status-label">Máximo Mantido</div>
                </div>
                <div class="status-item">
                    <div class="status-value"><?= is_writable($backup_dir) ? 'OK' : 'ERRO' ?></div>
                    <div class="status-label">Permissões</div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="backup-section">
                <h2 class="section-title">💾 Criar Backup</h2>
                <div class="backup-buttons">
                    <form method="POST" style="display: contents;">
                        <input type="hidden" name="acao" value="backup_completo">
                        <button type="submit" class="btn btn-success" <?= !$pg_dump_disponivel ? 'disabled' : '' ?>>
                            🗃️ Backup Completo
                            <small style="display: block; font-weight: normal; opacity: 0.8;">Estrutura + Dados</small>
                        </button>
                    </form>

                    <form method="POST" style="display: contents;">
                        <input type="hidden" name="acao" value="backup_estrutura">
                        <button type="submit" class="btn btn-primary" <?= !$pg_dump_disponivel ? 'disabled' : '' ?>>
                            🏗️ Backup Estrutura
                            <small style="display: block; font-weight: normal; opacity: 0.8;">Apenas tabelas e índices</small>
                        </button>
                    </form>

                    <form method="POST" style="display: contents;">
                        <input type="hidden" name="acao" value="backup_dados">
                        <button type="submit" class="btn btn-warning" <?= !$pg_dump_disponivel ? 'disabled' : '' ?>>
                            📋 Backup Dados
                            <small style="display: block; font-weight: normal; opacity: 0.8;">Apenas registros</small>
                        </button>
                    </form>
                </div>
            </div>

            <div class="restore-section">
                <h2 class="section-title">🔧 Manutenção</h2>
                <div class="backup-buttons">
                    <form method="POST" style="display: contents;">
                        <input type="hidden" name="acao" value="limpeza_automatica">
                        <button type="submit" class="btn btn-info">
                            🧹 Limpeza Automática
                            <small style="display: block; font-weight: normal; opacity: 0.8;">Remove backups antigos</small>
                        </button>
                    </form>

                    <a href="dashboard.php" class="btn btn-primary">
                        🏠 Voltar ao Dashboard
                    </a>

                    <button type="button" class="btn btn-warning" onclick="location.reload()">
                        🔄 Atualizar Lista
                    </button>
                </div>
            </div>
        </div>

        <div class="backups-list">
            <h2 class="section-title">📁 Backups Disponíveis</h2>
            
            <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <p>Nenhum backup encontrado. Crie seu primeiro backup usando as opções acima.</p>
                </div>
            <?php else: ?>
                <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <div class="backup-name"><?= htmlspecialchars($backup['nome']) ?></div>
                            <div class="backup-details">
                                <span class="backup-type" style="background-color: <?= $backup['cor'] ?>;">
                                    <?= $backup['tipo'] ?>
                                </span>
                                <span>📅 <?= $backup['data'] ?></span>
                                <span>💾 <?= $backup['tamanho'] ?></span>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja restaurar este backup? Esta ação pode sobrescrever dados existentes.')">
                                <input type="hidden" name="acao" value="restaurar">
                                <input type="hidden" name="arquivo" value="<?= htmlspecialchars($backup['nome']) ?>">
                                <button type="submit" class="btn btn-success btn-small">
                                    🔄 Restaurar
                                </button>
                            </form>
                            
                            <a href="<?= 'backups/' . urlencode($backup['nome']) ?>" 
                               class="btn btn-primary btn-small" 
                               download="<?= htmlspecialchars($backup['nome']) ?>">
                                💾 Download
                            </a>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este backup?')">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="arquivo" value="<?= htmlspecialchars($backup['nome']) ?>">
                                <button type="submit" class="btn btn-danger btn-small">
                                    🗑️ Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="loading" id="loading">
        <p>⏳ Processando backup... Por favor, aguarde...</p>
    </div>

    <script>
        // Mostrar loading durante operações de backup
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            const loading = document.getElementById('loading');

            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const acao = form.querySelector('input[name="acao"]');
                    
                    if (acao && ['backup_completo', 'backup_estrutura', 'backup_dados', 'restaurar'].includes(acao.value)) {
                        loading.style.display = 'block';
                        
                        // Simular progresso
                        let progress = 0;
                        const interval = setInterval(() => {
                            progress += Math.random() * 10;
                            if (progress >= 100) {
                                clearInterval(interval);
                                progress = 100;
                            }
                            
                            const loadingText = document.querySelector('#loading p');
                            if (loadingText) {
                                loadingText.innerHTML = `⏳ Processando backup... ${Math.round(progress)}%`;
                            }
                        }, 500);
                    }
                });
            });

            // Animações de entrada
            const cards = document.querySelectorAll('.backup-section, .restore-section, .backup-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Confirmar ações perigosas
        function confirmarRestauracao(arquivo) {
            return confirm(`Tem certeza que deseja restaurar o backup "${arquivo}"?\n\nEsta ação pode sobrescrever dados existentes e não pode ser desfeita.`);
        }

        function confirmarExclusao(arquivo) {
            return confirm(`Tem certeza que deseja excluir o backup "${arquivo}"?\n\nEsta ação não pode ser desfeita.`);
        }

        // Atualizar status em tempo real
        function atualizarStatus() {
            fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    console.log('Status atualizado');
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar status:', error);
            });
        }

        // Atualizar status a cada 30 segundos
        setInterval(atualizarStatus, 30000);
    </script>
</body>

</html>