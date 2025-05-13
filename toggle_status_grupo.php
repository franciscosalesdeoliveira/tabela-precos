<?php
// Inclusão de arquivos necessários
include_once 'connection.php';
include_once 'functions.php'; // Se existir

// Verificação de segurança básica
session_start();

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
    // Primeiro, vamos verificar o status atual do grupo
    $stmt_check = $pdo->prepare("SELECT ativo FROM grupos WHERE id = :id");
    $stmt_check->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() === 0) {
        throw new Exception("Grupo não encontrado.");
    }
    
    $grupo = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $status_atual = $grupo['ativo'];
    
    // Converter para um valor booleano padronizado
    $is_ativo = false;
    if ($status_atual === true || $status_atual === 't' || $status_atual === 'true' || $status_atual === '1' || $status_atual === 1) {
        $is_ativo = true;
    }
    
    // Determinar qual deve ser o novo status
    $novo_status = ($acao === 'ativar');
    
    // Verificar se já está no estado desejado
    if ($is_ativo === $novo_status) {
        $mensagem = ($is_ativo) ? 
            "O grupo já está ativo." : 
            "O grupo já está inativo.";
        $tipo_mensagem = "warning";
    } else {
        // Atualizar para o novo status
        $sql = "UPDATE grupos SET ativo = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', $novo_status ? 'true' : 'false', PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Mensagem de sucesso baseada na ação realizada
        $mensagem = ($novo_status) ? 
            "Grupo ativado com sucesso!" : 
            "Grupo inativado com sucesso!";
        $tipo_mensagem = "success";
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao processar a operação: " . filter_var($e->getMessage(), FILTER_SANITIZE_SPECIAL_CHARS);
    $tipo_mensagem = "danger";
}

// Armazenar mensagem em sessão para exibição na próxima página
$_SESSION['mensagem'] = $mensagem;
$_SESSION['tipo_mensagem'] = $tipo_mensagem;

// Redirecionar de volta para a página de listagem
header('Location: cadastro_grupos.php');
exit;