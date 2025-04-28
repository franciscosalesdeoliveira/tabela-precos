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

if (!empty($_GET['search'])) {
    $data = "%" . $_GET['search'] . "%"; // adiciona os % para o LIKE
    $sql = "SELECT * FROM grupos 
    WHERE unaccent(nome) ILIKE unaccent(:data)
       OR CAST(id AS TEXT) ILIKE :data
    ORDER BY id ASC";
    // unaccent é usado para remover acentos e permitir comparação sem acentuação
    // ILIKE é usado para comparação sem diferenciar maiúsculas de minúsculas
    // CAST(id AS TEXT) é usado para permitir a pesquisa pelo ID como texto

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':data', $data, PDO::PARAM_STR);
    $stmt->execute();
} else {
    $sql = "SELECT * FROM grupos ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<body>

    <!-- Formulário para cadastrar grupos -->
    <div class="container mt-3" style="max-width: 80%; margin: 0 auto;">
        <form method="POST">
            <label for="grupo">Grupo:</label>
            <input type="text" name="grupo" id="grupo" required>
            <button class="btn btn-primary mt-2 " color="white" target="_blank" style="width:10%; height: 30px;  color: white; ;font-size: 20px;" type="submit">Cadastrar</button>
            <button class="btn btn-primary mt-2 " color="white" target="_blank" style="width:10%; height: 30px;  color: white; ;font-size: 20px;" type="reset">Limpar</button>
            <!-- <button><a href="listar_grupos.php" target="_blank">Listar Grupos</a></button> -->
            <a class="btn btn-primary mt-2 " color="white" target="_blank" style="width:10%; height: 30px;  color: white; ;font-size: 20px;" href="index.php">Página Inicial</a>
        </form>
    </div>

    <!-- formulario de pesquisa -->

    <div class="box-search">
        <input class="form-control w-25" type="search" id="pesquisar" placeholder="Pesquisar ...">
        <button onclick="searchData()" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
            </svg></button>
    </div>


    <!-- Listagem de Grupos -->
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
</body>

<script>
    var search = document.getElementById("pesquisar");

    //verifica a tecla apertada e chama a função searchData() se for Enter
    search.addEventListener("keyup", function(event) {
        if (event.key === "Enter") {
            searchData();
        }
    });

    function searchData() {
        window.location = 'cadastro_grupos.php?search=' + search.value;
    }
</script>