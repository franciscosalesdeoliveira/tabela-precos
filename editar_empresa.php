<?php
$titulo = "Pesquisa de Empresas";
require_once 'connection.php';
require_once 'header.php';

// Configurações de paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Filtros de pesquisa
$filtro_nome = $_GET['nome'] ?? '';
$filtro_cnpj = $_GET['cnpj'] ?? '';
$filtro_cidade = $_GET['cidade'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_ativo = $_GET['ativo'] ?? '';

// Construir query de pesquisa
$where_conditions = [];
$params = [];

if (!empty($filtro_nome)) {
    $where_conditions[] = "(razao_social LIKE :nome OR fantasia LIKE :nome)";
    $params[':nome'] = "%$filtro_nome%";
}

if (!empty($filtro_cnpj)) {
    // Remover formatação do CNPJ para pesquisa
    $cnpj_limpo = preg_replace('/\D/', '', $filtro_cnpj);
    if (strlen($cnpj_limpo) > 0) {
        $where_conditions[] = "REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') LIKE :cnpj";
        $params[':cnpj'] = "%$cnpj_limpo%";
    }
}

if (!empty($filtro_cidade)) {
    $where_conditions[] = "cidade LIKE :cidade";
    $params[':cidade'] = "%$filtro_cidade%";
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "estado = :estado";
    $params[':estado'] = $filtro_estado;
}

if ($filtro_ativo !== '') {
    $where_conditions[] = "ativo = :ativo";
    $params[':ativo'] = $filtro_ativo;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Query para contar total de registros
$count_sql = "SELECT COUNT(*) as total FROM empresas $where_clause";
$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_registros = $count_stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Query principal para buscar empresas
$sql = "SELECT 
            id, 
            uuid, 
            cpf_cnpj, 
            razao_social, 
            fantasia, 
            telefone, 
            email, 
            cidade, 
            estado, 
            ativo, 
            criado_em,
            atualizado_em
        FROM empresas 
        $where_clause 
        ORDER BY razao_social ASC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Bind dos parâmetros de pesquisa
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind dos parâmetros de paginação
$stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$empresas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa de Empresas</title>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --bg-color: #f8f9fa;
            --text-color: #333;
            --border-color: #ddd;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 30px;
            text-align: center;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: var(--primary-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-novo {
            background-color: var(--success-color);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-novo:hover {
            background-color: #27ae60;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
            justify-content: center;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .results-count {
            font-size: 14px;
            color: #666;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .empresa-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .empresa-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
        }

        .empresa-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .empresa-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .empresa-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            min-width: 60px;
            display: inline-block;
        }

        .status-ativo {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }

        .status-inativo {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 3px;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .filters-form {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                grid-column: 1;
                justify-content: stretch;
            }

            .filter-actions .btn {
                flex: 1;
            }

            .results-info {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .empresa-table {
                font-size: 12px;
            }

            .empresa-table th,
            .empresa-table td {
                padding: 8px 4px;
            }

            .actions {
                flex-direction: column;
                gap: 4px;
            }

            .pagination {
                flex-wrap: wrap;
            }
        }

        .debug-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #856404;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Painel Administrativo</h1>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2 class="section-title">
                Pesquisa de Empresas
                <a href="cadastro_empresa.php" class="btn-novo">+ Nova Empresa</a>
            </h2>

            <!-- Debug Info (remover em produção) -->
            <?php if (!empty($_GET)): ?>
                <div class="debug-info">
                    <strong>Debug - Parâmetros de pesquisa:</strong><br>
                    <?php foreach ($_GET as $key => $value): ?>
                        <?= htmlspecialchars($key) ?>: <?= htmlspecialchars($value) ?><br>
                    <?php endforeach; ?>
                    <strong>Query SQL:</strong> <?= htmlspecialchars($sql) ?><br>
                    <strong>Where Clause:</strong> <?= htmlspecialchars($where_clause) ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de Filtros -->
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="nome">Razão Social / Nome Fantasia:</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($filtro_nome) ?>" placeholder="Digite o nome da empresa">
                </div>

                <div class="form-group">
                    <label for="cnpj">CNPJ:</label>
                    <input type="text" id="cnpj" name="cnpj" value="<?= htmlspecialchars($filtro_cnpj) ?>" placeholder="Digite o CNPJ" maxlength="18">
                </div>

                <div class="form-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($filtro_cidade) ?>" placeholder="Digite a cidade">
                </div>

                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado">
                        <option value="">Todos os estados</option>
                        <option value="AC" <?= $filtro_estado == 'AC' ? 'selected' : '' ?>>AC</option>
                        <option value="AL" <?= $filtro_estado == 'AL' ? 'selected' : '' ?>>AL</option>
                        <option value="AP" <?= $filtro_estado == 'AP' ? 'selected' : '' ?>>AP</option>
                        <option value="AM" <?= $filtro_estado == 'AM' ? 'selected' : '' ?>>AM</option>
                        <option value="BA" <?= $filtro_estado == 'BA' ? 'selected' : '' ?>>BA</option>
                        <option value="CE" <?= $filtro_estado == 'CE' ? 'selected' : '' ?>>CE</option>
                        <option value="DF" <?= $filtro_estado == 'DF' ? 'selected' : '' ?>>DF</option>
                        <option value="ES" <?= $filtro_estado == 'ES' ? 'selected' : '' ?>>ES</option>
                        <option value="GO" <?= $filtro_estado == 'GO' ? 'selected' : '' ?>>GO</option>
                        <option value="MA" <?= $filtro_estado == 'MA' ? 'selected' : '' ?>>MA</option>
                        <option value="MT" <?= $filtro_estado == 'MT' ? 'selected' : '' ?>>MT</option>
                        <option value="MS" <?= $filtro_estado == 'MS' ? 'selected' : '' ?>>MS</option>
                        <option value="MG" <?= $filtro_estado == 'MG' ? 'selected' : '' ?>>MG</option>
                        <option value="PA" <?= $filtro_estado == 'PA' ? 'selected' : '' ?>>PA</option>
                        <option value="PB" <?= $filtro_estado == 'PB' ? 'selected' : '' ?>>PB</option>
                        <option value="PR" <?= $filtro_estado == 'PR' ? 'selected' : '' ?>>PR</option>
                        <option value="PE" <?= $filtro_estado == 'PE' ? 'selected' : '' ?>>PE</option>
                        <option value="PI" <?= $filtro_estado == 'PI' ? 'selected' : '' ?>>PI</option>
                        <option value="RJ" <?= $filtro_estado == 'RJ' ? 'selected' : '' ?>>RJ</option>
                        <option value="RN" <?= $filtro_estado == 'RN' ? 'selected' : '' ?>>RN</option>
                        <option value="RS" <?= $filtro_estado == 'RS' ? 'selected' : '' ?>>RS</option>
                        <option value="RO" <?= $filtro_estado == 'RO' ? 'selected' : '' ?>>RO</option>
                        <option value="RR" <?= $filtro_estado == 'RR' ? 'selected' : '' ?>>RR</option>
                        <option value="SC" <?= $filtro_estado == 'SC' ? 'selected' : '' ?>>SC</option>
                        <option value="SP" <?= $filtro_estado == 'SP' ? 'selected' : '' ?>>SP</option>
                        <option value="SE" <?= $filtro_estado == 'SE' ? 'selected' : '' ?>>SE</option>
                        <option value="TO" <?= $filtro_estado == 'TO' ? 'selected' : '' ?>>TO</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ativo">Status:</label>
                    <select id="ativo" name="ativo">
                        <option value="">Todos</option>
                        <option value="S" <?= $filtro_ativo == 'S' ? 'selected' : '' ?>>Ativo</option>
                        <option value="N" <?= $filtro_ativo == 'N' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Pesquisar</button>
                    <a href="pesquisa_empresas.php" class="btn btn-secondary">🧹 Limpar</a>
                </div>
            </form>

            <!-- Informações dos Resultados -->
            <div class="results-info">
                <div class="results-count">
                    Mostrando <?= count($empresas) ?> de <?= $total_registros ?> empresa(s) encontrada(s)
                </div>
                <div class="results-count">
                    Página <?= $pagina_atual ?> de <?= $total_paginas ?>
                </div>
            </div>

            <!-- Tabela de Resultados -->
            <?php if (count($empresas) > 0): ?>
                <div class="table-container">
                    <table class="empresa-table">
                        <thead>
                            <tr>
                                <th>Razão Social</th>
                                <th>Nome Fantasia</th>
                                <th>CNPJ</th>
                                <th>Cidade/UF</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empresas as $empresa): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($empresa['razao_social']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($empresa['fantasia'] ?: '-') ?></td>
                                    <td>
                                        <?php
                                        $cnpj = $empresa['cpf_cnpj'];
                                        if (strlen($cnpj) == 14) {
                                            echo preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
                                        } else {
                                            echo htmlspecialchars($cnpj);
                                        }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($empresa['cidade'] . '/' . $empresa['estado']) ?></td>
                                    <td><?= htmlspecialchars($empresa['telefone'] ?: '-') ?></td>
                                    <td>
                                        <span class="status-badge <?= $empresa['ativo'] == 'S' ? 'status-ativo' : 'status-inativo' ?>">
                                            <?= $empresa['ativo'] == 'S' ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $data_cadastro = new DateTime($empresa['criado_em']);
                                        echo $data_cadastro->format('d/m/Y');
                                        ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="visualizar_empresa.php?id=<?= urlencode($empresa['id']) ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Visualizar"
                                               target="_blank">
                                                👁️
                                            </a>
                                            <a href="editar_empresa.php?id=<?= urlencode($empresa['id']) ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Editar"
                                               onclick="return confirmarEdicao(<?= $empresa['id'] ?>, '<?= htmlspecialchars(addslashes($empresa['razao_social'])) ?>')">
                                                ✏️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php
                        $query_params = $_GET;

                        // Botão Anterior
                        if ($pagina_atual > 1):
                            $query_params['pagina'] = $pagina_atual - 1;
                            $url_anterior = '?' . http_build_query($query_params);
                        ?>
                            <a href="<?= $url_anterior ?>">« Anterior</a>
                        <?php else: ?>
                            <span class="disabled">« Anterior</span>
                        <?php endif; ?>

                        <?php
                        // Páginas numeradas
                        $inicio = max(1, $pagina_atual - 2);
                        $fim = min($total_paginas, $pagina_atual + 2);

                        for ($i = $inicio; $i <= $fim; $i++):
                            $query_params['pagina'] = $i;
                            $url_pagina = '?' . http_build_query($query_params);
                        ?>
                            <?php if ($i == $pagina_atual): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= $url_pagina ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php
                        // Botão Próximo
                        if ($pagina_atual < $total_paginas):
                            $query_params['pagina'] = $pagina_atual + 1;
                            $url_proximo = '?' . http_build_query($query_params);
                        ?>
                            <a href="<?= $url_proximo ?>">Próximo »</a>
                        <?php else: ?>
                            <span class="disabled">Próximo »</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-results">
                    <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;">🔍</div>
                    <h3>Nenhuma empresa encontrada</h3>
                    <p>Tente ajustar os filtros de pesquisa ou <a href="cadastro_empresa.php">cadastre uma nova empresa</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formatação do CNPJ no filtro
            const cnpjInput = document.getElementById('cnpj');
            if (cnpjInput) {
                cnpjInput.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        // CPF
                        value = value.replace(/^(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                        value = value.replace(/\.(\d{3})(\d)/, '.$1-$2');
                    } else {
                        // CNPJ
                        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                        value = value.replace(/(\d{4})(\d)/, '$1-$2');
                    }
                    this.value = value;
                });
            }

            // Confirmar antes de limpar filtros (apenas se houver filtros aplicados)
            const btnLimpar = document.querySelector('a[href="pesquisa_empresas.php"]');
            if (btnLimpar) {
                btnLimpar.addEventListener('click', function(e) {
                    const temFiltros = <?= !empty(array_filter([$filtro_nome, $filtro_cnpj, $filtro_cidade, $filtro_estado, $filtro_ativo])) ? 'true' : 'false' ?>;

                    if (temFiltros && !confirm('Deseja realmente limpar todos os filtros?')) {
                        e.preventDefault();
                    }
                });
            }
        });

        // Função para confirmar edição
        function confirmarEdicao(id, nomeEmpresa) {
            console.log('Tentando editar empresa ID:', id, 'Nome:', nomeEmpresa);
            return confirm('Deseja editar a empresa "' + nomeEmpresa + '"?');
        }

        // Função para destacar termo pesquisado
        function destacarTermo(texto, termo) {
            if (!termo) return texto;
            const regex = new RegExp(`(${termo})`, 'gi');
            return texto.replace(regex, '<mark style="background-color: yellow; padding: 2px;">$1</mark>');
        }

        // Aplicar destaque nos resultados se houver termo de pesquisa
        document.addEventListener('DOMContentLoaded', function() {
            const termoPesquisa = '<?= htmlspecialchars($filtro_nome) ?>';
            if (termoPesquisa) {
                const celulasNome = document.querySelectorAll('.empresa-table tbody td:first-child strong');
                const celulasFantasia = document.querySelectorAll('.empresa-table tbody td:nth-child(2)');

                celulasNome.forEach(celula => {
                    celula.innerHTML = destacarTermo(celula.textContent, termoPesquisa);
                });

                celulasFantasia.forEach(celula => {
                    if (celula.textContent !== '-') {
                        celula.innerHTML = destacarTermo(celula.textContent, termoPesquisa);
                    }
                });
            }
        });

        // Debug dos links de ação
        document.addEventListener('DOMContentLoaded', function() {
            const linksAcao = document.querySelectorAll('.actions a');
            linksAcao.forEach(link => {
                link.addEventListener('click', function(e) {
                    console.log('Link clicado:', this.href);
                    console.log('Tipo de ação:', this.title);
                    
                    // Para debug, comentar a linha abaixo depois dos testes
                    // e.preventDefault();
                });
            });
        });
    </script>
</body>

</html>