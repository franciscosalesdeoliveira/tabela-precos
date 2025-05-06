<?php
/**
 * Conexão com o banco de dados PostgreSQL
 * Versão melhorada com configurações em arquivo separado
 */

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

// Opções para PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

//conexao com o banco de dados
try {
    // Conexão com o banco de dados PostgreSQL
    $pdo = new PDO("pgsql:host=$servidor;port=$porta;dbname=$dbname", $usuario, $senha, $options);
    
    // Configurar o PostgreSQL para usar funções específicas
    $pdo->exec("SET search_path TO public");
    
    // Defina uma função para limpar a conexão quando não for mais necessária
    function closeConnection() {
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
 * Exemplo de arquivo .env.php (criar na raiz do projeto):
 * 
 * <?php
 * define('ENVIRONMENT', 'development'); // development ou production
 * define('DB_HOST', 'localhost');
 * define('DB_NAME', 'tprecos');
 * define('DB_USER', 'seu_usuario');
 * define('DB_PASS', 'sua_senha');
 * define('DB_PORT', 5432);
 */