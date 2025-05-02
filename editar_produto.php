<?php
$titulo = "Editar Produto";
require_once "connection.php";
require_once "header.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Atualiza o produto
    $id = $_POST["id"];
    $novoNome = trim($_POST["nome"]);

    $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, grupo_id = :grupo_id, preco = :preco WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":nome", $novoNome);
    $stmt->bindParam(":descricao", $_POST["descricao"]);
    $stmt->bindParam(":grupo_id", $_POST["grupo_id"], PDO::PARAM_INT);
    $stmt->bindParam(":preco", $_POST["preco"], PDO::PARAM_STR);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: cadastro_produtos.php");
        exit;
    } else {
        echo "Erro ao atualizar.";
    }
} else {
    // Carrega os dados do produto
    if (!isset($_GET["id"])) {
        echo "Produto não informado.";
        exit;
    }

    $id = $_GET["id"];
    $sql = "SELECT nome, descricao, grupo_id, preco FROM produtos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        echo "Produto não encontrado.";
        exit;
    }
}
?>


<body>
    <section id="editar_produto">
        <div class="text-center texto_editar_produto">
            <h2>Editar Produto</h2>
        </div>

        <div class="editar_produto mt-3 ">
            <div class="alter_itens" style="border: 1px solid darkslateblue;">
                <form method="post">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="editar_itens">
                        <label>Nome:</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
                    </div>
                    <div class="editar_itens">
                        <label>Descrição:</label>
                        <input type="text" name="descricao" value="<?= htmlspecialchars($produto['descricao']) ?>">
                    </div>
                    <div>
                        <label class="editar_itens">Grupo:</label>
                        <select name="grupo_id" id="grupo_id" required>
                            <?php
                            $sql = "SELECT id, nome FROM grupos ORDER BY id";
                            $stmt = $pdo->query($sql);
                            $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($grupos as $grupo) {
                                echo "<option value='{$grupo['id']}'>{$grupo['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="editar_itens">
                        <label>Preço:</label>
                        <input type="number" name="preco" value="<?= $produto['preco'] ?>" step="0.01" required>
                    </div>
            </div>
            </form>
            <div class="salvar_alter">
                <button class="btn btn-success mt-2" type="submit">Salvar</button>
                
            </div>
        </div>
    </section>]
</body>