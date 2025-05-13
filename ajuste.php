<?php
$titulo = "Cadastro de Grupos";
include_once 'connection.php';
include_once 'header.php';
include_once 'functions.php'; // Arquivo sugerido para funções auxiliares

// Inicializar variáveis para mensagens de feedback
$mensagem = '';
$tipo_mensagem = '';

// Geração de token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro de validação do formulário.";
        $tipo_mensagem = "danger";
    } else {
        // Validação de entrada
        $grupo = trim($_POST['grupo']);

        if (empty($grupo)) {
            $mensagem = "O nome do grupo não pode estar vazio.";
            $tipo_mensagem = "danger";
        } else {
            try {
                // Modificado para incluir status como ativo por padrão
                $sql = "INSERT INTO grupos (nome, status) VALUES (:grupo, 'ativo')";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':grupo', $grupo);
                $stmt->execute();

                // Gerar nova mensagem e token após submissão bem-sucedida
                $mensagem = "Grupo cadastrado com sucesso!";
                $tipo_mensagem = "success";
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (PDOException $e) {
                $mensagem = "Erro ao cadastrar: " . filter_var($e->getMessage(), FILTER_SANITIZE_SPECIAL_CHARS);
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Configurações de paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Parâmetros de ordenação
$ordem_coluna = isset($_GET['ordem']) ? $_GET['ordem'] : 'id';
$ordem_direcao = isset($_GET['direcao']) ? $_GET['direcao'] : 'ASC';

// Lista de colunas permitidas para ordenação
$colunas_permitidas = ['id', 'nome'];
if (!in_array($ordem_coluna, $colunas_permitidas)) {
    $ordem_coluna = 'id';
}

// Direções permitidas
if (!in_array($ordem_direcao, ['ASC', 'DESC'])) {
    $ordem_direcao = 'ASC';
}

// Modificar consulta para filtrar apenas grupos ativos
// Consulta para contagem total
$sql_count = "SELECT COUNT(*) FROM grupos WHERE status = 'ativo'";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Pesquisa
if (!empty($_GET['search'])) {
    $data = "%" . $_GET['search'] . "%";
    $sql = "SELECT * FROM grupos 
            WHERE status = 'ativo' AND 
            (unaccent(nome) ILIKE unaccent(:data)
            OR CAST(id AS TEXT) ILIKE :data)
            ORDER BY $ordem_coluna $ordem_direcao
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':data', $data, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    // Modificar consulta para filtrar apenas grupos ativos
    $sql = "SELECT * FROM grupos 
            WHERE status = 'ativo' 
            ORDER BY $ordem_coluna $ordem_direcao 
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resto do código permanece o mesmo...
// (Funções auxiliares, HTML, JavaScript)
// Mantenha o restante do código original do arquivo

// Adicionar uma opção no formulário para marcar grupo como inativo (opcional)
// No formulário de cadastro, você pode adicionar:
?>
    <!-- No formulário de cadastro, adicionar (opcional) -->
    <div class="col-md-4">
        <label class="form-label" style="font-weight: bold; color: black;">Status:</label>
        <select class="form-control" name="status">
            <option value="ativo" selected>Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
    </div>
<?php
// Na lógica de inserção, você pode modificar o INSERT para incluir o status
// $sql = "INSERT INTO grupos (nome, status) VALUES (:grupo, :status)";
// $stmt->bindValue(':status', $_POST['status']);
?>