<?php ?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #343a40;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-action {
            width: 38px;
            height: 38px;
            padding: 6px 0;
            border-radius: 50%;
            text-align: center;
            line-height: 1.42857;
            margin: 0 3px;
        }
        .table-container {
            overflow-y: auto;
            max-height: 550px;
            border-radius: 0 0 10px 10px;
        }
        .table-dark {
            --bs-table-bg: #343a40;
        }
        .sticky-header th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            z-index: 1;
        }
        .produto-inativo {
            opacity: 0.7;
            background-color: #f8d7da !important;
        }
        .form-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .links-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Cabeçalho da Página -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center">Cadastro de Produtos</h1>
            </div>
        </div>
        
        <div class="row">
            <!-- Coluna do Formulário -->
            <div class="col-lg-4 mb-4">
                <div class="form-section">
                    <h4 class="mb-3 text-center">Novo Produto</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome:</label>
                            <input type="text" class="form-control" name="nome" id="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição:</label>
                            <input type="text" class="form-control" name="descricao" id="descricao">
                        </div>
                        <div class="mb-3">
                            <label for="grupo_id" class="form-label">Grupo:</label>
                            <select class="form-select" name="grupo_id" id="grupo_id" required>
                                <!-- PHP code for groups goes here -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="preco" class="form-label">Preço:</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" name="preco" id="preco" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="ativo" id="ativo" checked>
                            <label class="form-check-label" for="ativo">
                                Produto Ativo
                            </label>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button class="btn btn-warning" type="reset">
                                <i class="fas fa-eraser me-1"></i> Limpar
                            </button>
                            <button class="btn btn-success" type="submit">
                                <i class="fas fa-save me-1"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Links Úteis -->
                <div class="links-section">
                    <h4 class="mb-3 text-center">Links Úteis</h4>
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i> Página Inicial
                        </a>
                        <a href="cadastro_grupos.php" class="btn btn-primary">
                            <i class="fas fa-layer-group me-1"></i> Cadastro de Grupos
                        </a>
                        <a href="excel.php" class="btn btn-primary">
                            <i class="fas fa-file-excel me-1"></i> Importar CSV
                        </a>
                        <a href="configuracoes.php" class="btn btn-primary">
                            <i class="fas fa-cogs me-1"></i> Configurações
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Coluna da Tabela -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lista de Produtos</h5>
                        <div class="d-flex">
                            <!-- Filtro de Status -->
                            <div class="btn-group me-2" role="group">
                                <input type="radio" class="btn-check" name="filtroStatus" id="todos" value="todos" autocomplete="off" checked onclick="filtrarPorStatus('todos')">
                                <label class="btn btn-outline-light btn-sm" for="todos">Todos</label>
                                
                                <input type="radio" class="btn-check" name="filtroStatus" id="ativos" value="ativos" autocomplete="off" onclick="filtrarPorStatus('ativos')">
                                <label class="btn btn-outline-light btn-sm" for="ativos">Ativos</label>
                                
                                <input type="radio" class="btn-check" name="filtroStatus" id="inativos" value="inativos" autocomplete="off" onclick="filtrarPorStatus('inativos')">
                                <label class="btn btn-outline-light btn-sm" for="inativos">Inativos</label>
                            </div>
                            
                            <!-- Pesquisa -->
                            <div class="input-group">
                                <input class="form-control form-control-sm" type="search" id="pesquisar" placeholder="Pesquisar...">
                                <button onclick="searchData()" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabela -->
                    <div class="table-container">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="sticky-header">
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Nome</th>
                                    <th class="text-center">Descrição</th>
                                    <th class="text-center">Grupo</th>
                                    <th class="text-center">Preço</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-produtos-body">
                                <!-- PHP loop for products goes here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        var search = document.getElementById("pesquisar");

        // Verifica a tecla apertada e chama a função searchData() se for Enter
        search.addEventListener("keyup", function(event) {
            if (event.key === "Enter") {
                searchData();
            }
        });

        function searchData() {
            window.location = 'cadastro_produtos.php?search=' + search.value;
        }

        function filtrarPorStatus(status) {
            const rows = document.querySelectorAll('.produto-row');

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');

                if (status === 'todos') {
                    row.style.display = '';
                } else if (status === 'ativos' && rowStatus === 'ativo') {
                    row.style.display = '';
                } else if (status === 'inativos' && rowStatus === 'inativo') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>