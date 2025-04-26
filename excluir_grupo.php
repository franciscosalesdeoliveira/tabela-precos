<?php
$titulo = "Excluir Grupo";
require_once "connection.php";
require_once "header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID não informado.";
    exit;
}

$id = (int)$_GET['id'];

$sql = "DELETE FROM grupos WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(":id", $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    header("Location: cadastro_grupos.php");
    exit;
} else {
    echo "Erro ao excluir o grupo.";
}
