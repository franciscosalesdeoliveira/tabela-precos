<?php
/**
 * Funções Avançadas de Backup para PostgreSQL
 * 
 * Este arquivo contém funções utilitárias para backup e restauração
 * Pode ser incluído em outros arquivos ou usado via linha de comando
 */

require_once 'connection.php';

/**
 * Classe para gerenciamento avançado de backups
 */
class BackupManager
{
    private $config;
    private $backup_dir;
    private $log_file;

    public function __construct()
    {
        global $servidor, $dbname, $usuario, $senha, $porta;
        
        $this->config = [
            'host' => $servidor,
            'database' => $dbname,
            'username' => $usuario,
            'password' => $senha,
            'port' => $porta
        ];

        $this->backup_dir = __DIR__ . '/backups/';
        $this->log_file = $this->backup_dir . 'backup.log';

        // Criar diretório se não existir
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }

    /**
     * Registrar log de operações
     */
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Backup automático com compressão
     */
    public function backupAutomatico($tipo = 'completo', $comprimir = true)
    {
        $this->log("Iniciando backup automático - Tipo: $tipo");

        try {
            $timestamp = date('Y-m-d_H-i-s');
            $arquivo_base = $this->backup_dir . "backup_{$tipo}_{$this->config['database']}_{$timestamp}";
            $arquivo_sql = $arquivo_base . '.sql';

            // Comando base do pg_dump
            $comando_base = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %d -U %s -d %s',
                escapeshellarg($this->config['password']),
                escapeshellarg($this->config['host']),
                $this->config['port'],
                escapeshellarg($this->config['username']),
                escapeshellarg($this->config['database'])
            );

            // Adicionar opções específicas por tipo
            switch ($tipo) {
                case 'completo':
                    $comando = $comando_base . ' --verbose --clean --create --if-exists';
                    break;
                case 'estrutura':
                    $comando = $comando_base . ' --schema-only --verbose --clean --create --if-exists';
                    break;
                case 'dados':
                    $comando = $comando_base . ' --data-only --verbose';
                    break;
                case 'custom':
                    $arquivo_sql = $arquivo_base . '.backup';
                    $comando = $comando_base . ' --format=custom --verbose --clean --create --if-exists';
                    break;
                default:
                    throw new Exception("Tipo de backup inválido: $tipo");
            }

            // Executar backup
            $comando .= ' > ' . escapeshellarg($arquivo_sql) . ' 2>&1';
            $output = [];
            $return_code = 0;
            exec($comando, $output, $return_code);

            if ($return_code !== 0) {
                throw new Exception("Erro no pg_dump: " . implode('\n', $output));
            }

            if (!file_exists($arquivo_sql)) {
                throw new Exception("Arquivo de backup não foi criado");
            }

            $tamanho_original = filesize($arquivo_sql);
            $arquivo_final = $arquivo_sql;

            // Comprimir se solicitado
            if ($comprimir && $tipo !== 'custom') {
                $arquivo_comprimido = $arquivo_sql . '.gz';
                
                if ($this->comprimirArquivo($arquivo_sql, $arquivo_comprimido)) {
                    unlink($arquivo_sql); // Remove o arquivo original
                    $arquivo_final = $arquivo_comprimido;
                    $tamanho_final = filesize($arquivo_final);
                    $taxa_compressao = round((1 - $tamanho_final / $tamanho_original) * 100, 2);
                    
                    $this->log("Backup comprimido com sucesso. Taxa de compressão: {$taxa_compressao}%");
                } else {
                    $this->log("Falha na compressão, mantendo arquivo original", 'WARNING');
                }
            }

            // Adicionar metadados
            $this->salvarMetadados($arquivo_final, [
                'tipo' => $tipo,
                'database' => $this->config['database'],
                'timestamp' => $timestamp,
                'tamanho' => filesize($arquivo_final),
                'comprimido' => $comprimir && str_ends_with($arquivo_final, '.gz'),
                'versao_pg' => $this->obterVersaoPostgreSQL()
            ]);

            $this->log("Backup concluído com sucesso: " . basename($arquivo_final));

            return [
                'sucesso' => true,
                'arquivo' => basename($arquivo_final),
                'caminho' => $arquivo_final,
                'tamanho' => filesize($arquivo_final),
                'mensagem' => "Backup $tipo criado com sucesso!"
            ];

        } catch (Exception $e) {
            $this->log("Erro no backup: " . $e->getMessage(), 'ERROR');
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

    /**
     * Comprimir arquivo usando gzip
     */
    private function comprimirArquivo($origem, $destino)
    {
        try {
            $buffer_size = 4096;
            $file_in = fopen($origem, 'rb');
            $file_out = gzopen($destino, 'wb9');

            if (!$file_in || !$file_out) {
                return false;
            }

            while (!feof($file_in)) {
                gzwrite($file_out, fread($file_in, $buffer_size));
            }

            fclose($file_in);
            gzclose($file_out);

            return true;
        } catch (Exception $e) {
            $this->log("Erro na compressão: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Descomprimir arquivo gzip
     */
    private function descomprimirArquivo($origem, $destino)
    {
        try {
            $buffer_size = 4096;
            $file_in = gzopen($origem, 'rb');
            $file_out = fopen($destino, 'wb');

            if (!$file_in || !$file_out) {
                return false;
            }

            while (!gzeof($file_in)) {
                fwrite($file_out, gzread($file_in, $buffer_size));
            }

            gzclose($file_in);
            fclose($file_out);

            return true;
        } catch (Exception $e) {
            $this->log("Erro na descompressão: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Obter versão do PostgreSQL
     */
    private function obterVersaoPostgreSQL()
    {
        try {
            global $pdo;
            $stmt = $pdo->query("SELECT version()");
            $version = $stmt->fetchColumn();
            return $version;
        } catch (Exception $e) {
            return 'Desconhecida';
        }
    }

    /**
     * Salvar metadados do backup
     */
    private function salvarMetadados($arquivo, $metadados)
    {
        $metadata_file = $arquivo . '.meta';
        $metadados['criado_em'] = date('Y-m-d H:i:s');
        
        file_put_contents($metadata_file, json_encode($metadados, JSON_PRETTY_PRINT));
    }

    /**
     * Carregar metadados do backup
     */
    public function carregarMetadados($arquivo)
    {
        $metadata_file = $arquivo . '.meta';
        
        if (file_exists($metadata_file)) {
            $content = file_get_contents($metadata_file);
            return json_decode($content, true);
        }
        
        return null;
    }

    /**
     * Restaurar backup com verificações
     */
    public function restaurarBackup($arquivo)
    {
        $this->log("Iniciando restauração do backup: " . basename($arquivo));

        try {
            if (!file_exists($arquivo)) {
                throw new Exception("Arquivo de backup não encontrado: $arquivo");
            }

            // Carregar metadados se disponíveis
            $metadados = $this->carregarMetadados($arquivo);
            
            // Verificar se é um arquivo comprimido
            $arquivo_para_restaurar = $arquivo;
            if (str_ends_with($arquivo, '.gz')) {
                $arquivo_temporario = $this->backup_dir . 'temp_restore_' . uniqid() . '.sql';
                
                if (!$this->descomprimirArquivo($arquivo, $arquivo_temporario)) {
                    throw new Exception("Falha ao descomprimir o arquivo de backup");
                }
                
                $arquivo_para_restaurar = $arquivo_temporario;
            }

            // Verificar se é um backup válido
            if (!$this->validarBackup($arquivo_para_restaurar)) {
                throw new Exception("Arquivo não é um backup válido do PostgreSQL");
            }

            // Fazer backup de segurança antes da restauração
            $backup_seguranca = $this->backupAutomatico('completo', true);
            if (!$backup_seguranca['sucesso']) {
                $this->log("Aviso: Não foi possível criar backup de segurança", 'WARNING');
            } else {
                $this->log("Backup de segurança criado: " . $backup_seguranca['arquivo']);
            }

            // Restaurar usando psql
            $comando = sprintf(
                'PGPASSWORD=%s psql -h %s -p %d -U %s -d %s -f %s 2>&1',
                escapeshellarg($this->config['password']),
                escapeshellarg($this->config['host']),
                $this->config['port'],
                escapeshellarg($this->config['username']),
                escapeshellarg($this->config['database']),
                escapeshellarg($arquivo_para_restaurar)
            );

            $output = [];
            $return_code = 0;
            exec($comando, $output, $return_code);

            // Limpar arquivo temporário se criado
            if (isset($arquivo_temporario) && file_exists($arquivo_temporario)) {
                unlink($arquivo_temporario);
            }

            // Verificar resultado
            $output_text = implode('\n', $output);
            $tem_erro_critico = strpos($output_text, 'FATAL') !== false || 
                               strpos($output_text, 'ERROR') !== false;

            if ($tem_erro_critico) {
                throw new Exception("Erros encontrados durante a restauração: $output_text");
            }

            $this->log("Restauração concluída com sucesso");

            return [
                'sucesso' => true,
                'mensagem' => 'Backup restaurado com sucesso!',
                'backup_seguranca' => $backup_seguranca['arquivo'] ?? null,
                'detalhes' => $output_text
            ];

        } catch (Exception $e) {
            $this->log("Erro na restauração: " . $e->getMessage(), 'ERROR');
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

    /**
     * Validar se arquivo é um backup válido
     */
    private function validarBackup($arquivo)
    {
        $handle = fopen($arquivo, 'r');
        if (!$handle) {
            return false;
        }

        $primeiras_linhas = '';
        for ($i = 0; $i < 20; $i++) {
            $linha = fgets($handle);
            if ($linha === false) break;
            $primeiras_linhas .= $linha;
        }
        fclose($handle);

        // Verificar se contém marcadores do PostgreSQL
        return strpos($primeiras_linhas, 'PostgreSQL database dump') !== false ||
               strpos($primeiras_linhas, 'pg_dump') !== false ||
               strpos($primeiras_linhas, 'SET statement_timeout') !== false;
    }

    /**
     * Limpeza inteligente de backups antigos
     */
    public function limpezaInteligente($configuracao = [])
    {
        $config = array_merge([
            'manter_diarios' => 7,      // Manter backups diários dos últimos 7 dias
            'manter_semanais' => 4,     // Manter 4 backups semanais
            'manter_mensais' => 3,      // Manter 3 backups mensais
            'tamanho_maximo_mb' => 1024 // Tamanho máximo total em MB
        ], $configuracao);

        $this->log("Iniciando limpeza inteligente de backups");

        try {
            $backups = $this->listarBackups(true);
            $backups_para_manter = [];
            $backups_para_excluir = [];

            // Separar por tipo e data
            $backups_por_data = [];
            foreach ($backups as $backup) {
                $data = date('Y-m-d', $backup['timestamp']);
                $backups_por_data[$data][] = $backup;
            }

            // Lógica de retenção
            $agora = time();
            $excluidos = 0;
            $tamanho_total = 0;

            foreach ($backups as $backup) {
                $idade_dias = ($agora - $backup['timestamp']) / (24 * 60 * 60);
                $manter = false;

                // Manter backups recentes
                if ($idade_dias <= $config['manter_diarios']) {
                    $manter = true;
                }
                // Manter backups semanais
                elseif ($idade_dias <= $config['manter_semanais'] * 7 && 
                        date('w', $backup['timestamp']) == 0) { // Domingos
                    $manter = true;
                }
                // Manter backups mensais
                elseif ($idade_dias <= $config['manter_mensais'] * 30 && 
                        date('j', $backup['timestamp']) == 1) { // Primeiro dia do mês
                    $manter = true;
                }

                if ($manter) {
                    $backups_para_manter[] = $backup;
                    $tamanho_total += $backup['tamanho'];
                } else {
                    $backups_para_excluir[] = $backup;
                }
            }

            // Verificar limite de tamanho
            $tamanho_limite = $config['tamanho_maximo_mb'] * 1024 * 1024;
            if ($tamanho_total > $tamanho_limite) {
                // Ordenar por data (mais antigos primeiro) e remover até atingir o limite
                usort($backups_para_manter, function($a, $b) {
                    return $a['timestamp'] - $b['timestamp'];
                });

                while ($tamanho_total > $tamanho_limite && !empty($backups_para_manter)) {
                    $backup_removido = array_shift($backups_para_manter);
                    $backups_para_excluir[] = $backup_removido;
                    $tamanho_total -= $backup_removido['tamanho'];
                }
            }

            // Executar exclusões
            foreach ($backups_para_excluir as $backup) {
                if ($this->excluirBackup($backup['caminho'])) {
                    $excluidos++;
                    $this->log("Backup excluído: " . $backup['nome']);
                }
            }

            $this->log("Limpeza concluída. $excluidos backup(s) removido(s)");

            return [
                'sucesso' => true,
                'excluidos' => $excluidos,
                'mantidos' => count($backups_para_manter),
                'tamanho_final' => $this->formatarTamanho($tamanho_total),
                'mensagem' => "Limpeza concluída. $excluidos backup(s) removido(s), " . 
                             count($backups_para_manter) . " mantido(s)."
            ];

        } catch (Exception $e) {
            $this->log("Erro na limpeza: " . $e->getMessage(), 'ERROR');
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

    /**
     * Listar backups com informações detalhadas
     */
    public function listarBackups($incluir_metadados = false)
    {
        $backups = [];
        $arquivos = glob($this->backup_dir . "backup_*.{sql,gz,backup}", GLOB_BRACE);

        foreach ($arquivos as $arquivo) {
            $info = [
                'nome' => basename($arquivo),
                'caminho' => $arquivo,
                'tamanho' => filesize($arquivo),
                'timestamp' => filemtime($arquivo),
                'data' => date('d/m/Y H:i:s', filemtime($arquivo))
            ];

            // Determinar tipo
            if (strpos($info['nome'], 'completo') !== false) {
                $info['tipo'] = 'Completo';
            } elseif (strpos($info['nome'], 'estrutura') !== false) {
                $info['tipo'] = 'Estrutura';
            } elseif (strpos($info['nome'], 'dados') !== false) {
                $info['tipo'] = 'Dados';
            } else {
                $info['tipo'] = 'Desconhecido';
            }

            // Incluir metadados se solicitado
            if ($incluir_metadados) {
                $metadados = $this->carregarMetadados($arquivo);
                if ($metadados) {
                    $info['metadados'] = $metadados;
                }
            }

            $backups[] = $info;
        }

        // Ordenar por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Excluir backup e seus metadados
     */
    public function excluirBackup($arquivo)
    {
        try {
            $excluido = false;
            
            if (file_exists($arquivo)) {
                $excluido = unlink($arquivo);
            }

            // Excluir metadados se existirem
            $metadata_file = $arquivo . '.meta';
            if (file_exists($metadata_file)) {
                unlink($metadata_file);
            }

            return $excluido;
        } catch (Exception $e) {
            $this->log("Erro ao excluir backup: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Formatar tamanho de arquivo
     */
    public function formatarTamanho($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $potencia = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $potencia), 2) . ' ' . $unidades[$potencia];
    }

    /**
     * Obter estatísticas dos backups
     */
    public function obterEstatisticas()
    {
        $backups = $this->listarBackups(true);
        
        $stats = [
            'total_backups' => count($backups),
            'tamanho_total' => 0,
            'backup_mais_recente' => null,
            'backup_mais_antigo' => null,
            'tipos' => ['Completo' => 0, 'Estrutura' => 0, 'Dados' => 0, 'Desconhecido' => 0]
        ];

        foreach ($backups as $backup) {
            $stats['tamanho_total'] += $backup['tamanho'];
            $stats['tipos'][$backup['tipo']]++;

            if (!$stats['backup_mais_recente'] || $backup['timestamp'] > $stats['backup_mais_recente']['timestamp']) {
                $stats['backup_mais_recente'] = $backup;
            }

            if (!$stats['backup_mais_antigo'] || $backup['timestamp'] < $stats['backup_mais_antigo']['timestamp']) {
                $stats['backup_mais_antigo'] = $backup;
            }
        }

        $stats['tamanho_total_formatado'] = $this->formatarTamanho($stats['tamanho_total']);
        
        return $stats;
    }

    /**
     * Verificar integridade do backup
     */
    public function verificarIntegridade($arquivo)
    {
        $this->log("Verificando integridade do backup: " . basename($arquivo));

        try {
            if (!file_exists($arquivo)) {
                return ['valido' => false, 'erro' => 'Arquivo não encontrado'];
            }

            $tamanho = filesize($arquivo);
            if ($tamanho == 0) {
                return ['valido' => false, 'erro' => 'Arquivo vazio'];
            }

            // Verificar se é arquivo comprimido
            if (str_ends_with($arquivo, '.gz')) {
                $handle = gzopen($arquivo, 'r');
                if (!$handle) {
                    return ['valido' => false, 'erro' => 'Arquivo comprimido corrompido'];
                }
                $conteudo = gzread($handle, 1000);
                gzclose($handle);
            } else {
                $conteudo = file_get_contents($arquivo, false, null, 0, 1000);
            }

            // Verificar marcadores do PostgreSQL
            $marcadores_validos = [
                'PostgreSQL database dump',
                'pg_dump',
                'SET statement_timeout'
            ];

            $valido = false;
            foreach ($marcadores_validos as $marcador) {
                if (strpos($conteudo, $marcador) !== false) {
                    $valido = true;
                    break;
                }
            }

            if (!$valido) {
                return ['valido' => false, 'erro' => 'Arquivo não é um backup válido do PostgreSQL'];
            }

            return [
                'valido' => true,
                'tamanho' => $tamanho,
                'tamanho_formatado' => $this->formatarTamanho($tamanho),
                'comprimido' => str_ends_with($arquivo, '.gz')
            ];

        } catch (Exception $e) {
            return ['valido' => false, 'erro' => $e->getMessage()];
        }
    }
}

// Uso via linha de comando
if (php_sapi_name() === 'cli') {
    $backup_manager = new BackupManager();
    
    $opcoes = getopt('t:c:h', ['tipo:', 'comprimir', 'help']);
    
    if (isset($opcoes['h']) || isset($opcoes['help'])) {
        echo "Uso: php backup_functions.php [opções]\n";
        echo "Opções:\n";
        echo "  -t, --tipo TYPE     Tipo de backup (completo|estrutura|dados|custom)\n";
        echo "  -c, --comprimir     Comprimir backup\n";
        echo "  -h, --help          Mostrar esta ajuda\n";
        exit(0);
    }
    
    $tipo = $opcoes['t'] ?? $opcoes['tipo'] ?? 'completo';
    $comprimir = isset($opcoes['c']) || isset($opcoes['comprimir']);
    
    echo "Executando backup $tipo...\n";
    $resultado = $backup_manager->backupAutomatico($tipo, $comprimir);
    
    if ($resultado['sucesso']) {
        echo "✅ " . $resultado['mensagem'] . "\n";
        echo "Arquivo: " . $resultado['arquivo'] . "\n";
        echo "Tamanho: " . $backup_manager->formatarTamanho($resultado['tamanho']) . "\n";
    } else {
        echo "❌ Erro: " . $resultado['mensagem'] . "\n";
        exit(1);
    }
}
?>