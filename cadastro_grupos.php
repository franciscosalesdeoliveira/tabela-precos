<?php
$titulo = "Cadastro de Grupos";
include_once 'connection.php';
include_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupo = $_POST['grupo'];

    $sql = "INSERT INTO grupos (nome) VALUES (:grupo)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo', $grupo);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

?>
<!-- Formulário para cadastrar grupos -->
<div class="container mt-3" id="cadastro_grupos">
    <form method="POST">
        <label for="grupo">Grupo:</label>
        <input type="text" name="grupo" id="grupo" required>
        <button type="submit">Cadastrar</button>
        <button type="reset">Limpar</button>
        <!-- <button><a href="listar_grupos.php" target="_blank">Listar Grupos</a></button> -->
        <button><a href="index.php">Página Inicial</a></button>
    </form>
</div>

<!-- Listagem de Grupos -->
<?php
$sql = "SELECT id, nome FROM grupos ORDER BY id";
$stmt = $pdo->query($sql);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="text-center m-2">Grupos</h2>
<div class="overflow-y-auto" style="max-height: 450px; max-width: 80%; margin: 0 auto;">
    <table cellpadding='8' class='table table-striped border border-black-3' style='max-width: 80%; margin: 0 auto;'>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th class="text-center">Ações</th>
        </tr>
        <?php foreach ($grupos as $grupo): ?>
            <tr>
                <td><?= $grupo['id'] ?></td>
                <td><?= htmlspecialchars($grupo['nome']) ?></td>
                <td>
                    <div class=" col-12 d-flex justify-content-between">
                        <a class="btn btn-primary p-1 bottons col-5" href="editar_grupo.php?id=<?= $grupo['id'] ?>">Editar</a>
                        <a class="btn btn-danger p-1  bottons col-5" href="excluir_grupo.php?id=<?= $grupo['id'] ?>"
                            onclick="return confirm('Tem certeza que deseja excluir este grupo e todos os seus produtos?');">Excluir</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
</div>
</table>