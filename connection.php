<?php
$servidor = "localhost";
$dbname = "tprecos";
$usuario = "postgres";
$senha = "admin";


//conexao com o banco de dados
try {
    //sgbd:host; port;dbname; usuario; senha; errmode
    // Conexão com o banco de dados PostgreSQL
    $pdo = new PDO("pgsql:host=$servidor; port=5432; dbname=$dbname", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //echo "Conexão bem-sucedida ao banco de dados PostgreSQL.";
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco de dados.";
    die($e->getMessage());
}
