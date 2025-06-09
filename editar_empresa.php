<?php
$titulo = "Editar Empresa";
require_once 'connection.php';
require_once 'header.php';

// Verificar se foi passado o ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: pesquisa_empresas.php');
    exit;
}

$empresa_id = (int)$_GET['id'];

// Buscar dados da empresa
$sql = "SELECT * FROM empresas WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $empresa_id, PDO::PARAM_INT);
$stmt->execute();
$empresa = $stmt->fetch();

if (!$empresa) {
    echo "<script>alert('Empresa não encontrada!'); window.location.href='pesquisa_empresas.php';</script>";
    exit;
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validações básicas
        $errors = [];

        if (empty($_POST['razao_social'])) {
            $errors[] = "Razão Social é obrigatório";
        }

        if (empty($_POST['cpf_cnpj'])) {
            $errors[] = "CNPJ é obrigatório";
        } else {
            $cnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj']);
            if (strlen($cnpj) != 14) {
                $errors[] = "CNPJ deve ter 14 dígitos";
            }
        }

        if (empty($_POST['logradouro'])) {
            $errors[] = "Logradouro é obrigatório";
        }

        if (empty($_POST['estado'])) {
            $errors[] = "Estado é obrigatório";
        }

        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "E-mail inválido";
        }

        if (empty($errors)) {
            $sql_update = "UPDATE empresas SET 
                           razao_social = :razao_social,
                           fantasia = :fantasia,
                           cpf_cnpj = :cpf_cnpj,
                           telefone = :telefone,
                           email = :email,
                           site = :site,
                           logradouro = :logradouro,
                           cidade = :cidade,
                           estado = :estado,
                           cep = :cep,
                           ativo = :ativo,
                           atualizado_em = NOW()
                           WHERE id = :id";

            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':razao_social' => trim($_POST['razao_social']),
                ':fantasia' => !empty($_POST['fantasia']) ? trim($_POST['fantasia']) : null,
                ':cpf_cnpj' => preg_replace('/\D/', '', $_POST['cpf_cnpj']),
                ':telefone' => !empty($_POST['telefone']) ? preg_replace('/\D/', '', $_POST['telefone']) : null,
                ':email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                ':site' => !empty($_POST['site']) ? trim($_POST['site']) : null,
                ':logradouro' => !empty($_POST['logradouro']) ? trim($_POST['logradouro']) : null,
                ':cidade' => trim($_POST['cidade']),
                ':estado' => $_POST['estado'],
                ':cep' => !empty($_POST['cep']) ? preg_replace('/\D/', '', $_POST['cep']) : null,
                ':ativo' => $_POST['ativo'],
                ':id' => $empresa_id
            ]);

            $sucesso = "Empresa atualizada com sucesso!";

            // Recarregar dados atualizados
            $stmt->execute();
            $empresa = $stmt->fetch();
        }
    } catch (Exception $e) {
        $erro = "Erro ao atualizar empresa: " . $e->getMessage();
    }
    // Redirecionar após salvar
    header("Location: listar_empresas.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #93c5fd;
            --success-color: #10b981;
            --success-light: #d1fae5;
            --danger-color: #ef4444;
            --danger-light: #fee2e2;
            --warning-color: #f59e0b;
            --warning-light: #fef3c7;
            --bg-color: #f8fafc;
            --bg-white: #ffffff;
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --border-focus: #3b82f6;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --radius-lg: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
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

        .card {
            background: var(--bg-white);
            /* border-radius: var(--radius-lg); */
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 20px;

        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px 30px;
            /* border-bottom: 1px solid var(--border-color); */

        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 30px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background-color: var(--success-light);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-danger {
            background-color: var(--danger-light);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .form-group label.required::after {
            content: " *";
            color: var(--danger-color);
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: var(--bg-white);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--border-focus);
            background-color: #fafbff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input.error,
        .form-group select.error {
            border-color: var(--danger-color);
            background-color: #fef2f2;
        }

        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.active {
            background-color: var(--success-light);
            color: #065f46;
        }

        .status-badge.inactive {
            background-color: var(--danger-light);
            color: #991b1b;
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
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .form-actions {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .card-body {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                margin-right: 0;
                justify-content: center;
            }
        }

        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading.show {
            display: flex;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .info-box {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #0ea5e9;
            border-radius: var(--radius);
            padding: 15px;
            margin-bottom: 25px;
            color: #0c4a6e;
        }

        .info-box i {
            margin-right: 8px;
            color: #0ea5e9;
        }

        .footer-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            color: var(--text-color) !important;
        }

        .footer-custom-text {
            margin-top: -10px !important;
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
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

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

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Editar Empresa</h2>
        </div>

        <div class="card-body">
            <?php if (isset($sucesso)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($sucesso) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Corrija os seguintes erros:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Empresa criada em:</strong>
                <?= date('d/m/Y H:i', strtotime($empresa['criado_em'])) ?>
                <?php if ($empresa['atualizado_em']): ?>
                    | <strong>Última atualização:</strong>
                    <?= date('d/m/Y H:i', strtotime($empresa['atualizado_em'])) ?>
                <?php endif; ?>
            </div>

            <form method="POST" id="empresaForm" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="razao_social" class="required">Razão Social</label>
                        <div class="input-wrapper">
                            <input type="text" id="razao_social" name="razao_social"
                                value="<?= htmlspecialchars($empresa['razao_social']) ?>"
                                required maxlength="255" autocomplete="organization">
                            <i class="fas fa-building input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fantasia">Nome Fantasia</label>
                        <div class="input-wrapper">
                            <input type="text" id="fantasia" name="fantasia"
                                value="<?= htmlspecialchars($empresa['fantasia'] ?: '') ?>"
                                maxlength="255">
                            <i class="fas fa-store input-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cpf_cnpj" class="required">CNPJ</label>
                        <div class="input-wrapper">
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj"
                                value="<?= htmlspecialchars($empresa['cpf_cnpj']) ?>"
                                required pattern="\d{14}"
                                placeholder="00.000.000/0000-00">
                            <i class="fas fa-id-card input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <div class="input-wrapper">
                            <input type="tel" id="telefone" name="telefone"
                                value="<?= htmlspecialchars($empresa['telefone'] ?: '') ?>"
                                placeholder="(00) 00000-0000">
                            <i class="fas fa-phone input-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email"
                                value="<?= htmlspecialchars($empresa['email'] ?: '') ?>"
                                autocomplete="email" maxlength="255">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        <label for="site">Site</label>
                        <div class="input-wrapper">
                            <input type="text" id="site" name="site"
                                value="<?= htmlspecialchars($empresa['site'] ?: '') ?>">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label for="logradouro">Logradouro</label>
                        <div class="input-wrapper">
                            <input type="text" id="logradouro" name="logradouro"
                                value="<?= htmlspecialchars($empresa['logradouro'] ?: '') ?>"
                                maxlength="255">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cidade" class="required">Cidade</label>
                        <div class="input-wrapper">
                            <input type="text" id="cidade" name="cidade"
                                value="<?= htmlspecialchars($empresa['cidade']) ?>"
                                required maxlength="100">
                            <i class="fas fa-city input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cep">CEP</label>
                        <div class="input-wrapper">
                            <input type="text" id="cep" name="cep"
                                value="<?= htmlspecialchars($empresa['cep'] ?: '') ?>"
                                pattern="\d{8}" placeholder="00000-000">
                            <i class="fas fa-mail-bulk input-icon"></i>
                        </div>
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> O endereço será preenchido automaticamente
                        </small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estado" class="required">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="">Selecione o estado...</option>
                            <?php
                            $estados = [
                                'AC' => 'Acre',
                                'AL' => 'Alagoas',
                                'AP' => 'Amapá',
                                'AM' => 'Amazonas',
                                'BA' => 'Bahia',
                                'CE' => 'Ceará',
                                'DF' => 'Distrito Federal',
                                'ES' => 'Espírito Santo',
                                'GO' => 'Goiás',
                                'MA' => 'Maranhão',
                                'MT' => 'Mato Grosso',
                                'MS' => 'Mato Grosso do Sul',
                                'MG' => 'Minas Gerais',
                                'PA' => 'Pará',
                                'PB' => 'Paraíba',
                                'PR' => 'Paraná',
                                'PE' => 'Pernambuco',
                                'PI' => 'Piauí',
                                'RJ' => 'Rio de Janeiro',
                                'RN' => 'Rio Grande do Norte',
                                'RS' => 'Rio Grande do Sul',
                                'RO' => 'Rondônia',
                                'RR' => 'Roraima',
                                'SC' => 'Santa Catarina',
                                'SP' => 'São Paulo',
                                'SE' => 'Sergipe',
                                'TO' => 'Tocantins'
                            ];
                            foreach ($estados as $sigla => $nome): ?>
                                <option value="<?= $sigla ?>" <?= $empresa['estado'] == $sigla ? 'selected' : '' ?>>
                                    <?= $sigla ?> - <?= $nome ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ativo" class="required">Status</label>
                        <select id="ativo" name="ativo" required>
                            <option value="S" <?= $empresa['ativo'] == 'S' ? 'selected' : '' ?>>
                                <i class="fas fa-check-circle"></i> Ativo
                            </option>
                            <option value="N" <?= $empresa['ativo'] == 'N' ? 'selected' : '' ?>>
                                <i class="fas fa-times-circle"></i> Inativo
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <div>
                        <span class="status-badge <?= $empresa['ativo'] == 'S' ? 'active' : 'inactive' ?>">
                            <i class="fas fa-<?= $empresa['ativo'] == 'S' ? 'check-circle' : 'times-circle' ?>"></i>
                            Status: <?= $empresa['ativo'] == 'S' ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                        <a href="pesquisa_empresas.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>

    <script>
        // Máscaras para campos
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara CNPJ
            const cnpjInput = document.getElementById('cpf_cnpj');
            cnpjInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            });

            // Máscara Telefone
            const telefoneInput = document.getElementById('telefone');
            telefoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            });

            // Máscara CEP
            const cepInput = document.getElementById('cep');
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });

            // Buscar CEP
            cepInput.addEventListener('blur', function(e) {
                const cep = e.target.value.replace(/\D/g, '');
                console.log('CEP digitado:', cep);

                if (cep.length === 8) {
                    // Mostrar loading
                    const logradouroInput = document.getElementById('logradouro');
                    const cidadeInput = document.getElementById('cidade');
                    const estadoSelect = document.getElementById('estado');

                    console.log('Elementos encontrados:', {
                        logradouro: !!logradouroInput,
                        cidade: !!cidadeInput,
                        estado: !!estadoSelect
                    });

                    if (logradouroInput) logradouroInput.value = 'Buscando...';
                    if (cidadeInput) cidadeInput.value = 'Buscando...';

                    console.log('Buscando CEP:', cep);

                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Dados recebidos:', data);

                            if (!data.erro) {
                                if (logradouroInput) {
                                    logradouroInput.value = data.logradouro || '';
                                    console.log('Logradouro preenchido:', data.logradouro);
                                }
                                if (cidadeInput) {
                                    cidadeInput.value = data.localidade || '';
                                    console.log('Cidade preenchida:', data.localidade);
                                }
                                if (estadoSelect) {
                                    estadoSelect.value = data.uf || '';
                                    console.log('Estado preenchido:', data.uf);
                                }

                                // Destacar campos preenchidos
                                if (logradouroInput) logradouroInput.style.backgroundColor = '#f0f9ff';
                                if (cidadeInput) cidadeInput.style.backgroundColor = '#f0f9ff';
                                if (estadoSelect) estadoSelect.style.backgroundColor = '#f0f9ff';

                                setTimeout(() => {
                                    if (logradouroInput) logradouroInput.style.backgroundColor = '';
                                    if (cidadeInput) cidadeInput.style.backgroundColor = '';
                                    if (estadoSelect) estadoSelect.style.backgroundColor = '';
                                }, 2000);
                            } else {
                                if (logradouroInput) logradouroInput.value = '';
                                if (cidadeInput) cidadeInput.value = '';
                                alert('CEP não encontrado!');
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao buscar CEP:', error);
                            if (logradouroInput) logradouroInput.value = '';
                            if (cidadeInput) cidadeInput.value = '';
                            alert('Erro ao buscar CEP. Verifique sua conexão com a internet.');
                        });
                } else if (cep.length > 0) {
                    console.log('CEP incompleto:', cep);
                }
            });

            // Loading no submit
            const form = document.getElementById('empresaForm');
            const loading = document.getElementById('loading');

            form.addEventListener('submit', function() {
                loading.classList.add('show');
            });

            // Validação em tempo real
            const requiredInputs = document.querySelectorAll('input[required], select[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('error');
                    } else {
                        this.classList.remove('error');
                    }
                });

                input.addEventListener('input', function() {
                    if (this.classList.contains('error') && this.value.trim()) {
                        this.classList.remove('error');
                    }
                });
            });

            // Auto-hide success message
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        successAlert.remove();
                    }, 300);
                }, 5000);
            }
        });
    </script>
</body>

<?php
require_once 'footer.php';
?>

</html>