<?php
require_once 'connection.php';

try {
    echo "✅ Conexão com PostgreSQL bem-sucedida!<br><br>";

    // Mostrar o objeto PDO (opcional)
    echo "<strong>Objeto PDO:</strong><br>";
    var_dump($pdo);
    echo "<br><br>";

    // Consulta simples para testar
    $stmt = $pdo->query("SELECT version()");
    $versao = $stmt->fetchColumn();

    echo "<strong>Versão do PostgreSQL:</strong><br>";
    echo $versao;
} catch (PDOException $e) {
    echo "❌ Erro ao conectar ou executar consulta: " . $e->getMessage();
}
