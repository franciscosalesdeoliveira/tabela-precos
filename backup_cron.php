<?php

/**
 * Script para execução de backups automatizados via cron
 * 
 * Uso:
 * php backup_cron.php database [config_name]
 * php backup_cron.php arquivos [config_name] [pasta_origem] [pasta_destino]
 */

require_once 'connection.php';

// Configurações padrão
$CONFIG_PADRAO = 'gdrive-backup'; // Nome da configuração padrão do rclone
$PASTA_DESTINO_DB = 'backups/database';
$PASTA_DESTINO_ARQUIVOS = 'backups/sistema';
$PASTA_ORIGEM_PADRAO = '/var/www/html';

// Padrões de exclusão padrão
$PADROES_EXCLUSAO = [
    '*.log',
    '*.tmp',
    'cache/',
    'temp/',
    'node_modules/',
    '.git/',
    '.svn/',
    'vendor/',
    '*.bak',
    'thumbs.db',
    '.DS_Store',
    'logs/',
    'tmp/'
];

/**
 * Registra log de backup
 */
function registrarLog($tipo, $status, $detalhes = '', $tamanho = '')
{
    global $pdo;

    try {
        // Criar tabela de logs se não existir
        $sql = "CREATE TABLE IF NOT EXISTS backup_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_backup DATETIME DEFAULT CURRENT_TIMESTAMP,
            tipo VARCHAR(50) NOT NULL,
            status ENUM('sucesso', 'erro', 'em_andamento') NOT NULL,
            detalhes TEXT,
            tamanho VARCHAR(20),
            INDEX idx_data (data_backup),
            INDEX idx_tipo (tipo)
        )";
        $pdo->exec($sql);

        $stmt = $pdo->prepare("INSERT INTO backup_logs (tipo, status, detalhes, tamanho) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tipo, $status, $detalhes, $tamanho]);

        echo "[" . date('Y-m-d H:i:s') . "] Log registrado: $tipo - $status\n";
    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Erro ao registrar log: " . $e->getMessage() . "\n";
    }
}

/**
 * Executa backup do banco de dados
 */
function backupDatabase($config, $pasta_destino)
{
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando backup do banco de dados...\n";

    registrarLog('database', 'em_andamento', 'Iniciando backup do banco de dados');

    try {
        $timestamp = date('Y-m-d_H-i-s');
        $arquivo_backup = "backup_database_$timestamp.sql";
        $arquivo_compactado = "backup_database_$timestamp.sql.gz";

        // Dump do MySQL
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $database = DB_NAME;

        echo "[" . date('Y-m-d H:i:s') . "] Criando dump do banco '$database'...\n";

        // Comando para dump com compactação
        $comando_dump = "mysqldump -h$host -u$user -p'$pass' --single-transaction --routines --triggers '$database' | gzip > /tmp/$arquivo_compactado 2>&1";
        $resultado_dump = shell_exec($comando_dump);

        if (!file_exists("/tmp/$arquivo_compactado")) {
            throw new Exception("Falha ao criar backup do banco: $resultado_dump");
        }

        $tamanho = formatarTamanho(filesize("/tmp/$arquivo_compactado"));
        echo "[" . date('Y-m-d H:i:s') . "] Backup criado com sucesso ($tamanho)\n";

        // Enviar para Google Drive
        echo "[" . date('Y-m-d H:i:s') . "] Enviando para Google Drive...\n";
        $destino = $config . ($pasta_destino ? ":$pasta_destino" : ":");
        $comando_upload = "rclone copy /tmp/$arquivo_compactado $destino --progress 2>&1";
        $resultado_upload = shell_exec($comando_upload);

        // Limpar arquivo local
        unlink("/tmp/$arquivo_compactado");

        if (strpos($resultado_upload, 'error') !== false || strpos($resultado_upload, 'ERROR') !== false) {
            throw new Exception("Erro no upload: $resultado_upload");
        }

        echo "[" . date('Y-m-d H:i:s') . "] Backup enviado com sucesso para $destino\n";
        registrarLog('database', 'sucesso', "Backup enviado para $destino", $tamanho);

        return true;
    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
        registrarLog('database', 'erro', $e->getMessage());
        return false;
    }
}

/**
 * Executa backup de arquivos
 */
function backupArquivos($config, $pasta_origem, $pasta_destino, $padroes_exclusao)
{
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando backup de arquivos...\n";
    echo "[" . date('Y-m-d H:i:s') . "] Origem: $pasta_origem\n";
    echo "[" . date('Y-m-d H:i:s') . "] Destino: $config:$pasta_destino\n";

    registrarLog('arquivos', 'em_andamento', "Sincronizando $pasta_origem para $config:$pasta_destino");

    try {
        if (!is_dir($pasta_origem)) {
            throw new Exception("Pasta de origem não existe: $pasta_origem");
        }

        $destino = $config . ($pasta_destino ? ":$pasta_destino" : ":");

        // Montar comando com exclusões
        $comando = "rclone sync '$pasta_origem' '$destino' --progress --stats 1m";

        // Adicionar padrões de exclusão
        foreach ($padroes_exclusao as $padrao) {
            $comando .= " --exclude '$padrao'";
        }

        // Adicionar opções de segurança e performance
        $comando .= " --checksum --transfers 4 --checkers 8 --contimeout 60s --timeout 300s --retries 3";
        $comando .= " 2>&1";

        echo "[" . date('Y-m-d H:i:s') . "] Executando sincronização...\n";
        echo "[" . date('Y-m-d H:i:s') . "] Comando: $comando\n";

        $resultado = shell_exec($comando);

        if (strpos($resultado, 'error') !== false || strpos($resultado, 'ERROR') !== false || strpos($resultado, 'NOTICE') !== false) {
            // Verificar se são apenas avisos ou erros críticos
            if (strpos($resultado, 'Failed to') !== false || strpos($resultado, 'couldn\'t') !== false) {
                throw new Exception("Erro na sincronização: $resultado");
            }
        }

        // Extrair estatísticas do resultado
        $tamanho = extrairTamanhoDoResultado($resultado);

        echo "[" . date('Y-m-d H:i:s') . "] Backup de arquivos concluído com successo\n";
        if ($tamanho) {
            echo "[" . date('Y-m-d H:i:s') . "] Dados transferidos: $tamanho\n";
        }

        registrarLog('arquivos', 'sucesso', "Sincronização concluída: $pasta_origem -> $destino", $tamanho);

        return true;
    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
        registrarLog('arquivos', 'erro', $e->getMessage());
        return false;
    }
}

/**
 * Formatar tamanho de arquivo
 */
function formatarTamanho($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Extrair tamanho dos dados do resultado do rclone
 */
function extrairTamanhoDoResultado($resultado)
{
    // Procurar por padrões como "Transferred: 123.45 MB"
    if (preg_match('/Transferred:\s+([0-9.,]+\s*[KMGT]?B)/i', $resultado, $matches)) {
        return $matches[1];
    }

    // Procurar por outros padrões
    if (preg_match('/([0-9.,]+\s*[KMGT]?B)\s+transferred/i', $resultado, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Verificar se o rclone está instalado
 */
function verificarRclone()
{
    $output = shell_exec('rclone version 2>&1');
    return strpos($output, 'rclone') !== false;
}

/**
 * Verificar se a configuração existe
 */
function verificarConfiguracao($config)
{
    $output = shell_exec('rclone listremotes 2>&1');
    if ($output) {
        $configuracoes = array_filter(explode("\n", trim($output)));
        foreach ($configuracoes as $configuracao) {
            if (str_replace(':', '', $configuracao) === $config) {
                return true;
            }
        }
    }
    return false;
}

// Verificar argumentos
if ($argc < 2) {
    echo "Uso:\n";
    echo "  php backup_cron.php database [config_name] [pasta_destino]\n";
    echo "  php backup_cron.php arquivos [config_name] [pasta_origem] [pasta_destino]\n";
    echo "\nExemplos:\n";
    echo "  php backup_cron.php database\n";
    echo "  php backup_cron.php database gdrive-backup backups/db\n";
    echo "  php backup_cron.php arquivos gdrive-backup /var/www/html backups/sistema\n";
    exit(1);
}

$tipo = $argv[1];

// Verificar se rclone está instalado
if (!verificarRclone()) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: rclone não está instalado\n";
    registrarLog($tipo, 'erro', 'rclone não está instalado');
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Iniciando backup automatizado - Tipo: $tipo\n";

switch ($tipo) {
    case 'database':
        $config = $argv[2] ?? $CONFIG_PADRAO;
        $pasta_destino = $argv[3] ?? $PASTA_DESTINO_DB;

        if (!verificarConfiguracao($config)) {
            echo "[" . date('Y-m-d H:i:s') . "] ERRO: Configuração '$config' não encontrada\n";
            registrarLog('database', 'erro', "Configuração '$config' não encontrada");
            exit(1);
        }

        $sucesso = backupDatabase($config, $pasta_destino);
        exit($sucesso ? 0 : 1);

    case 'arquivos':
        $config = $argv[2] ?? $CONFIG_PADRAO;
        $pasta_origem = $argv[3] ?? $PASTA_ORIGEM_PADRAO;
        $pasta_destino = $argv[4] ?? $PASTA_DESTINO_ARQUIVOS;

        if (!verificarConfiguracao($config)) {
            echo "[" . date('Y-m-d H:i:s') . "] ERRO: Configuração '$config' não encontrada\n";
            registrarLog('arquivos', 'erro', "Configuração '$config' não encontrada");
            exit(1);
        }

        $sucesso = backupArquivos($config, $pasta_origem, $pasta_destino, $PADROES_EXCLUSAO);
        exit($sucesso ? 0 : 1);

    default:
        echo "[" . date('Y-m-d H:i:s') . "] ERRO: Tipo de backup inválido: $tipo\n";
        echo "Tipos válidos: database, arquivos\n";
        exit(1);
}
