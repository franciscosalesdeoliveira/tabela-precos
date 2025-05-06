<?php
$titulo = "Tabela de Preços";
require_once 'header.php';
require_once 'connection.php';

?>

<body>
    <div class="container mt-4">

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

            echo '<div id="grupoCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">';
            echo '<div class="carousel-inner">';

            $primeiro = true;
            foreach ($grupos as $nomeGrupo => $listaProdutos) {
                echo '<div class="carousel-item ' . ($primeiro ? 'active' : '') . '">';
                echo "<div class='text-center mb-3'><h3 class='mb-3 text-center' style='font-size: 30px; font-weight: bold; color: white;'>Grupo: " . htmlspecialchars($nomeGrupo) . "</h3></div>";
                echo "<div class='table-responsive-sm mx-auto' style='max-height: 100vh; max-width: 90%; overflow-y: auto;'>";

                echo "<table class='table table-striped border'>";

                echo "<thead><tr><th class='text-center' style='width: 80%;'>Produto</th><th style='width: 20%;'>Preço</th></tr></thead>";

                echo "<tbody>";
                foreach ($listaProdutos as $item) {
                    echo "<tr>";
                    echo "<td class='text-center'>" . htmlspecialchars($item['produto']) . "</td>";
                    echo "<td>R$ " . number_format($item['preco'], 2, ',', '.') . " </td>";

                    echo "</tr>";
                }
                echo "</tbody></table></div></div>";
                $primeiro = false;
            }
            echo '</div>';
        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }
        ?>

    </div>
</body>