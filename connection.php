<?php

/**
 * Conexão com o banco de dados PostgreSQL
 * Versão otimizada combinando as melhores práticas
 */

// Iniciar sessão se ainda não estiver iniciada


// Carregar configurações de arquivo .env se disponível
if (file_exists(__DIR__ . '/.env.php')) {
    include_once __DIR__ . '/.env.php';
}

// Configurações do banco de dados
// Use variáveis de ambiente ou valores padrão para desenvolvimento
$servidor = defined('DB_HOST') ? DB_HOST : "localhost";
$dbname = defined('DB_NAME') ? DB_NAME : "tprecos";
$usuario = defined('DB_USER') ? DB_USER : "postgres";
$senha = defined('DB_PASS') ? DB_PASS : "admin";
$porta = defined('DB_PORT') ? DB_PORT : 5432;

// Opções avançadas para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lança exceções em caso de erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Retorna arrays associativos por padrão
    PDO::ATTR_EMULATE_PREPARES   => false,                    // Usa prepared statements nativos
    PDO::ATTR_PERSISTENT         => true,                     // Usa conexões persistentes para melhor performance
    PDO::ATTR_CASE               => PDO::CASE_NATURAL,        // Mantém o nome das colunas como estão no banco
];

// String de conexão DSN (Data Source Name)
$dsn = "pgsql:host=$servidor;port=$porta;dbname=$dbname";

try {
    // Conexão com o banco de dados PostgreSQL
    $pdo = new PDO($dsn, $usuario, $senha, $options);

    // Configurações do PostgreSQL
    $pdo->exec("SET search_path TO public");

    // Configuração para trabalhar com caracteres UTF-8
    $pdo->exec("SET NAMES 'utf8'");

    // Configuração de timezone para garantir consistência de datas
    $pdo->exec("SET timezone = 'America/Sao_Paulo'");

    // Defina uma função para limpar a conexão quando não for mais necessária
    function closeConnection()
    {
        global $pdo;
        $pdo = null;
    }

    // Registre a função para fechar a conexão no final do script
    register_shutdown_function('closeConnection');
} catch (PDOException $e) {
    // Log do erro em arquivo para não expor detalhes sensíveis
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage(), 0);

    // Em produção, mostrar uma mensagem genérica
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        die("Erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.");
    } else {
        // Em desenvolvimento, mostrar o erro completo
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}

/**
 * Função para obter uma nova conexão PDO
 * Útil quando precisamos garantir uma nova conexão
 * 
 * @return PDO Nova instância de conexão PDO ou null em caso de erro
 */
function getNewConnection()
{
    global $dsn, $usuario, $senha, $options;

    try {
        return new PDO($dsn, $usuario, $senha, $options);
    } catch (PDOException $e) {
        error_log("Erro ao criar nova conexão: " . $e->getMessage());
        throw new Exception("Falha ao criar nova conexão PDO.");
    }
}

/**
 * Função para verificar se a conexão com o banco está ativa
 * 
 * @param PDO $connection Conexão a ser testada
 * @return bool True se a conexão estiver ativa, false caso contrário
 */
function isConnectionAlive($connection)
{
    try {
        $connection->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
