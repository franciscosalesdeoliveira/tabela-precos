<?php
$titulo = "Editar Grupo";
require_once "connection.php";
require_once "header.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Atualiza o grupo
    $id = $_POST["id"];
    $novoNome = trim($_POST["nome"]);

    $sql = "UPDATE grupos SET nome = :nome WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":nome", $novoNome);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: cadastro_grupos.php");
        exit;
    } else {
        echo "Erro ao atualizar.";
    }
} else {
    // Carrega os dados do grupo
    if (!isset($_GET["id"])) {
        echo "Grupo não informado.";
        exit;
    }

    $id = $_GET["id"];
    $sql = "SELECT nome FROM grupos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grupo) {
        echo "Grupo não encontrado.";
        exit;
    }
}
?>

<body>

    <div>
        <h2 class="text-center mt-5" style="font-size: 30px; font-weight: bold; color: white;">Editar Grupo</h2>
        <form class="text-center mt-5" method="post">
            <input type="hidden" name="id" value="<?= $id ?>">
            <label class="form-label" style="font-size: 20px; font-weight: bold; color: white;" for="nome">Grupo:</label>
            <input class="text-center" type="text" name="nome" value="<?= htmlspecialchars($grupo['nome']) ?>" required>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </form>
    </div>
</body>