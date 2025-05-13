<?php
// Inclusão de arquivos necessários
include_once 'connection.php';
include_once 'functions.php'; // Se existir

// Verificação de segurança básica
session_start();
if (!isset($_SESSION['logado']) && $_SESSION['logado'] !== true) {
    // Se quiser implementar verificação de login, descomente
    // header('Location: login.php');
    // exit;
}

// Inicializar variáveis
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// Verificar se ID e ação são válidos
if ($id <= 0 || !in_array($acao, ['ativar', 'inativar'])) {
    $_SESSION['mensagem'] = "Parâmetros inválidos.";
    $_SESSION['tipo_mensagem'] = "danger";
    header('Location: cadastro_grupos.php');
    exit;
}

try {
    // Determinar o novo valor de status baseado na ação
    $novo_status = ($acao === 'ativar') ? 'true' : 'false';
    
    // Verificar se o grupo existe
    $stmt_check = $pdo->prepare("SELECT id FROM grupos WHERE id = :id");
    $stmt_check->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() === 0) {
        throw new Exception("Grupo não encontrado.");
    }
    
    // Atualizar o status
    $sql = "UPDATE grupos SET ativo = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':status', $novo_status, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Executar um comando adicional para garantir consistência
    if ($novo_status === 'true') {
        // Tentar atualizar para boolean true também
        $stmt_extra = $pdo->prepare("UPDATE grupos SET ativo = true WHERE id = :id");
        $stmt_extra->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt_extra->execute();
    } else {
        // Tentar atualizar para boolean false também
        $stmt_extra = $pdo->prepare("UPDATE grupos SET ativo = false WHERE id = :id");
        $stmt_extra->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt_extra->execute();
    }
    
    // Verificar se a atualização foi bem-sucedida
    if ($stmt->rowCount() > 0) {
        $mensagem = ($acao === 'ativar') ? "Grupo ativado com sucesso!" : "Grupo inativado com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Nenhuma alteração realizada.";
        $tipo_mensagem = "warning";
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao " . $acao . " o grupo: " . filter_var($e->getMessage(), FILTER_SANITIZE_SPECIAL_CHARS);
    $tipo_mensagem = "danger";
}

// Armazenar mensagem em sessão para exibição na próxima página
$_SESSION['mensagem'] = $mensagem;
$_SESSION['tipo_mensagem'] = $tipo_mensagem;

// Registrar log da operação (opcional)
// logOperacao("Alteração de status do grupo ID: $id para " . ($novo_status === 'true' ? 'Ativo' : 'Inativo'));

// Redirecionar de volta para a página de listagem
header('Location: cadastro_grupos.php');
exit;