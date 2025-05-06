<?php

/**
 * Arquivo de funções auxiliares para o sistema
 */

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitiza strings para evitar XSS
 * 
 * @param string $data String a ser sanitizada
 * @return string String sanitizada
 */
function sanitizeOutput($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida se um valor é um inteiro positivo
 * 
 * @param mixed $value Valor a ser validado
 * @return bool True se for um inteiro positivo, false caso contrário
 */
function isPositiveInteger($value)
{
    return is_numeric($value) && $value > 0 && ctype_digit((string)$value);
}

/**
 * Exibe mensagens de alerta formatadas
 * 
 * @param string $mensagem Texto da mensagem
 * @param string $tipo Tipo de alerta (success, danger, warning, info)
 * @return string HTML da mensagem formatada
 */
function displayAlert($mensagem, $tipo = 'info')
{
    if (empty($mensagem)) return '';

    return '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">
                ' . sanitizeOutput($mensagem) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>';
}

/**
 * Verifica e exibe mensagens armazenadas na sessão
 * 
 * @return string HTML da mensagem formatada ou string vazia
 */
function checkSessionMessages()
{
    $output = '';

    if (isset($_SESSION['mensagem']) && !empty($_SESSION['mensagem'])) {
        $tipo = isset($_SESSION['tipo_mensagem']) ? $_SESSION['tipo_mensagem'] : 'info';
        $output = displayAlert($_SESSION['mensagem'], $tipo);

        // Limpar mensagens da sessão após exibição
        unset($_SESSION['mensagem']);
        unset($_SESSION['tipo_mensagem']);
    }

    return $output;
}

/**
 * Gera token CSRF e o armazena na sessão
 * 
 * @return string Token CSRF gerado
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se o token CSRF é válido
 * 
 * @param string $token Token a ser verificado
 * @return bool True se o token for válido, false caso contrário
 */
function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Redireciona com mensagem
 * 
 * @param string $url URL para redirecionamento
 * @param string $mensagem Mensagem a ser exibida
 * @param string $tipo Tipo de mensagem (success, danger, warning, info)
 */
function redirectWithMessage($url, $mensagem, $tipo = 'info')
{
    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['tipo_mensagem'] = $tipo;
    header("Location: $url");
    exit;
}

/**
 * Verifica se o usuário tem permissão para acessar a página
 * Versão básica - implementar lógica de controle de acesso real
 * 
 * @param string $permissao Permissão necessária
 * @return bool True se tiver permissão, false caso contrário
 */
function userCanAccess($permissao)
{
    // Implementar verificação de permissões reais do usuário
    // Por enquanto, retorna true para não bloquear acesso
    return true;
}

/**
 * Registra logs de atividades no sistema
 * 
 * @param string $acao Ação realizada
 * @param string $tabela Tabela afetada
 * @param int $id_registro ID do registro afetado
 * @param string $detalhes Detalhes adicionais (opcional)
 * @return bool Sucesso ou falha ao registrar log
 */
function registrarLog($acao, $tabela, $id_registro, $detalhes = '')
{
    global $pdo;

    try {
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
        $ip = $_SERVER['REMOTE_ADDR'];

        $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, ip, detalhes, data_hora) 
                VALUES (:usuario_id, :acao, :tabela, :registro_id, :ip, :detalhes, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':acao', $acao, PDO::PARAM_STR);
        $stmt->bindParam(':tabela', $tabela, PDO::PARAM_STR);
        $stmt->bindParam(':registro_id', $id_registro, PDO::PARAM_INT);
        $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindParam(':detalhes', $detalhes, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (PDOException $e) {
        // Em caso de erro, registra em arquivo de log
        error_log("Erro ao registrar log: " . $e->getMessage());
        return false;
    }
}

/**
 * Formata a data para o formato brasileiro
 * 
 * @param string $data Data no formato YYYY-MM-DD
 * @return string Data formatada (DD/MM/YYYY)
 */
function formatarData($data)
{
    if (empty($data)) return '';

    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

/**
 * Valida se um nome de grupo já existe no banco de dados
 * 
 * @param string $nome Nome do grupo a verificar
 * @param int $id_excluir ID a ser excluído da verificação (para edição)
 * @return bool True se o nome já existe, false caso contrário
 */
function grupoExiste($nome, $id_excluir = 0)
{
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) FROM grupos WHERE nome = :nome";

        if ($id_excluir > 0) {
            $sql .= " AND id != :id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);

        if ($id_excluir > 0) {
            $stmt->bindParam(':id', $id_excluir, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao verificar existência de grupo: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se um grupo possui produtos associados
 * 
 * @param int $grupo_id ID do grupo
 * @return int Número de produtos associados ao grupo
 */
function contarProdutosPorGrupo($grupo_id)
{
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) FROM produtos WHERE grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':grupo_id', $grupo_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao contar produtos por grupo: " . $e->getMessage());
        return 0;
    }
}

/**
 * Gera uma opção para select com os grupos disponíveis
 * 
 * @param int $selected_id ID do grupo selecionado (opcional)
 * @return string HTML com as opções do select
 */
function getGruposOptions($selected_id = null)
{
    global $pdo;

    try {
        $sql = "SELECT id, nome FROM grupos ORDER BY nome ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $options = '';
        foreach ($grupos as $grupo) {
            $selected = ($selected_id == $grupo['id']) ? 'selected' : '';
            $options .= '<option value="' . $grupo['id'] . '" ' . $selected . '>' .
                sanitizeOutput($grupo['nome']) . '</option>';
        }

        return $options;
    } catch (PDOException $e) {
        error_log("Erro ao gerar opções de grupos: " . $e->getMessage());
        return '<option value="">Erro ao carregar grupos</option>';
    }
}
