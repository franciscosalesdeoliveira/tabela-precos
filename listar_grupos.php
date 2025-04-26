<!--NÃO USANDO POR ENQUANTO-->
<?php
$titulo = "Listar Grupos";
require_once "connection.php";
require_once "header.php";

$sql = "SELECT id, nome FROM grupos ORDER BY id";
$stmt = $pdo->query($sql);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="text-center m-2">Grupos</h2>
<table cellpadding='8' class='table  border border-black-3' style='max-width: 80%; margin: 0 auto;'>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($grupos as $grupo): ?>
        <tr>
            <td><?= $grupo['id'] ?></td>
            <td><?= htmlspecialchars($grupo['nome']) ?></td>
            <td>
                <a class="btn btn-primary p-1 " href="editar_grupo.php?id=<?= $grupo['id'] ?>">Editar</a>
                <a class="btn btn-danger p-1" href="excluir_grupo.php?id=<?= $grupo['id'] ?>"
                    onclick="return confirm('Tem certeza que deseja excluir este grupo?');">Excluir</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>