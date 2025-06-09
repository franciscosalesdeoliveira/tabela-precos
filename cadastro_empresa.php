<?php
$titulo = "Cadastro de Empresa";
require_once 'connection.php'; // Inclui o arquivo de conexão com o banco de dados
require_once 'header.php';
$empresa = []; // Armazena os dados retornados da API
$empresa_encontrada = false; // Flag para controlar se a empresa foi encontrada
$empresa_cadastrada = false; // Flag para controlar se a empresa foi cadastrada

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    // Buscar empresa na API
    if (isset($_POST['buscar_cnpj'])) {
        $cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');

        if (strlen($cnpj) != 14) {
            echo "<p style='color:red;'>CNPJ inválido. Deve conter 14 dígitos.</p>";
        } else {
            $endpoint = "https://brasilapi.com.br/api/cnpj/v1/$cnpj";

            $cURL = curl_init();
            curl_setopt_array($cURL, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT => 10
            ]);

            $response = curl_exec($cURL);
            $http_code = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
            curl_close($cURL);

            if ($http_code == 200) {
                $empresa = json_decode($response, true);
                if ($empresa && isset($empresa['cnpj'])) {
                    $empresa_encontrada = true;
                    echo "<p style='color:green;'>Empresa encontrada! Verifique os dados abaixo.</p>";
                }
            } else {
                $error = json_decode($response, true);
                echo "<p style='color:red;'>Erro: " . ($error['message'] ?? 'CNPJ não encontrado ou erro na consulta') . "</p>";
                // Limpar formulário em caso de erro
                $empresa = [];
                $empresa_encontrada = false;
            }
        }
    }

    function gerarUuidV4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff), // 32 bits
            mt_rand(0, 0xffff), // 16 bits
            mt_rand(0, 0x0fff) | 0x4000, // 16 bits com versão 4
            mt_rand(0, 0x3fff) | 0x8000, // 16 bits com variante RFC 4122
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }



    function arrayToHiddenInputs($data)
    {
        $html = '';
        foreach ($data as $key => $value) {
            $html .= "<input type='hidden' name='" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "' value='" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "'>\n";
        }
        return $html;
    }

    if (isset($_POST['cancelar_atualizacao'])) {
        // Você pode apenas limpar os dados e recarregar a página, por exemplo
        header("Location: cadastro_empresa.php");
        exit;
    }

    if (isset($_POST['cadastrar_empresa'])) {
        $cnpj = $_POST['cnpj'];
        $razao_social = $_POST['nome'];
        $nome_fantasia = $_POST['fantasia'];
        $rg_ie = $_POST['rg_ie'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $site = $_POST['site'] ?? '';
        $logradouro = $_POST['endereco'];
        $numero = $_POST['numero'];
        $complemento = $_POST['complemento'] ?? '';
        $bairro = $_POST['bairro'];
        $municipio = $_POST['cidade'];
        $uf = $_POST['estado'];
        $cep = $_POST['cep'];
        $descricao_situacao_cadastral = $_POST['sit'] ?? '';

        $descricao_situacao_cadastral = ($descricao_situacao_cadastral === 'ATIVA') ? 'S' : 'N';
        $cnae_fiscal_descricao = $_POST['cnae'] ?? '';
        $uuid = gerarUuidV4();
        $criado_por = 1;
        $atualizado_por = 1;

        if (isset($_POST['modo']) && $_POST['modo'] === 'atualizar') {
            $uuid = $_POST['uuid'];
            try {
                $stmt = $pdo->prepare("UPDATE empresas SET 
                cpf_cnpj = :cpf_cnpj, 
                razao_social = :razao_social, 
                fantasia = :fantasia, 
                rg_ie = :rg_ie, 
                telefone = :telefone, 
                email = :email, 
                site = :site, 
                logradouro = :logradouro, 
                numero = :numero, 
                complemento = :complemento, 
                bairro = :bairro, 
                cidade = :cidade, 
                estado = :estado, 
                cep = :cep, 
                ativo = :ativo, 
                cnae_fiscal_descricao = :cnae_fiscal_descricao,
                atualizado_por = :atualizado_por
            WHERE uuid = :uuid");

                $stmt->execute([
                    ':uuid' => $uuid,
                    ':cpf_cnpj' => $cnpj,
                    ':razao_social' => $razao_social,
                    ':fantasia' => $nome_fantasia,
                    ':rg_ie' => $rg_ie,
                    ':telefone' => $telefone,
                    ':email' => $email,
                    ':site' => $site,
                    ':logradouro' => $logradouro,
                    ':numero' => $numero,
                    ':complemento' => $complemento,
                    ':bairro' => $bairro,
                    ':cidade' => $municipio,
                    ':estado' => $uf,
                    ':cep' => $cep,
                    ':ativo' => $descricao_situacao_cadastral,
                    ':cnae_fiscal_descricao' => $cnae_fiscal_descricao,
                    ':atualizado_por' => $atualizado_por
                ]);

                header("Location: cadastro_empresa.php");
                exit;
            } catch (PDOException $e) {
                echo "<p style='color:red;'>Erro ao atualizar empresa: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
            }
        }

        try {
            $stmt_check = $pdo->prepare("SELECT uuid FROM empresas WHERE cpf_cnpj = :cnpj");
            $stmt_check->execute([':cnpj' => $cnpj]);
            $empresa_existente = $stmt_check->fetch();

            if ($empresa_existente) {
                echo "
    <script>
        if (confirm('A empresa já existe no banco de dados. Deseja atualizar os dados?')) {
            // Criar e enviar um formulário oculto com os dados
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const postData = " . json_encode($_POST) . ";
            for (const key in postData) {
                if (postData.hasOwnProperty(key)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = postData[key];
                    form.appendChild(input);
                }
            }

            const uuidInput = document.createElement('input');
            uuidInput.type = 'hidden';
            uuidInput.name = 'uuid';
            uuidInput.value = '" . htmlspecialchars($empresa_existente['uuid'], ENT_QUOTES, 'UTF-8') . "';
            form.appendChild(uuidInput);

            const modoInput = document.createElement('input');
            modoInput.type = 'hidden';
            modoInput.name = 'modo';
            modoInput.value = 'atualizar';
            form.appendChild(modoInput);

            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'cadastrar_empresa';
            submitInput.value = '1';
            form.appendChild(submitInput);

            document.body.appendChild(form);
            form.submit();
        } else {
            alert('Cadastro cancelado pelo usuário.');
        }
    </script>
    ";
            } else {
                $stmt = $pdo->prepare("INSERT INTO empresas (
                uuid, cpf_cnpj, razao_social, fantasia, rg_ie, telefone, email, site, 
                logradouro, numero, complemento, bairro, cidade, estado, cep, 
                ativo, cnae_fiscal_descricao, criado_por, atualizado_por
            ) VALUES (
                :uuid, :cpf_cnpj, :razao_social, :fantasia, :rg_ie, :telefone, :email, :site,
                :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :cep,
                :ativo, :cnae_fiscal_descricao, :criado_por, :atualizado_por
            )");

                $stmt->execute([
                    ':uuid' => $uuid,
                    ':cpf_cnpj' => $cnpj,
                    ':razao_social' => $razao_social,
                    ':fantasia' => $nome_fantasia,
                    ':rg_ie' => $rg_ie,
                    ':telefone' => $telefone,
                    ':email' => $email,
                    ':site' => $site,
                    ':logradouro' => $logradouro,
                    ':numero' => $numero,
                    ':complemento' => $complemento,
                    ':bairro' => $bairro,
                    ':cidade' => $municipio,
                    ':estado' => $uf,
                    ':cep' => $cep,
                    ':ativo' => $descricao_situacao_cadastral,
                    ':cnae_fiscal_descricao' => $cnae_fiscal_descricao,
                    ':criado_por' => $criado_por,
                    ':atualizado_por' => $atualizado_por
                ]);

                header("Location: cadastro_empresa.php");
                exit;
            }
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Erro ao cadastrar empresa: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }

    // LIMPAR FORMULÁRIO
    if (isset($_POST['limpar_formulario'])) {
        $empresa = [];
        $empresa_encontrada = false;
        $empresa_cadastrada = false;
        echo "<p style='color:blue;'>Formulário limpo!</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Empresa</title>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --bg-color: #f8f9fa;
            --text-color: #333;
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
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
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

        h1 {
            margin-bottom: 30px;
            text-align: center;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
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

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
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
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 20px;
        }

        .form-group {
            flex: 1 1 300px;
            margin-bottom: 15px;
        }

        .form-group.small {
            flex: 0 1 180px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .error {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        button,
        input[type="submit"] {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-buscar {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-buscar:hover {
            background-color: var(--primary-dark);
        }

        .btn-cadastrar {
            background-color: var(--success-color);
            color: white;
        }

        .btn-cadastrar:hover {
            background-color: #27ae60;
        }

        .btn-limpar {
            background-color: #6c757d;
            color: white;
        }

        .btn-limpar:hover {
            background-color: #545b62;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check input {
            width: auto;
        }

        .input-group {
            display: flex;
            gap: 5px;
            align-items: flex-end;
        }

        .input-group input {
            flex: 1;
        }

        .btn-search {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 15px;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 14px;
            transition: background-color 0.3s;
            height: 42px;
            /* Mesmo height do input */
        }

        .btn-search:hover {
            background-color: var(--primary-dark);
        }

        .btn-search:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .status-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }

        .status-message.success {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid var(--success-color);
            color: #27ae60;
            display: block;
        }

        .status-message.error {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid var(--error-color);
            color: #c0392b;
            display: block;
        }

        .loading {
            display: none;
            color: var(--primary-color);
            font-size: 14px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-group,
            .form-group.small {
                flex: 1 1 100%;
            }

            .button-group {
                flex-direction: column;
            }

            .button-group input {
                width: 100%;
            }

            .input-group {
                flex-direction: column;
                gap: 5px;
            }

            .btn-search {
                width: 100%;
                margin-top: 5px;
                height: auto;
            }
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
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                🏢 Sistema Administrativo
            </a>
            <nav class="nav-links">
                <a target="_blank" class="btn btn-primary" href="dashboard.php">Dashboard</a>
                <a target="_blank" class="btn btn-primary" href="listar_empresas.php">Listar Empresas</a>
                <a target="_blank" class="btn btn-primary" href="cadastro_usuarios.php">Cadastro de Usuários</a>
            </nav>
        </div>
    </header>


    <div class="container">
        <div class="card">
            <h2 class="section-title">Cadastro de Empresa</h2>

            <form method="POST" id="empresaForm">
                <div class="card">
                    <h3 class="section-title">Dados Básicos</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Razão Social:</label>
                            <input type="text" id="nome" name="nome" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['razao_social'] ?? '') ?>">
                            <div class="error" id="razaoSocialError"></div>
                        </div>
                        <div class="form-group">
                            <label for="fantasia">Nome Fantasia:</label>
                            <input type="text" id="fantasia" name="fantasia" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>" required>
                            <div class="error" id="fantasiaError"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cnpj">CNPJ</label>
                            <div class="input-group">
                                <input type="text" id="cnpj" name="cnpj" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($_POST['cnpj'] ?? '') ?>" placeholder="Digite apenas números" maxlength="18">
                                <button type="button" id="btnBuscarCnpj" class="btn-search" onclick="buscarCNPJ()">
                                    🔍 Buscar
                                </button>
                            </div>
                            <div class="loading" id="cnpjLoading">Buscando CNPJ...</div>
                            <div class="error" id="cnpjError"></div>
                        </div>
                        <div class="form-group">
                            <label for="rg_ie">RG/Inscrição Estadual</label>
                            <input type="text" id="rg_ie" name="rg_ie" maxlength="20">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['ddd_telefone_1'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                            <div class="error" id="emailError"></div>
                        </div>
                        <div class="form-group">
                            <label for="site">Site</label>
                            <input type="text" id="site" name="site">
                            <div class="error" id="siteError"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="section-title">Endereço</h3>
                    <div class="form-row">
                        <div class="form-group small">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['cep'] ?? '') ?>" maxlength="9">
                        </div>
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" id="endereco" name="endereco" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['logradouro'] ?? '') ?>" maxlength="100">
                        </div>
                        <div class="form-group small">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['numero'] ?? '') ?>" maxlength="20">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['complemento'] ?? '') ?>" maxlength="50">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" id="bairro" name="bairro" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['bairro'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['municipio'] ?? '') ?>" maxlength="100">
                        </div>
                        <div class="form-group small">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="">Selecione</option>
                                <option value="AC" <?= ($empresa['uf'] ?? '') == 'AC' ? 'selected' : '' ?>>AC</option>
                                <option value="AL" <?= ($empresa['uf'] ?? '') == 'AL' ? 'selected' : '' ?>>AL</option>
                                <option value="AP" <?= ($empresa['uf'] ?? '') == 'AP' ? 'selected' : '' ?>>AP</option>
                                <option value="AM" <?= ($empresa['uf'] ?? '') == 'AM' ? 'selected' : '' ?>>AM</option>
                                <option value="BA" <?= ($empresa['uf'] ?? '') == 'BA' ? 'selected' : '' ?>>BA</option>
                                <option value="CE" <?= ($empresa['uf'] ?? '') == 'CE' ? 'selected' : '' ?>>CE</option>
                                <option value="DF" <?= ($empresa['uf'] ?? '') == 'DF' ? 'selected' : '' ?>>DF</option>
                                <option value="ES" <?= ($empresa['uf'] ?? '') == 'ES' ? 'selected' : '' ?>>ES</option>
                                <option value="GO" <?= ($empresa['uf'] ?? '') == 'GO' ? 'selected' : '' ?>>GO</option>
                                <option value="MA" <?= ($empresa['uf'] ?? '') == 'MA' ? 'selected' : '' ?>>MA</option>
                                <option value="MT" <?= ($empresa['uf'] ?? '') == 'MT' ? 'selected' : '' ?>>MT</option>
                                <option value="MS" <?= ($empresa['uf'] ?? '') == 'MS' ? 'selected' : '' ?>>MS</option>
                                <option value="MG" <?= ($empresa['uf'] ?? '') == 'MG' ? 'selected' : '' ?>>MG</option>
                                <option value="PA" <?= ($empresa['uf'] ?? '') == 'PA' ? 'selected' : '' ?>>PA</option>
                                <option value="PB" <?= ($empresa['uf'] ?? '') == 'PB' ? 'selected' : '' ?>>PB</option>
                                <option value="PR" <?= ($empresa['uf'] ?? '') == 'PR' ? 'selected' : '' ?>>PR</option>
                                <option value="PE" <?= ($empresa['uf'] ?? '') == 'PE' ? 'selected' : '' ?>>PE</option>
                                <option value="PI" <?= ($empresa['uf'] ?? '') == 'PI' ? 'selected' : '' ?>>PI</option>
                                <option value="RJ" <?= ($empresa['uf'] ?? '') == 'RJ' ? 'selected' : '' ?>>RJ</option>
                                <option value="RN" <?= ($empresa['uf'] ?? '') == 'RN' ? 'selected' : '' ?>>RN</option>
                                <option value="RS" <?= ($empresa['uf'] ?? '') == 'RS' ? 'selected' : '' ?>>RS</option>
                                <option value="RO" <?= ($empresa['uf'] ?? '') == 'RO' ? 'selected' : '' ?>>RO</option>
                                <option value="RR" <?= ($empresa['uf'] ?? '') == 'RR' ? 'selected' : '' ?>>RR</option>
                                <option value="SC" <?= ($empresa['uf'] ?? '') == 'SC' ? 'selected' : '' ?>>SC</option>
                                <option value="SP" <?= ($empresa['uf'] ?? '') == 'SP' ? 'selected' : '' ?>>SP</option>
                                <option value="SE" <?= ($empresa['uf'] ?? '') == 'SE' ? 'selected' : '' ?>>SE</option>
                                <option value="TO" <?= ($empresa['uf'] ?? '') == 'TO' ? 'selected' : '' ?>>TO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="section-title">Informações Adicionais</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sit">Situação Cadastral</label>
                            <input type="text" id="sit" name="sit" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['descricao_situacao_cadastral'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="cnae">CNAE Principal</label>
                            <input type="text" id="cnae" name="cnae" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['cnae_fiscal_descricao'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <input type="submit" name="cadastrar_empresa" value="Cadastrar Empresa" class="btn-cadastrar" id="btnCadastrar">
                    <input type="reset" name="limpar_formulario" value="Limpar Formulário" class="btn-limpar" onclick="limparFormulario()">


                    <!-- <input type="reset" name="limpar_formulario" value="Limpar Formulário" class="btn-limpar"> -->
                </div>

                <!-- Campos hidden para envio via POST quando buscar CNPJ via JavaScript -->
                <input type="hidden" name="buscar_cnpj" id="hiddenBuscarCnpj">
            </form>
        </div>
    </div>


    <script>
        let buscandoCNPJ = false;

        document.addEventListener('DOMContentLoaded', function() {
            const cnpjInput = document.getElementById('cnpj');
            const btnBuscar = document.getElementById('btnBuscarCnpj');
            const loading = document.getElementById('cnpjLoading');

            // Formatação do CNPJ
            cnpjInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                this.value = value;

                // Habilitar/desabilitar botão de busca
                const cnpjLimpo = value.replace(/\D/g, '');
                btnBuscar.disabled = cnpjLimpo.length !== 14;
            });

            // Buscar CNPJ ao sair do campo (blur)
            cnpjInput.addEventListener('blur', function() {
                const cnpjLimpo = this.value.replace(/\D/g, '');
                if (cnpjLimpo.length === 14 && !buscandoCNPJ) {
                    buscarCNPJ();
                }
            });

            // Enter no campo CNPJ para buscar
            cnpjInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const cnpjLimpo = this.value.replace(/\D/g, '');
                    if (cnpjLimpo.length === 14) {
                        buscarCNPJ();
                    }
                }
            });

            // Validação de email em tempo real
            const emailInput = document.getElementById('email');
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                const emailError = document.getElementById('emailError');

                if (email && !isValidEmail(email)) {
                    emailError.textContent = 'Por favor, insira um email válido.';
                    this.style.borderColor = '#e74c3c';
                } else {
                    emailError.textContent = '';
                    this.style.borderColor = '#ddd';
                }
            });

            // Validação de site em tempo real
            const siteInput = document.getElementById('site');
            siteInput.addEventListener('blur', function() {
                const site = this.value.trim();
                const siteError = document.getElementById('siteError');

                if (site && !isValidUrl(site)) {
                    siteError.textContent = 'Por favor, insira uma URL válida (ex: https://exemplo.com.br).';
                    this.style.borderColor = '#e74c3c';
                } else {
                    siteError.textContent = '';
                    this.style.borderColor = '#ddd';
                }
            });

            function buscarCep(cep) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                        } else {
                            mostrarMensagem('CEP não encontrado!', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar CEP:', error);
                        mostrarMensagem('Erro ao buscar CEP. Tente novamente.', 'error');
                    });
            }

            // Formatação de CEP
            const cepInput = document.getElementById('cep');
            cepInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                this.value = value;
            });

            // Buscar CEP
            cepInput.addEventListener('blur', function() {
                const cep = this.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    buscarCep(cep);
                }
            });
        });

        // Formatação de telefone
        const telefoneInput = document.getElementById('telefone');
        telefoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            this.value = value;
        });

        // Validação do formulário antes do envio
        const form = document.getElementById('empresaForm');
        form.addEventListener('submit', function(e) {
            if (!validarFormulario()) {
                e.preventDefault();
                alert('Por favor, corrija os erros no formulário antes de continuar.');
            }
        });

        // Função para buscar CNPJ via API
        function buscarCNPJ() {
            if (buscandoCNPJ) return;

            const cnpjInput = document.getElementById('cnpj');
            const loading = document.getElementById('cnpjLoading');
            const btnBuscar = document.getElementById('btnBuscarCnpj');
            const cnpjError = document.getElementById('cnpjError');

            const cnpjLimpo = cnpjInput.value.replace(/\D/g, '');

            if (cnpjLimpo.length !== 14) {
                cnpjError.textContent = 'CNPJ deve conter exatamente 14 dígitos.';
                cnpjInput.style.borderColor = '#e74c3c';
                return;
            }

            buscandoCNPJ = true;
            loading.style.display = 'block';
            btnBuscar.disabled = true;
            btnBuscar.textContent = '⏳ Buscando...';
            cnpjError.textContent = '';
            cnpjInput.style.borderColor = '#ddd';

            // Fazer requisição para a API
            fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjLimpo}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('CNPJ não encontrado ou inválido');
                    }
                    return response.json();
                })
                .then(data => {
                    // Preencher os campos com os dados da API
                    preencherCamposEmpresa(data);

                    // Mostrar mensagem de sucesso
                    mostrarMensagem('Empresa encontrada! Verifique e complete os dados necessários.', 'success');
                })
                .catch(error => {
                    cnpjError.textContent = error.message;
                    cnpjInput.style.borderColor = '#e74c3c';
                    mostrarMensagem('Erro ao buscar CNPJ. Você pode preencher os dados manualmente.', 'error');
                })
                .finally(() => {
                    buscandoCNPJ = false;
                    loading.style.display = 'none';
                    btnBuscar.disabled = false;
                    btnBuscar.textContent = '🔍 Buscar';
                });
        }

        // Função para preencher os campos com dados da empresa
        function preencherCamposEmpresa(empresa) {
            document.getElementById('nome').value = empresa.razao_social || '';
            document.getElementById('fantasia').value = empresa.nome_fantasia || empresa.razao_social || '';
            document.getElementById('telefone').value = empresa.ddd_telefone_1 || '';
            document.getElementById('cep').value = empresa.cep || '';
            document.getElementById('endereco').value = empresa.logradouro || '';
            document.getElementById('numero').value = empresa.numero || '';
            document.getElementById('complemento').value = empresa.complemento || '';
            document.getElementById('bairro').value = empresa.bairro || '';
            document.getElementById('cidade').value = empresa.municipio || '';
            document.getElementById('estado').value = empresa.uf || '';
            document.getElementById('sit').value = empresa.descricao_situacao_cadastral || '';
            document.getElementById('cnae').value = empresa.cnae_fiscal_descricao || '';

            // Aplicar formatação nos campos preenchidos
            formatarCampo('telefone');
            formatarCampo('cep');
        }

        // Função para aplicar formatação em campos específicos
        function formatarCampo(campo) {
            const input = document.getElementById(campo);
            const event = new Event('input', {
                bubbles: true
            });
            input.dispatchEvent(event);
        }

        // Função para validar email
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Função para validar URL
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        }

        // Função para validar o formulário completo
        function validarFormulario() {
            let isValid = true;

            // Validar campos obrigatórios
            const camposObrigatorios = [{
                    id: 'nome',
                    nome: 'Razão Social'
                },
                {
                    id: 'fantasia',
                    nome: 'Nome Fantasia'
                },
                {
                    id: 'cnpj',
                    nome: 'CNPJ'
                },
                {
                    id: 'email',
                    nome: 'Email'
                }
            ];

            camposObrigatorios.forEach(campo => {
                const input = document.getElementById(campo.id);
                const value = input.value.trim();

                if (!value) {
                    input.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    input.style.borderColor = '#ddd';
                }
            });

            // Validar CNPJ
            const cnpjInput = document.getElementById('cnpj');
            const cnpjLimpo = cnpjInput.value.replace(/\D/g, '');
            if (cnpjLimpo.length !== 14) {
                cnpjInput.style.borderColor = '#e74c3c';
                document.getElementById('cnpjError').textContent = 'CNPJ deve conter 14 dígitos.';
                isValid = false;
            }

            // Validar email
            const emailInput = document.getElementById('email');
            if (emailInput.value && !isValidEmail(emailInput.value)) {
                emailInput.style.borderColor = '#e74c3c';
                document.getElementById('emailError').textContent = 'Email inválido.';
                isValid = false;
            }

            // Validar site se preenchido
            const siteInput = document.getElementById('site');
            if (siteInput.value && !isValidUrl(siteInput.value)) {
                siteInput.style.borderColor = '#e74c3c';
                document.getElementById('siteError').textContent = 'URL inválida.';
                isValid = false;
            }

            return isValid;
        }

        // Função para limpar o formulário
        function limparFormulario() {
            // Limpar todos os campos
            document.getElementById('empresaForm').reset();

            // Limpar mensagens de erro
            const errorElements = document.querySelectorAll('.error');
            errorElements.forEach(error => error.textContent = '');

            // Resetar bordas dos campos
            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => input.style.borderColor = '#ddd');

            // Focar no campo CNPJ
            document.getElementById('cnpj').focus();

            // Mostrar mensagem
            mostrarMensagem('Formulário limpo! Você pode inserir dados manualmente ou buscar por CNPJ.', 'success');
        }

        // Função para mostrar mensagens
        function mostrarMensagem(texto, tipo) {
            // Remover mensagens existentes
            const mensagensExistentes = document.querySelectorAll('.mensagem-temp');
            mensagensExistentes.forEach(msg => msg.remove());

            // Criar nova mensagem
            const mensagem = document.createElement('div');
            mensagem.className = `mensagem-temp status-message ${tipo}`;
            mensagem.textContent = texto;
            mensagem.style.display = 'block';

            // Inserir mensagem no início do container
            const container = document.querySelector('.container');
            container.insertBefore(mensagem, container.firstChild);

            // Remover mensagem após 5 segundos
            setTimeout(() => {
                mensagem.remove();
            }, 5000);
        }

        // Função para habilitar cadastro manual
        function habilitarCadastroManual() {
            mostrarMensagem('Modo de cadastro manual ativado. Preencha todos os campos obrigatórios.', 'success');
            document.getElementById('nome').focus();
        }

        // Adicionar dica para cadastro manual
        document.addEventListener('DOMContentLoaded', function() {
            const cnpjInput = document.getElementById('cnpj');
            const dica = document.createElement('small');
            dica.style.color = '#666';
            dica.style.fontSize = '12px';
            dica.style.display = 'block';
            dica.style.marginTop = '5px';
            dica.innerHTML = '💡 <em>Dica: Você pode buscar por CNPJ ou preencher manualmente todos os campos obrigatórios.</em>';

            cnpjInput.parentNode.appendChild(dica);
        });
    </script>
</body>
<?php
require_once 'footer.php';
?>

</html>