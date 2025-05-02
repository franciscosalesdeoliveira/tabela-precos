<!--NÃO USANDO POR ENQUANTO-->
<?php
$titulo = "Listar Grupos";
require_once "connection.php";
require_once "header.php";

$sql = "SELECT id, nome FROM grupos ORDER BY id";
$stmt = $pdo->query($sql);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="text-center m-2" style="color:white; font-size: 20px; font-weight: bold;">Grupos</h2>
<table cellpadding='8' class='table  border border-black-3' style='max-width: 80%; margin: 0 auto;'>
    <tr>
        <th class="text-center">ID</th>
        <th class="text-center">Nome</th>
        <th class="text-center">Ações</th>
    </tr>
    <?php foreach ($grupos as $grupo): ?>
        <tr>
            <td class="text-center"><?= $grupo['id'] ?></td>
            <td class="text-center"><?= htmlspecialchars($grupo['nome']) ?></td>
            <td class="text-center align-middle">

                <a class="btn btn-primary p-1 w-25 " href="editar_grupo.php?id=<?= $grupo['id'] ?>">Editar</a>
                <a class="btn btn-danger p-1 w-25" href="excluir_grupo.php?id=<?= $grupo['id'] ?>"
                    onclick="return confirm('Tem certeza que deseja excluir este grupo?');">Excluir</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>