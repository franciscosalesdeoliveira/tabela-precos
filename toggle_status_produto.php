<?php
require_once 'connection.php';

// Verifica se recebeu um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['msg_error'] = "ID do produto não fornecido!";
    header("Location: cadastro_produtos.php");
    exit;
}

$id = (int)$_GET['id'];
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'toggle';

// Verifica se o produto existe
$query = "SELECT id, nome, ativo FROM produtos WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    $_SESSION['msg_error'] = "Produto não encontrado!";
    header("Location: cadastro_produtos.php");
    exit;
}

// Define o novo status conforme a ação solicitada
$novoStatus = 0; // inativar por padrão
if ($acao === 'ativar') {
    $novoStatus = 1;
} elseif ($acao === 'inativar') {
    $novoStatus = 0;
} else {
    // Modo toggle (inverte o status atual)
    $novoStatus = isset($produto['ativo']) && $produto['ativo'] ? 0 : 1;
}

// Atualiza o status do produto
$query = "UPDATE produtos SET ativo = :ativo WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':ativo', $novoStatus, PDO::PARAM_BOOL);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);

try {
    $stmt->execute();

    $statusTexto = $novoStatus ? "ativado" : "inativado";
    $_SESSION['msg_success'] = "Produto '{$produto['nome']}' {$statusTexto} com sucesso!";
} catch (PDOException $e) {
    $_SESSION['msg_error'] = "Erro ao alterar status do produto: " . $e->getMessage();
}

// Redireciona de volta para a página de produtos
header("Location: cadastro_produtos.php");
exit;
