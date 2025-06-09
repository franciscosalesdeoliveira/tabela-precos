<?php
// auth_check.php - Arquivo para verificar autenticação

// Função para verificar se o usuário está logado
function verificarAutenticacao($redirecionarSeNaoLogado = true)
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        if ($redirecionarSeNaoLogado) {
            // Redirecionar para login se não estiver autenticado
            header("Location: login.php");
            exit;
        }
        return false;
    }

    // Verificar se a sessão não expirou (opcional - 8 horas)
    $tempoExpiracaoSessao = 8 * 60 * 60; // 8 horas em segundos
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $tempoExpiracaoSessao) {
        // Sessão expirada
        destruirSessao();
        if ($redirecionarSeNaoLogado) {
            header("Location: login.php?expired=1");
            exit;
        }
        return false;
    }

    return true;
}

// Função para obter dados do usuário logado
function obterUsuarioLogado()
{
    if (!verificarAutenticacao(false)) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'uuid' => $_SESSION['user_uuid'] ?? null,
        'nome' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'empresa_id' => $_SESSION['empresa_id'] ?? null,
        'empresa_nome' => $_SESSION['empresa_nome'] ?? '',
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

// Função para destruir sessão
function destruirSessao()
{
    // Limpar todas as variáveis de sessão
    $_SESSION = array();

    // Destruir cookie de sessão se existir
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruir a sessão
    session_destroy();
}

// Função para fazer logout
function fazerLogout()
{
    destruirSessao();
    header("Location: login.php?logout=1");
    exit;
}

// Verificar se é uma solicitação de logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    fazerLogout();
}

// Função para verificar permissões (expandível no futuro)
function verificarPermissao($permissao)
{
    // Por enquanto, todos os usuários autenticados têm todas as permissões
    // Esta função pode ser expandida para incluir níveis de acesso
    return verificarAutenticacao(false);
}

// Função para atualizar último acesso
function atualizarUltimoAcesso()
{
    if (!verificarAutenticacao(false)) {
        return false;
    }

    try {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao atualizar último acesso: " . $e->getMessage());
        return false;
    }
}
