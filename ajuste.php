<?php
$titulo = "Cadastro de Empresa Temporária";
require_once 'connection.php'; // Inclui o arquivo de conexão com o banco de dados
require_once 'header.php'; // Inclui o cabeçalho HTML
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

    // Cadastrar empresa no banco
    if (isset($_POST['cadastrar_empresa'])) {
        $cnpj = $_POST['cnpj'];
        $razao_social = $_POST['nome'];
        $logradouro = $_POST['rua'];
        $numero = $_POST['numero'];
        $complemento = $_POST['complemento'];
        $bairro = $_POST['bairro'];
        $municipio = $_POST['cidade'];
        $uf = $_POST['estado'];
        $cep = $_POST['cep'];
        $descricao_situacao_cadastral = $_POST['sit'];
        $cnae_fiscal_descricao = $_POST['cnae'];

        try {
            $stmt = $pdo->prepare("INSERT INTO empresa (cnpj, razao_social, logradouro, numero, complemento, bairro, municipio, uf, cep, descricao_situacao_cadastral, cnae_fiscal_descricao) 
                           VALUES (:cnpj, :razao_social, :logradouro, :numero, :complemento, :bairro, :municipio, :uf, :cep, :descricao_situacao_cadastral, :cnae_fiscal_descricao)");

            $stmt->execute([
                ':cnpj' => $cnpj,
                ':razao_social' => $razao_social,
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
    <title>Cadastro de Empresa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        input[type="submit"] {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-buscar {
            background-color: #007bff;
            color: white;
        }

        .btn-cadastrar {
            background-color: #28a745;
            color: white;
        }

        .btn-limpar {
            background-color: #6c757d;
            color: white;
        }

        .btn-buscar:hover {
            background-color: #0056b3;
        }

        .btn-cadastrar:hover {
            background-color: #218838;
        }

        .btn-limpar:hover {
            background-color: #545b62;
        }
    </style>
</head>

<body>

    <form action="" method="post">
        <label for="cnpj">CNPJ:</label>
        <input type="text" id="cnpj" name="cnpj" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($_POST['cnpj'] ?? '') ?>" placeholder="Digite apenas números">

        <label for="nome">Razão Social:</label>
        <input type="text" id="nome" name="nome" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['razao_social'] ?? '') ?>" readonly>

        <label for="rua">Rua:</label>
        <input type="text" id="rua" name="rua" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['logradouro'] ?? '') ?>" readonly>

        <label for="numero">Número:</label>
        <input type="text" id="numero" name="numero" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['numero'] ?? '') ?>" readonly>

        <label for="complemento">Complemento:</label>
        <input type="text" id="complemento" name="complemento" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['complemento'] ?? '') ?>" readonly>

        <label for="bairro">Bairro:</label>
        <input type="text" id="bairro" name="bairro" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['bairro'] ?? '') ?>" readonly>

        <label for="cidade">Cidade:</label>
        <input type="text" id="cidade" name="cidade" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['municipio'] ?? '') ?>" readonly>

        <label for="estado">Estado:</label>
        <input type="text" id="estado" name="estado" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['uf'] ?? '') ?>" readonly>

        <label for="cep">CEP:</label>
        <input type="text" id="cep" name="cep" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['cep'] ?? '') ?>" readonly>

        <label for="sit">Situação:</label>
        <input type="text" id="sit" name="sit" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['descricao_situacao_cadastral'] ?? '') ?>" readonly>

        <label for="cnae">CNAE:</label>
        <input type="text" id="cnae" name="cnae" value="<?= $empresa_cadastrada ? '' : htmlspecialchars($empresa['cnae_fiscal_descricao'] ?? '') ?>" readonly>

        <div class="button-group">
            <input type="submit" name="buscar_cnpj" value="Buscar CNPJ" class="btn-buscar">

            <?php if ($empresa_encontrada && !$empresa_cadastrada): ?>
                <input type="submit" name="cadastrar_empresa" value="Cadastrar Empresa" class="btn-cadastrar">
            <?php endif; ?>

            <input type="submit" name="limpar_formulario" value="Limpar" class="btn-limpar">
        </div>
    </form>

</body>

</html>