<?php
$titulo = "Cadastro de Grupos";
include_once 'connection.php';
include_once 'header.php';
include_once 'functions.php'; // Arquivo sugerido para funções auxiliares

// Inicializar variáveis para mensagens de feedback
$mensagem = '';
$tipo_mensagem = '';

// Verificar se há mensagens na sessão
if (isset($_SESSION['mensagem']) && isset($_SESSION['tipo_mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = $_SESSION['tipo_mensagem'];
    // Limpar mensagens da sessão após uso
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}

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
        $status = $_POST['status'] === 'ativo' ? 'true' : 'false';

        if (empty($grupo)) {
            $mensagem = "O nome do grupo não pode estar vazio.";
            $tipo_mensagem = "danger";
        } else {
            try {
                $sql = "INSERT INTO grupos (nome, ativo) VALUES (:grupo, :status)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':grupo', $grupo);
                $stmt->bindValue(':status', $status);
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

// Configurar filtro de status
$filtro_status = isset($_GET['status']) ? $_GET['status'] : 'todos';
$where_status = '';

if ($filtro_status === 'ativos') {
    $where_status = "WHERE ativo = 'true' OR ativo = true OR ativo = 't'";
} elseif ($filtro_status === 'inativos') {
    $where_status = "WHERE ativo = 'false' OR ativo = false OR ativo = 'f'";
}

// Consulta para contagem total com filtro de status
$sql_count = "SELECT COUNT(*) FROM grupos $where_status";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Pesquisa com filtro de status
if (!empty($_GET['search'])) {
    $data = "%" . $_GET['search'] . "%";
    
    // Adicionar condição WHERE se necessário
    if (!empty($where_status)) {
        $sql = "SELECT * FROM grupos 
                $where_status AND 
                (unaccent(nome) ILIKE unaccent(:data)
                OR CAST(id AS TEXT) ILIKE :data)
                ORDER BY $ordem_coluna $ordem_direcao
                LIMIT :limit OFFSET :offset";
    } else {
        $sql = "SELECT * FROM grupos 
                WHERE (unaccent(nome) ILIKE unaccent(:data)
                OR CAST(id AS TEXT) ILIKE :data)
                ORDER BY $ordem_coluna $ordem_direcao
                LIMIT :limit OFFSET :offset";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':data', $data, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    // Consulta sem busca
    $sql = "SELECT * FROM grupos 
            $where_status 
            ORDER BY $ordem_coluna $ordem_direcao 
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para inverter direção da ordenação
function inverterDirecao($atual)
{
    return $atual === 'ASC' ? 'DESC' : 'ASC';
}

// Função para criar URL de ordenação
function urlOrdenacao($coluna, $ordem_atual, $direcao_atual)
{
    $nova_direcao = ($coluna === $ordem_atual) ? inverterDirecao($direcao_atual) : 'ASC';
    $params = $_GET;
    $params['ordem'] = $coluna;
    $params['direcao'] = $nova_direcao;
    return '?' . http_build_query($params);
}

// Função para criar URL de paginação
function urlPaginacao($pagina)
{
    $params = $_GET;
    $params['pagina'] = $pagina;
    return '?' . http_build_query($params);
}

// Função para criar URL de filtro de status
function urlFiltroStatus($status)
{
    $params = $_GET;
    $params['status'] = $status;
    $params['pagina'] = 1; // Voltar para a primeira página ao mudar o filtro
    return '?' . http_build_query($params);
}

// Ícone para indicar a direção da ordenação
function iconeOrdenacao($coluna, $ordem_atual, $direcao_atual)
{
    if ($coluna !== $ordem_atual) {
        return '';
    }
    return ($direcao_atual === 'ASC') ? '↑' : '↓';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
    <!-- Bootstrap CSS incluído no header.php -->
    <style>
        .table-container {
            max-width: 90%;
            margin: 0 auto;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .form-container {
            max-width: 90%;
            margin: 0 auto;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        .sorting-header {
            cursor: pointer;
        }

        .alert-container {
            max-width: 90%;
            margin: 10px auto;
        }

        @media (max-width: 768px) {

            .form-container,
            .table-container,
            .alert-container {
                max-width: 95%;
            }
        }
    </style>
</head>

<body>

    <!-- Mensagens de feedback -->
    <?php if (!empty($mensagem)): ?>
        <div class="alert-container">
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulário para cadastrar grupos -->

    <div class="form-container mt-3">
        <h2 class="text-center mb-4" style="font-size: 24px; font-weight: bold; color: black;">Cadastro de Grupos</h2>
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="col-md-6">
                <label class="form-label" style="font-weight: bold; color: black;" for="grupo">Nome do Grupo:</label>
                <input class="form-control" type="text" name="grupo" id="grupo" required>
            </div>

            <div class="col-md-2">
                <label class="form-label" style="font-weight: bold; color: black;">Status:</label>
                <select class="form-select" name="status" id="status">
                    <option value="ativo" selected>Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Cadastrar</button>
                <button class="btn btn-warning flex-grow-1" type="reset">Limpar</button>
                <a class="btn btn-primary flex-grow-1" href="index.php">Início</a>
            </div>
        </form>
    </div>

    <!-- Formulário de pesquisa -->
    <div class="table-container">
        <div class="row mb-3 mt-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input class="form-control" type="search" id="pesquisar" placeholder="Pesquisar..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button onclick="searchData()" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="btn-group float-end" role="group">
                    <a href="<?= urlFiltroStatus('todos') ?>" class="btn btn-outline-primary btn-sm <?= $filtro_status === 'todos' ? 'active' : '' ?>">Todos</a>
                    <a href="<?= urlFiltroStatus('ativos') ?>" class="btn btn-outline-primary btn-sm <?= $filtro_status === 'ativos' ? 'active' : '' ?>">Ativos</a>
                    <a href="<?= urlFiltroStatus('inativos') ?>" class="btn btn-outline-primary btn-sm <?= $filtro_status === 'inativos' ? 'active' : '' ?>">Inativos</a>
                </div>
            </div>
        </div>
    
        <!-- Listagem de Grupos -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="text-center sorting-header" onclick="window.location.href='<?= urlOrdenacao('id', $ordem_coluna, $ordem_direcao) ?>'">
                            ID <?= iconeOrdenacao('id', $ordem_coluna, $ordem_direcao) ?>
                        </th>
                        <th class="text-center sorting-header" onclick="window.location.href='<?= urlOrdenacao('nome', $ordem_coluna, $ordem_direcao) ?>'">
                            Nome <?= iconeOrdenacao('nome', $ordem_coluna, $ordem_direcao) ?>
                        </th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($grupos) > 0): ?>
                        <?php foreach ($grupos as $grupo): ?>
                            <tr class="<?= ($grupo['ativo'] === 'false' || $grupo['ativo'] === false || $grupo['ativo'] === 'f') ? 'table-secondary' : '' ?>">
                                <td class="text-center"><?= htmlspecialchars($grupo['id']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($grupo['nome']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= ($grupo['ativo'] === 'true' || $grupo['ativo'] === true || $grupo['ativo'] === 't') ? 'bg-success' : 'bg-danger' ?>">
                                        <?= ($grupo['ativo'] === 'true' || $grupo['ativo'] === true || $grupo['ativo'] === 't') ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-primary btn-sm" href="editar_grupo.php?id=<?= $grupo['id'] ?>">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <?php
                                        $ehAtivo = ($grupo['ativo'] === 'true' || $grupo['ativo'] === true || $grupo['ativo'] === 't');
                                        $acaoCor = $ehAtivo ? 'warning' : 'success';
                                        $acaoIcone = $ehAtivo ? 'bi-toggle-off' : 'bi-toggle-on';
                                        $acaoTexto = $ehAtivo ? 'Inativar' : 'Ativar';
                                        ?>
                                        <a class="btn btn-<?= $acaoCor ?> btn-sm"
                                            href="toggle_status_grupo.php?id=<?= $grupo['id'] ?>&acao=<?= $grupo['ativo'] === 'true' ? 'inativar' : 'ativar' ?>">
                                            <i class="bi <?= $acaoIcone ?>"></i> <?= $acaoTexto ?>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirmarExclusao"
                                            data-id="<?= $grupo['id'] ?>"
                                            data-nome="<?= htmlspecialchars($grupo['nome']) ?>">
                                            <i class="bi bi-trash"></i> Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhum grupo encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegação de páginas">
                <ul class="pagination">
                    <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= urlPaginacao($pagina_atual - 1) ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                        <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= urlPaginacao($i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= urlPaginacao($pagina_atual + 1) ?>" aria-label="Próximo">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmação de exclusão -->
    <div class="modal fade" id="confirmarExclusao" tabindex="-1" aria-labelledby="confirmarExclusaoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmarExclusaoLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir o grupo <strong id="nomeGrupo"></strong>?
                    <p class="text-danger mt-2">Esta ação também excluirá todos os produtos associados a este grupo.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnExcluir" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função de pesquisa com validação
        var search = document.getElementById("pesquisar");

        // Verificar tecla pressionada e chamar a função searchData() se for Enter
        search.addEventListener("keyup", function(event) {
            if (event.key === "Enter") {
                searchData();
            }
        });

        function searchData() {
            const termo = search.value.trim();
            // Manter o filtro de status atual na busca
            const status = '<?= $filtro_status ?>';
            window.location = 'cadastro_grupos.php?search=' + encodeURIComponent(termo) + '&status=' + status;
        }

        // Configuração do modal de confirmação
        document.getElementById('confirmarExclusao').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');

            document.getElementById('nomeGrupo').textContent = nome;
            document.getElementById('btnExcluir').href = 'excluir_grupo.php?id=' + id;
        });

        // Fechar alertas automaticamente após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alertList = document.querySelectorAll('.alert');
            alertList.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>

</html>