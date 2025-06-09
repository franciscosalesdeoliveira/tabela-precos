<?php
$titulo = "Listar Empresas";
require_once 'connection.php';
require_once 'header.php';

// Parâmetros de paginação e filtros
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_ativo = isset($_GET['ativo']) ? $_GET['ativo'] : '';
$filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($filtro_ativo !== '') {
    $where_conditions[] = "ativo = :ativo";
    $params[':ativo'] = $filtro_ativo;
}

if ($filtro_busca !== '') {
    $where_conditions[] = "(
        unaccent(lower(razao_social)) LIKE unaccent(lower(:busca)) OR
        unaccent(lower(fantasia)) LIKE unaccent(lower(:busca)) OR
        unaccent(lower(email)) LIKE unaccent(lower(:busca)) OR
        cpf_cnpj LIKE :busca
    )";
    $params[':busca'] = '%' . $filtro_busca . '%';
}


$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Contar total de registros
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM empresas $where_clause");
    $stmt->execute($params);
    $total_registros = $stmt->fetch()['total'];
    $total_paginas = ceil($total_registros / $limite);

    // Buscar empresas com paginação
    $sql = "SELECT id, razao_social, fantasia, cpf_cnpj, email, telefone, cidade, estado, ativo, criado_em, atualizado_em 
            FROM empresas 
            $where_clause 
            ORDER BY razao_social ASC 
            LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind dos parâmetros de filtro
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind dos parâmetros de paginação
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $empresas = $stmt->fetchAll();
} catch (PDOException $e) {
    $empresas = [];
    $total_registros = 0;
    $total_paginas = 0;
    $erro = "Erro ao buscar empresas: " . $e->getMessage();
}

// Processar exclusão
if (isset($_POST['excluir']) && isset($_POST['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = :id");
        $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
        $stmt->execute();

        $sucesso = "Empresa excluída com sucesso!";
        // Recarregar a página para atualizar a lista
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao excluir empresa: " . $e->getMessage();
    }
}

// Processar alteração de status
if (isset($_POST['alterar_status']) && isset($_POST['id']) && isset($_POST['novo_status'])) {
    try {
        $stmt = $pdo->prepare("UPDATE empresas SET ativo = :ativo, atualizado_em = NOW() WHERE id = :id");
        $stmt->bindParam(':ativo', $_POST['novo_status']);
        $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
        $stmt->execute();

        $sucesso = "Status da empresa alterado com sucesso!";
        // Recarregar a página para atualizar a lista
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao alterar status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Empresas</title>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: var(--dark-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: var(--primary-color);
            color: white;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .table-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-ativo {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-inativo {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;

        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .stats-info {
            color: var(--dark-color);
            opacity: 0.7;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--dark-color);
            opacity: 0.6;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-hover);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: var(--dark-color);
            opacity: 0.5;
        }

        .close:hover {
            opacity: 1;
        }

        .modal-body {
            margin-bottom: 2rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .filters-form {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
            }

            .table-section {
                padding: 1rem;
            }

            .table {
                font-size: 0.8rem;
            }

            .actions {
                flex-direction: column;
            }

            .btn-sm {
                width: 100%;
                justify-content: center;
            }
        }


        .footer-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            color: var(--text-color) !important;
        }

        .footer-custom-text {
            margin-top: -15px !important;
            font-size: 14px;
            color: var(--text-color);
        }

        .footer-custom a {
            color: var(--text-color) !important;
            text-decoration: none;
        }

        .footer-custom a:hover {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                🏢 Sistema Administrativo
            </a>
            <nav class="nav-links">
                <a target="_blank" class="btn btn-primary" href="dashboard.php">Dashboard</a>
                <a target="_blank" class="btn btn-primary" href="cadastro_empresa.php">Nova Empresa</a>
                <a target="_blank" class="btn btn-primary" href="cadastro_usuarios.php">Cadastro de Usuários</a>
            </nav>
        </div>
    </header>


    <div class="container">
        <div class="page-header">
            <h1 class="page-title">📋 Listar Empresas</h1>

        </div>

        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="busca">Buscar</label>
                    <input type="text" id="busca" name="busca" class="form-control"
                        placeholder="Razão social, fantasia, CNPJ ou email..."
                        value="<?= htmlspecialchars($filtro_busca) ?>">
                </div>

                <div class="form-group">
                    <label for="ativo">Status</label>
                    <select id="ativo" name="ativo" class="form-control">
                        <option value="">Todos</option>
                        <option value="S" <?= $filtro_ativo === 'S' ? 'selected' : '' ?>>Ativo</option>
                        <option value="N" <?= $filtro_ativo === 'N' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                </div>

                <div class="form-group">
                    <a href="listar_empresas.php" class="btn btn-secondary">🔄 Limpar</a>
                </div>
            </form>
        </div>

        <div class="table-section">
            <div class="stats-info">
                Exibindo <?= count($empresas) ?> de <?= $total_registros ?> empresa(s) encontrada(s)
            </div>

            <?php if (empty($empresas)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏢</div>
                    <h3>Nenhuma empresa encontrada</h3>
                    <p>Não há empresas cadastradas com os filtros selecionados.</p>
                    <a href="cadastro_empresa.php" class="btn btn-primary" style="margin-top: 1rem;">
                        ➕ Cadastrar primeira empresa
                    </a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>CNPJ</th>
                            <th>Contato</th>
                            <th>Localização</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div style="font-weight: 600; color: var(--primary-color);">
                                            <?= htmlspecialchars($empresa['razao_social']) ?>
                                        </div>
                                        <?php if ($empresa['fantasia']): ?>
                                            <div style="font-size: 0.8rem; opacity: 0.7;">
                                                <?= htmlspecialchars($empresa['fantasia']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family: monospace;">
                                        <?= htmlspecialchars($empresa['cpf_cnpj']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($empresa['email']): ?>
                                        <div style="font-size: 0.8rem;">
                                            📧 <?= htmlspecialchars($empresa['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($empresa['telefone']): ?>
                                        <div style="font-size: 0.8rem;">
                                            📞 <?= htmlspecialchars($empresa['telefone']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($empresa['cidade'] || $empresa['estado']): ?>
                                        <span style="font-size: 0.9rem;">
                                            <?= htmlspecialchars($empresa['cidade']) ?>
                                            <?php if ($empresa['cidade'] && $empresa['estado']): ?> - <?php endif; ?>
                                            <?= htmlspecialchars($empresa['estado']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="opacity: 0.5;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $empresa['ativo'] === 'S' ? 'status-ativo' : 'status-inativo' ?>">
                                        <?= $empresa['ativo'] === 'S' ? '✅ Ativo' : '⏸️ Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem;">
                                        <?= $empresa['criado_em'] ? date('d/m/Y', strtotime($empresa['criado_em'])) : '-' ?>
                                    </div>
                                    <?php if ($empresa['atualizado_em'] && $empresa['atualizado_em'] !== $empresa['criado_em']): ?>
                                        <div style="font-size: 0.7rem; opacity: 0.6;">
                                            Atualizado: <?= date('d/m/Y', strtotime($empresa['atualizado_em'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- botões de ação -->
                                    <div class="actions">
                                        <a href="visualizar_empresa.php?id=<?= $empresa['id'] ?>"
                                            class="btn btn-info btn-sm" style="height: 40px;" title="Visualizar">
                                            👁️ Visualizar
                                        </a>
                                        <a href="editar_empresa.php?id=<?= $empresa['id'] ?>"
                                            class="btn btn-warning btn-sm" style="height: 40px;" title=" Editar">
                                            ✏️ Editar
                                        </a>
                                        <button onclick="alterarStatus(<?= $empresa['id'] ?>, '<?= $empresa['ativo'] === 'S' ? 'N' : 'S' ?>')"
                                            class="btn <?= $empresa['ativo'] === 'S' ? 'btn-warning' : 'btn-success' ?> btn-sm"
                                            title="<?= $empresa['ativo'] === 'S' ? 'Desativar' : 'Ativar'  ?>" style="height: 40px;">
                                            <?= $empresa['ativo'] === 'S' ? '⏸️ Desativar' : '▶️ Ativar' ?>
                                        </button>
                                        <button onclick="confirmarExclusao(<?= $empresa['id'] ?>, '<?= htmlspecialchars($empresa['razao_social'], ENT_QUOTES) ?>' ) "
                                            class="btn btn-danger btn-sm" style="height: 40px;" title="Excluir">
                                            🗑️ Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>">« Primeira</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">‹ Anterior</a>
                        <?php endif; ?>

                        <?php
                        $inicio = max(1, $pagina - 2);
                        $fim = min($total_paginas, $pagina + 2);

                        for ($i = $inicio; $i <= $fim; $i++):
                        ?>
                            <?php if ($i == $pagina): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">Próxima ›</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>">Última »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="modalExclusao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">🗑️ Confirmar Exclusão</h2>
                <span class="close" onclick="fecharModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a empresa:</p>
                <p><strong id="nomeEmpresa"></strong></p>
                <p style="color: var(--danger-color); margin-top: 1rem;">
                    ⚠️ Esta ação não pode ser desfeita!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="id" id="empresaId">
                    <button type="submit" name="excluir" class="btn btn-danger">🗑️ Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulários ocultos para ações -->
    <form id="formStatus" method="POST" style="display: none;">
        <input type="hidden" name="id" id="statusEmpresaId">
        <input type="hidden" name="novo_status" id="novoStatus">
        <input type="hidden" name="alterar_status" value="1">
    </form>

    <script>
        function confirmarExclusao(id, nome) {
            document.getElementById('empresaId').value = id;
            document.getElementById('nomeEmpresa').textContent = nome;
            document.getElementById('modalExclusao').style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modalExclusao').style.display = 'none';
        }

        function alterarStatus(id, novoStatus) {
            if (confirm('Tem certeza que deseja alterar o status desta empresa?')) {
                document.getElementById('statusEmpresaId').value = id;
                document.getElementById('novoStatus').value = novoStatus;
                document.getElementById('formStatus').submit();
            }
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('modalExclusao');
            if (event.target === modal) {
                fecharModal();
            }
        }

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
<?php
require_once 'footer.php';
?>

</html>