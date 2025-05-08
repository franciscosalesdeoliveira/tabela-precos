<?php

include_once 'connection.php';

$arquivo = $_FILES['arquivo'];
// var_dump($arquivo);

$linhas_importadas = 0;
$linhas_falha = 0;
$produtos_nao_importados = " ";


if ($arquivo['type'] == "text/csv") {
    $dados_arquivo = fopen($arquivo['tmp_name'], "r");

    // Pular a primeira linha (cabeçalho)
    $primeira_linha = true;
    $tem_descricao = false;
    $indice_grupo_id = 2;
    $indice_preco = 3;

    while ($linha = fgetcsv($dados_arquivo, 1000, ";")) {
        if ($primeira_linha) {
            // Verifica a estrutura do cabeçalho para determinar se há coluna de descrição
            $tem_descricao = false;
            for ($i = 0; $i < count($linha); $i++) {
                $cabecalho = mb_strtolower(trim($linha[$i]));
                if ($cabecalho === 'descricao' || $cabecalho === 'descrição' || $cabecalho === 'descr') {
                    $tem_descricao = true;
                    $indice_descricao = $i;
                    // Ajusta os índices de grupo_id e preço
                    $indice_grupo_id = $i + 1;
                    $indice_preco = $i + 2;
                    break;
                }
            }
            $primeira_linha = false;
            continue; // Pula a primeira linha (cabeçalho)
        }

        // Converte todos os campos para UTF-8 se necessário
        array_walk_recursive($linha, 'converterParaUTF8');

        // Extrai os campos básicos que sempre existem
        $codigo = $linha[0] ?? null;
        $nome = $linha[1] ?? null;

        // Determina os valores de descrição, grupo_id e preço com base na estrutura detectada
        if ($tem_descricao) {
            // Se tiver coluna de descrição, usa os índices ajustados
            $descricao = isset($linha[$indice_descricao]) && !empty($linha[$indice_descricao]) ?
                $linha[$indice_descricao] : null;
            $grupo_id = isset($linha[$indice_grupo_id]) && !empty($linha[$indice_grupo_id]) ?
                (int)$linha[$indice_grupo_id] : null;
            $preco = isset($linha[$indice_preco]) ? str_replace(',', '.', $linha[$indice_preco]) : null;
        } else {
            // Se não tiver coluna de descrição (como no seu CSV original)
            $descricao = null;
            $grupo_id = isset($linha[2]) && !empty($linha[2]) ? (int)$linha[2] : null;
            $preco = isset($linha[3]) ? str_replace(',', '.', $linha[3]) : null;
        }

        // Valida o preço
        if ($preco === null || !is_numeric($preco)) {
            $preco = 0.00; // Define um valor padrão para preco caso esteja ausente ou inválido
        }

        // Prepara a query
        $query_produto = "INSERT INTO produtos (codigo, nome, descricao, grupo_id, preco) 
                          VALUES (:codigo, :nome, :descricao, :grupo_id, :preco)";

        $stmt = $pdo->prepare($query_produto);
        $stmt->bindValue(':codigo', $codigo);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':descricao', $descricao); // Sempre NULL neste caso
        $stmt->bindValue(':grupo_id', $grupo_id);
        $stmt->bindValue(':preco', (float)$preco); // Garante que seja tratado como float

        try {
            $stmt->execute();

            // Verifica se a inserção foi bem-sucedida
            if ($stmt->rowCount() > 0) {
                $linhas_importadas++;
            } else {
                $linhas_falha++;
                $produtos_nao_importados .= "ID: " . $codigo . " - Nome: " . $nome . "<br>";
            }
        } catch (PDOException $e) {
            $linhas_falha++;
            $produtos_nao_importados .= "Erro ao importar - ID: " . $codigo . " - Nome: " . $nome . " - Erro: " . $e->getMessage() . "<br>";
        }
    }

    echo "Linhas importadas: " . $linhas_importadas . "<br>";
    echo "Linhas com falha: " . $linhas_falha . "<br>";
    if ($tem_descricao) {
        echo "Estrutura detectada: CSV COM campo de descrição<br>";
    } else {
        echo "Estrutura detectada: CSV SEM campo de descrição<br>";
    }
    echo "Produtos nao importados: <br>" . $produtos_nao_importados;
} else {
    echo "Arquivo não é um CSV";
    exit;
}

// Função para converter o arquivo CSV para UTF-8
function converterParaUTF8(&$dados_arquivo)
{
    $dados_arquivo = mb_convert_encoding($dados_arquivo, 'UTF-8', 'ISO-8859-1');
}
