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




    // Cadastrar empresa no banco
    if (isset($_POST['cadastrar_empresa'])) {
        $cnpj = $_POST['cnpj'];
        $razao_social = $_POST['nome'];
        $nome_fantasia = $_POST['fantasia'];
        $rg_ie = $_POST['rg_ie'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $logradouro = $_POST['endereco'];
        $numero = $_POST['numero'];
        $complemento = $_POST['complemento'] ?? '';
        $bairro = $_POST['bairro'];
        $municipio = $_POST['cidade'];
        $uf = $_POST['estado'];
        $cep = $_POST['cep'];
        $descricao_situacao_cadastral = $_POST['sit'] ?? '';

        if ($descricao_situacao_cadastral != 'ATIVA') {
            $descricao_situacao_cadastral = 'N';
        } else {
            $descricao_situacao_cadastral = 'S';
        }

        $cnae_fiscal_descricao = $_POST['cnae'] ?? '';
        $uuid = gerarUuidV4();

        // ADICIONAR CAMPOS OBRIGATÓRIOS QUE ESTÃO FALTANDO:
        $criado_por = 1; // ID do usuário que está criando (você pode pegar da sessão)
        $atualizado_por = 1; // ID do usuário que está atualizando

        // Se você tem sistema de login, use algo como:
        // $criado_por = $_SESSION['usuario_id'] ?? 1;
        // $atualizado_por = $_SESSION['usuario_id'] ?? 1;

        try {
            $stmt = $pdo->prepare("INSERT INTO empresas (
            uuid, 
            cpf_cnpj, 
            razao_social, 
            fantasia, 
            rg_ie, 
            telefone, 
            email, 
            logradouro, 
            numero, 
            complemento, 
            bairro, 
            cidade, 
            estado, 
            cep, 
            ativo, 
            cnae_fiscal_descricao,
            criado_por,
            atualizado_por,
        ) VALUES (
            :uuid, 
            :cpf_cnpj, 
            :razao_social, 
            :fantasia, 
            :rg_ie, 
            :telefone, 
            :email, 
            :logradouro, 
            :numero, 
            :complemento, 
            :bairro, 
            :cidade, 
            :estado, 
            :cep, 
            :ativo, 
            :cnae_fiscal_descricao,
            :criado_por,
            :atualizado_por
        )");

            $stmt->execute([
                ':uuid' => $uuid,
                ':cpf_cnpj' => $cnpj,
                ':razao_social' => $razao_social,
                ':fantasia' => $nome_fantasia,
                ':rg_ie' => $rg_ie,
                ':telefone' => $telefone,
                ':email' => $email,
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

            echo "<p style='color:green;'>Empresa cadastrada com sucesso!</p>";
            $empresa_cadastrada = true;

            // Limpar dados após cadastro bem-sucedido
            $empresa = [];
            $empresa_encontrada = false;
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Erro ao cadastrar empresa: " . $e->getMessage() . "</p>";
        }
    }


    // Limpar formulário
    if (isset($_POST['limpar_formulario'])) {
        $empresa = [];
        $empresa_encontrada = false;
        $empresa_cadastrada = false;
        echo "<p style='color:blue;'>Formulário limpo!</p>";
    }
    // Redirecionar após salvar
    header("Location: cadastro_empresa.php");
    exit;
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
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
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
                            <input type="text" id="fantasia" name="fantasia" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>">
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
                            <input type="text" id="sit" name="sit" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['descricao_situacao_cadastral'] ?? '') ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="cnae">CNAE Principal</label>
                            <input type="text" id="cnae" name="cnae" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['cnae_fiscal_descricao'] ?? '') ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <?php if ($empresa_encontrada && !$empresa_cadastrada): ?>
                        <input type="submit" name="cadastrar_empresa" value="Cadastrar Empresa" class="btn-cadastrar">
                    <?php endif; ?>


                    <input type="reset" name="limpar_formulario" value="Limpar Formulário" class="btn-limpar">
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
                if (cnpjLimpo.length === 14 && validarCNPJ(cnpjLimpo)) {
                    buscarCNPJ();
                }
            });

            // Buscar CNPJ ao pressionar Enter
            cnpjInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const cnpjLimpo = this.value.replace(/\D/g, '');
                    if (cnpjLimpo.length === 14 && validarCNPJ(cnpjLimpo)) {
                        buscarCNPJ();
                    }
                }
            });

            // Formatação do CEP
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

        function buscarCNPJ() {
            if (buscandoCNPJ) return;

            const cnpjInput = document.getElementById('cnpj');
            const btnBuscar = document.getElementById('btnBuscarCnpj');
            const loading = document.getElementById('cnpjLoading');
            const cnpjError = document.getElementById('cnpjError');

            const cnpj = cnpjInput.value.replace(/\D/g, '');

            if (cnpj.length !== 14) {
                cnpjError.textContent = 'CNPJ deve conter 14 dígitos';
                return;
            }

            if (!validarCNPJ(cnpj)) {
                cnpjError.textContent = 'CNPJ inválido';
                return;
            }

            cnpjError.textContent = '';
            buscandoCNPJ = true;
            btnBuscar.disabled = true;
            btnBuscar.textContent = 'Buscando...';
            loading.style.display = 'block';

            fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('CNPJ não encontrado');
                    }
                    return response.json();
                })
                .then(data => {
                    preencherDadosEmpresa(data);
                    mostrarMensagem('Empresa encontrada! Verifique os dados abaixo.', 'success');
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarMensagem('CNPJ não encontrado ou erro na consulta', 'error');
                    limparCamposEmpresa();
                })
                .finally(() => {
                    buscandoCNPJ = false;
                    btnBuscar.disabled = false;
                    btnBuscar.textContent = '🔍 Buscar';
                    loading.style.display = 'none';
                });
        }

        function preencherDadosEmpresa(empresa) {
            document.getElementById('nome').value = empresa.razao_social || '';
            document.getElementById('fantasia').value = empresa.nome_fantasia || '';
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

            // Mostrar botão de cadastrar
            mostrarBotaoCadastrar();
        }

        function limparCamposEmpresa() {
            const campos = ['nome', 'fantasia', 'telefone', 'cep', 'endereco', 'numero',
                'complemento', 'bairro', 'cidade', 'sit', 'cnae'
            ];
            campos.forEach(campo => {
                document.getElementById(campo).value = '';
            });
            document.getElementById('estado').selectedIndex = 0;
        }

        function mostrarBotaoCadastrar() {
            const buttonGroup = document.querySelector('.button-group');
            const btnCadastrar = document.querySelector('input[name="cadastrar_empresa"]');

            if (!btnCadastrar) {
                const newBtn = document.createElement('input');
                newBtn.type = 'submit';
                newBtn.name = 'cadastrar_empresa';
                newBtn.value = 'Cadastrar Empresa';
                newBtn.className = 'btn-cadastrar';
                buttonGroup.insertBefore(newBtn, buttonGroup.firstChild);
            }
        }

        function mostrarMensagem(texto, tipo) {
            // Remove mensagens existentes
            const mensagensAnteriores = document.querySelectorAll('.alert-message');
            mensagensAnteriores.forEach(msg => msg.remove());

            const div = document.createElement('div');
            div.className = `alert-message ${tipo}`;
            div.style.cssText = `
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
                ${tipo === 'success' ? 
                    'background-color: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #27ae60;' : 
                    'background-color: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #c0392b;'}
            `;
            div.textContent = texto;

            const container = document.querySelector('.container');
            container.insertBefore(div, container.firstChild);

            // Remove a mensagem após 5 segundos
            setTimeout(() => {
                if (div.parentNode) {
                    div.remove();
                }
            }, 5000);
        }

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

        // Validação de CNPJ
        function validarCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');

            if (cnpj.length !== 14) return false;

            // Elimina CNPJs inválidos conhecidos
            if (/^(\d)\1+$/.test(cnpj)) return false;

            // Validação do primeiro dígito
            let tamanho = cnpj.length - 2;
            let numeros = cnpj.substring(0, tamanho);
            let digitos = cnpj.substring(tamanho);
            let soma = 0;
            let pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }

            let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(0))) return false;

            // Validação do segundo dígito
            tamanho = tamanho + 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }

            resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            return resultado === parseInt(digitos.charAt(1));

        }
    </script>
</body>

</html>