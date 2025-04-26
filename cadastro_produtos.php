<?php
$titulo = "Cadastro de Produtos";
include_once 'connection.php';
include_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupo = $_POST['grupo'];


    $sql = "INSERT INTO produtos (nome, descricao, grupo_id, preco)
VALUES  (:nome, :descricao, :grupo_id, :preco)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nome', $_POST['nome']);
    $stmt->bindValue(':descricao', $_POST['descricao']);
    $stmt->bindValue(':grupo_id', (int)$_POST['grupo_id']);
    $stmt->bindValue(':preco', (float)$_POST['preco']);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

?>
<!-- Formulário para cadastrar produtos -->
<div class="container mt-3" id="cadastro_produtos"></div>
<form method="POST">
    <label for="nome">Nome:</label>
    <input type="text" name="nome" id="nome" required>
    <label for="descricao">Descrição:</label>
    <input type="text" name="descricao" id="descricao">
    <label for="grupo_id">Grupo:</label>
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
    <label for="preco">Preço:</label>
    <input type="number" name="preco" id="preco" step="0.01" required>
    <button type="submit">Cadastrar</button>
    <button type="reset">Limpar</button>
    <!-- <button><a href="listar_grupos.php" target="_blank">Listar Grupos</a></button> -->
    <a href="index.php" style="display: inline-block; padding: 8px 12px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Página Inicial</a>
</form>
</div>

<!-- Listagem de Produtos -->
<?php
$sql = "SELECT id, nome, descricao, grupo_id, preco FROM produtos ORDER BY id";
$stmt = $pdo->query($sql);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="text-center m-2">Produtos</h2>
<div class="overflow-y-auto" style="max-height: 450px; max-width: 90%; margin: 0 auto;">
    <table cellpadding='8' class='table table-striped border border-black-3' style='max-width: 80%; margin: 0 auto;'>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Grupo</th>
            <th>Preço</th>
            <th class="text-center">Ações</th>
        </tr>
        <?php foreach ($produtos as $produto): ?>
            <tr>
                <td><?= $produto['id'] ?></td>
                <td><?= htmlspecialchars($produto['nome']) ?></td>
                <td><?= htmlspecialchars($produto['descricao']) ?></td>
                <td><?= $produto['grupo_id'] ?></td>
                <td><?= $produto['preco'] ?></td>
                <td>
                    <div class=" col-12 d-flex justify-content-between">
                        <a class="btn btn-primary p-1 bottons col-5" href="editar_produto.php?id=<?= $produto['id'] ?>">Editar</a>
                        <a class="btn btn-danger p-1  bottons col-5" href="excluir_produto.php?id=<?= $produto['id'] ?>"
                            onclick="return confirm('Tem certeza que deseja excluir este grupo e todos os seus produtos?');">Excluir</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
</div>
</table>