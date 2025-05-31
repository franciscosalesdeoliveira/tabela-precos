<?php
$titulo = "Cadastro de Empresa";
require_once 'connection.php'; // Inclui o arquivo de conexão com o banco de dados
require_once 'header.php';


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

    // Cadastrar empresa no banco
    if (isset($_POST['cadastrar_empresa'])) {
        $cnpj = $_POST['cnpj'];
        $razao_social = $_POST['nome'];
        $rg_ie = $_POST['rg_ie'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $logradouro = $_POST['rua'];
        $numero = $_POST['numero'];
        $complemento = $_POST['complemento'];
        $bairro = $_POST['bairro'];
        $municipio = $_POST['cidade'];
        $uf = $_POST['estado'];
        $cep = $_POST['cep'];
        $descricao_situacao_cadastral = $_POST['sit']; //arrumar 
        $cnae_fiscal_descricao = $_POST['cnae'];
        

        try {
            $stmt = $pdo->prepare("INSERT INTO empresa (cnpj, razao_social, rg_ie, telefone, email, logradouro, numero, complemento, bairro, municipio, uf, cep, descricao_situacao_cadastral, cnae_fiscal_descricao) 
                           VALUES (:cnpj, :razao_social, :rg_ie, :telefone, :email, :logradouro, :numero, :complemento, :bairro, :municipio, :uf, :cep, :descricao_situacao_cadastral, :cnae_fiscal_descricao)");

            $stmt->execute([
                ':cnpj' => $cnpj,
                ':razao_social' => $razao_social,
                ':rg_ie' => $rg_ie,
                ':telefone' => $telefone,
                ':email' => $email,                
                ':logradouro' => $logradouro,
                ':numero' => $numero,
                ':complemento' => $complemento,
                ':bairro' => $bairro,
                ':municipio' => $municipio,
                ':uf' => $uf,
                ':cep' => $cep,
                ':descricao_situacao_cadastral' => $descricao_situacao_cadastral,
                ':cnae_fiscal_descricao' => $cnae_fiscal_descricao
            ]);

            echo "<p style='color:green;'>Empresa cadastrada com sucesso!</p>";
            $empresa_cadastrada = true;
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

        button {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        button.primary {
            background-color: var(--primary-color);
            color: white;
        }

        button.primary:hover {
            background-color: var(--primary-dark);
        }

        button.secondary {
            background-color: #e9ecef;
            color: #495057;
        }

        button.secondary:hover {
            background-color: #dee2e6;
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
        }

        .btn-search:hover {
            background-color: var(--primary-dark);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
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

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-group,
            .form-group.small {
                flex: 1 1 100%;
            }

            .buttons {
                flex-direction: column;
            }

            button {
                width: 100%;
            }

            .input-group {
                flex-direction: column;
            }

            .btn-search {
                width: 100%;
                margin-top: 5px;
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

            <form id="empresaForm">
                <input type="hidden" id="uuid" name="uuid">
                <input type="hidden" id="id" name="id">

                <div class="card">
                    <h3 class="section-title">Dados Básicos</h3>
                    <div id="statusMessage" class="status-message"></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="razaoSocial">Razão Social *</label>
                            <input type="text" id="razaoSocial" name="razao_social" maxlength="120" required>
                            <div class="error" id="razaoSocialError"></div>
                        </div>
                        <div class="form-group">
                            <label for="fantasia">Nome Fantasia *</label>
                            <input type="text" id="fantasia" name="fantasia" maxlength="100" required>
                            <div class="error" id="fantasiaError"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpfCnpj">CPF/CNPJ</label>
                            <div class="input-group">
                                <input type="text" id="cpfCnpj" name="cpf_cnpj" maxlength="20" placeholder="Digite o CNPJ para buscar dados">
                                <button type="button" id="btnBuscarCNPJ" class="btn-search">Buscar CNPJ</button>
                            </div>
                            <div class="error" id="cpfCnpjError"></div>
                        </div>
                        <div class="form-group">
                            <label for="rgIe">RG/Inscrição Estadual</label>
                            <input type="text" id="rgIe" name="rg_ie" maxlength="20">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" maxlength="30">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" maxlength="150" required>
                            <div class="error" id="emailError"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="section-title">Endereço</h3>
                    <div class="form-row">
                        <div class="form-group small">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" id="endereco" name="endereco" maxlength="100">
                        </div>
                        <div class="form-group small">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" maxlength="20">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" id="bairro" name="bairro" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" maxlength="100">
                            <input type="hidden" id="cidadeid" name="cidadeid">
                        </div>
                        <div class="form-group small">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="">Selecione</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="section-title">Status da Empresa</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="ativo" name="ativo" checked>
                                <label for="ativo">Empresa Ativa</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="buttons">
                    <button type="button" class="secondary" id="btnLimpar">Limpar</button>
                    <button type="submit" class="primary" id="btnSalvar">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gerar UUID ao carregar a página
            document.getElementById('uuid').value = generateUUID();

            // Manipuladores de eventos
            document.getElementById('empresaForm').addEventListener('submit', handleSubmit);
            document.getElementById('btnLimpar').addEventListener('click', limparFormulario);
            document.getElementById('cep').addEventListener('blur', buscarCep);
            document.getElementById('cpfCnpj').addEventListener('blur', function() {
                validarCpfCnpj();

                // Se for CNPJ, tentar buscar dados
                const cpfCnpj = this.value.replace(/\D/g, '');
                if (cpfCnpj.length === 14) {
                    buscarDadosCNPJ(cpfCnpj);
                }
            });

            // Adicionar botão para busca manual de CNPJ
            document.getElementById('btnBuscarCNPJ').addEventListener('click', function() {
                const cpfCnpj = document.getElementById('cpfCnpj').value.replace(/\D/g, '');
                if (cpfCnpj.length === 14) {
                    buscarDadosCNPJ(cpfCnpj);
                } else {
                    alert('Por favor, insira um CNPJ válido.');
                }
            });

            // Atualizar status ativo/inativo
            document.getElementById('ativo').addEventListener('change', function() {
                this.value = this.checked ? 'S' : 'N';
            });
        });

        // Função para gerar UUID v4
        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        // Buscar CEP
        function buscarCep() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');

            if (cep.length !== 8) return;

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('endereco').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                    }
                })
                .catch(error => console.error('Erro ao buscar CEP:', error));
        }

        // Buscar dados do CNPJ
        function buscarDadosCNPJ(cnpj) {
            // Exibir mensagem de carregamento
            const statusMessage = document.getElementById('statusMessage');
            statusMessage.className = 'status-message';
            statusMessage.innerHTML = '<div class="loading"></div> Consultando CNPJ...';
            statusMessage.style.display = 'block';

            // Formatar o CNPJ para envio
            const cnpjFormatado = cnpj.replace(/[^\d]/g, '');

            // IMPORTANTE: Em um ambiente real, esta chamada deve ser feita através do seu backend
            // pois a API ReceitaWS não permite requisições diretas do navegador por questões de CORS
            // 
            // Para fins de demonstração, vamos usar dados simulados para alguns CNPJs conhecidos

            // Simular um pequeno atraso para parecer uma chamada real
            setTimeout(() => {
                // Verificar se é um CNPJ para simulação
                const cnpjData = getMockCNPJData(cnpjFormatado);

                if (cnpjData) {
                    // Preencher os campos com os dados simulados
                    preencherDadosEmpresa(cnpjData);

                    // Exibir mensagem de sucesso
                    statusMessage.className = 'status-message success';
                    statusMessage.textContent = 'Dados do CNPJ recuperados com sucesso! (Dados simulados)';
                } else {
                    // Para CNPJs não conhecidos, exibir mensagem
                    statusMessage.className = 'status-message error';
                    statusMessage.textContent = 'CNPJ não encontrado na base de dados. Para demonstração, tente um dos seguintes CNPJs: 00000000000191, 33000167000101, 60746948000112';
                }

                // Esconder a mensagem após 8 segundos
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 8000);
            }, 1500);

            /* IMPLEMENTAÇÃO REAL (necessita de backend)
            fetch('/api/consulta-cnpj?cnpj=' + cnpjFormatado, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'OK') {
                    preencherDadosEmpresa(data);
                    statusMessage.className = 'status-message success';
                    statusMessage.textContent = 'Dados do CNPJ recuperados com sucesso!';
                } else {
                    throw new Error(data.message || 'CNPJ não encontrado');
                }
            })
            .catch(error => {
                console.error('Erro ao buscar dados do CNPJ:', error);
                statusMessage.className = 'status-message error';
                statusMessage.textContent = 'Erro ao consultar CNPJ: ' + error.message;
            });
            */
        }

        // Preencher dados da empresa nos campos do formulário
        function preencherDadosEmpresa(data) {
            document.getElementById('razaoSocial').value = data.nome || '';
            document.getElementById('fantasia').value = data.fantasia || data.nome || '';
            document.getElementById('telefone').value = data.telefone || '';
            document.getElementById('email').value = data.email || '';

            // Preencher dados de endereço
            document.getElementById('cep').value = data.cep || '';
            document.getElementById('endereco').value = data.logradouro || '';
            document.getElementById('numero').value = data.numero || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.municipio || '';
            document.getElementById('estado').value = data.uf || '';
        }



        // Validar CPF/CNPJ
        function validarCpfCnpj() {
            const cpfCnpj = document.getElementById('cpfCnpj').value.replace(/\D/g, '');
            const errorElement = document.getElementById('cpfCnpjError');
            errorElement.textContent = '';

            if (cpfCnpj.length === 0) return;

            if (cpfCnpj.length === 11) {
                if (!validarCPF(cpfCnpj)) {
                    errorElement.textContent = 'CPF inválido';
                }
            } else if (cpfCnpj.length === 14) {
                if (!validarCNPJ(cpfCnpj)) {
                    errorElement.textContent = 'CNPJ inválido';
                }
            } else {
                errorElement.textContent = 'Formato de CPF/CNPJ inválido';
            }
        }

        // Validar CPF
        function validarCPF(cpf) {
            // Elimina CPFs inválidos conhecidos
            if (
                cpf === '00000000000' ||
                cpf === '11111111111' ||
                cpf === '22222222222' ||
                cpf === '33333333333' ||
                cpf === '44444444444' ||
                cpf === '55555555555' ||
                cpf === '66666666666' ||
                cpf === '77777777777' ||
                cpf === '88888888888' ||
                cpf === '99999999999'
            ) {
                return false;
            }

            // Validação do primeiro dígito
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }

            let resto = 11 - (soma % 11);
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) return false;

            // Validação do segundo dígito
            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }

            resto = 11 - (soma % 11);
            if (resto === 10 || resto === 11) resto = 0;

            return resto === parseInt(cpf.charAt(10));
        }

        // Validar CNPJ
        function validarCNPJ(cnpj) {
            // Elimina CNPJs inválidos conhecidos
            if (
                cnpj === '00000000000000' ||
                cnpj === '11111111111111' ||
                cnpj === '22222222222222' ||
                cnpj === '33333333333333' ||
                cnpj === '44444444444444' ||
                cnpj === '55555555555555' ||
                cnpj === '66666666666666' ||
                cnpj === '77777777777777' ||
                cnpj === '88888888888888' ||
                cnpj === '99999999999999'
            ) {
                return false;
            }

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

        // Enviar formulário
        function handleSubmit(event) {
            event.preventDefault();

            if (!validarFormulario()) {
                return;
            }

            // Pegar todos os dados do formulário
            const formData = new FormData(document.getElementById('empresaForm'));
            const dadosEmpresa = Object.fromEntries(formData.entries());

            // Adicionar campos de auditoria
            const dataAtual = new Date().toISOString();
            const usuarioAtual = 'usuario_logado'; // Substituir pelo usuário da sessão

            dadosEmpresa.criado_em = dataAtual;
            dadosEmpresa.criado_por = usuarioAtual;
            dadosEmpresa.atualizado_em = dataAtual;
            dadosEmpresa.atualizado_por = usuarioAtual;
            dadosEmpresa.ativo = document.getElementById('ativo').checked ? 'S' : 'N';

            // Simulação de envio para o servidor
            console.log('Dados a serem enviados:', dadosEmpresa);

            // Aqui você faria o envio para o backend
            // fetch('/api/empresas', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //     },
            //     body: JSON.stringify(dadosEmpresa)
            // })
            // .then(response => response.json())
            // .then(data => {
            //     alert('Empresa cadastrada com sucesso!');
            //     limparFormulario();
            // })
            // .catch(error => {
            //     console.error('Erro ao cadastrar empresa:', error);
            //     alert('Erro ao cadastrar empresa. Verifique o console para mais detalhes.');
            // });

            alert('Empresa cadastrada com sucesso! (Simulação)');
        }

        // Validar formulário
        function validarFormulario() {
            let isValid = true;

            // Limpar erros anteriores
            document.querySelectorAll('.error').forEach(el => el.textContent = '');

            // Validar razão social
            const razaoSocial = document.getElementById('razaoSocial').value.trim();
            if (!razaoSocial) {
                document.getElementById('razaoSocialError').textContent = 'Razão Social é obrigatória';
                isValid = false;
            }

            // Validar nome fantasia
            const fantasia = document.getElementById('fantasia').value.trim();
            if (!fantasia) {
                document.getElementById('fantasiaError').textContent = 'Nome Fantasia é obrigatório';
                isValid = false;
            }

            // Validar email
            const email = document.getElementById('email').value.trim();
            if (!email) {
                document.getElementById('emailError').textContent = 'Email é obrigatório';
                isValid = false;
            } else if (!validateEmail(email)) {
                document.getElementById('emailError').textContent = 'Email inválido';
                isValid = false;
            }

            // Validar CPF/CNPJ se foi preenchido
            const cpfCnpj = document.getElementById('cpfCnpj').value.replace(/\D/g, '');
            if (cpfCnpj) {
                if (cpfCnpj.length === 11 && !validarCPF(cpfCnpj)) {
                    document.getElementById('cpfCnpjError').textContent = 'CPF inválido';
                    isValid = false;
                } else if (cpfCnpj.length === 14 && !validarCNPJ(cpfCnpj)) {
                    document.getElementById('cpfCnpjError').textContent = 'CNPJ inválido';
                    isValid = false;
                } else if (cpfCnpj.length !== 11 && cpfCnpj.length !== 14) {
                    document.getElementById('cpfCnpjError').textContent = 'Formato de CPF/CNPJ inválido';
                    isValid = false;
                }
            }

            return isValid;
        }

        // Validar email
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Limpar formulário
        function limparFormulario() {
            document.getElementById('empresaForm').reset();
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            document.getElementById('uuid').value = generateUUID();
            document.getElementById('id').value = '';
            document.getElementById('ativo').checked = true;
        }
    </script>
</body>

</html>