<?php
$titulo = "Tabela de Preços";
include_once 'header.php';
include_once 'connection.php';
// include_once 'limitar_grupo.php';
?>

<style>
    tr:hover td {

        background-image: linear-gradient(to right, rgb(18, 73, 121), rgb(19, 33, 39));
        transform: scale(1.013);
        /* cuidado: esse transform ainda não funciona bem em <td> diretamente */
        box-shadow: 2px 2px 12px rgba(0, 0, 0, 0.2), -1px -1px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease-in;
        color: white;
        font-weight: bold;
    }


    /* .tr-hover {
        background-color: #f5f5f5;
        transform: scale(1.02);
        box-shadow: 2px 2px 12px rgba(0, 0, 0, 0.2), -1px -1px 8px rgba(0, 0, 0, 0.2);
    } */
</style>

<body>



    <div class="tabela-precos">
        <?php
        try {
            $limiteGrupo = isset($_GET['limite']) && is_numeric($_GET['limite']) ? (int)$_GET['limite'] : 5;
            $sql = "SELECT produto, preco, grupo
                FROM vw_produtos_com_numero
                WHERE row_num <= :limite
                ORDER BY grupo, produto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limite', $limiteGrupo, PDO::PARAM_INT);
            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $grupos = [];
            foreach ($produtos as $produto) {
                $grupos[$produto['grupo']][] = $produto;
            }
            foreach ($grupos as $nomeGrupo => $listaProdutos) {
                echo "<div class='grupo'> <h3>Grupo: " . htmlspecialchars($nomeGrupo) . "</h3></div>";
                //um <div> com rolagem e responsividade
                echo "<div class='table-responsive-sm' style='max-height: 300px; max-width: 80%; margin: 0 auto; overflow-y: auto;'>";
                echo "<table class='table table-striped' border='2' cellpadding='8'>";
                echo "<thead><tr><th class='text-center' style='width: 80%;'>Produto</th><th style='width: 20%;'>Preço</th></tr></thead>";
                echo "<tbody>";
                foreach ($listaProdutos as $item) {
                    echo "<tr>";
                    echo "<td class='text-center'>" . htmlspecialchars($item['produto']) . "</td>";
                    echo "<td '>R$ " . number_format($item['preco'], 2, ',', '.') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>"; // Fecha o div de rolagem
            }
            echo "</table><br>";
        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }
        ?>
    </div>

</body>

</html>