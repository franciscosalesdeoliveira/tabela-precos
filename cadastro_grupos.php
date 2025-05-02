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

<body class="">

    <!-- Formulário para cadastrar grupos -->
    <div class=" container grupos mt-3" id="grupos" style="max-width: 80%; margin: 0 auto;">
        <form method="POST">
            <div class="botao">
                <label class="form-label" style="font-size: 20px; font-weight: bold; color: white;" for="grupo">Grupo:</label>
                <input class="form-control w-80 m-2" type="text" name="grupo" id="grupo" style="width: 80%;" required>
                <button class="btn btn-success m-2" color="white" style="width:27%; height: 40px;  color: white; ;font-size: 18px;" type="submit">Cadastrar</button>
                <button class="btn btn-warning m-2" color="white" target="_blank" style="width:27%; height: 40px;  color: white; ;font-size: 18px;" type="reset">Limpar</button>
                <a class="btn btn-primary m-2" color="white" target="_blank" style="width:27%; height: 40px;  color: white; ;font-size: 18px;" href="index.php">Página Inicial</a>
            </div>
        </form>
    </div>

    <!-- formulario de pesquisa -->

    <div class="box-search m-5">
        <input class="form-control w-25" type="search" id="pesquisar" placeholder="Pesquisar ...">
        <button onclick="searchData()" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
            </svg></button>
    </div>


    <!-- Listagem de Grupos -->
    <div style="max-width: 80%; margin: 0 auto;">
        <!-- <h2 class="text-center m-2">Grupos</h2> -->
        <div class="overflow-y-auto" style="max-height: 450px;">
            <table cellpadding='8' class='table table-striped' style='max-width: 80%; margin: 0 auto;'>
                <tr>
                    <th class="text-center">ID</th>
                    <th class="text-center">Nome</th>
                    <th class="text-center">Ações</th>
                </tr>
                <?php foreach ($grupos as $grupo): ?>
                    <tr>
                        <td class="text-center"><?= $grupo['id'] ?></td>
                        <td class="text-center"><?= htmlspecialchars($grupo['nome']) ?></td>
                        <td class="text-center">
                            <div class=" col-12 d-flex justify-content-between">
                                <a class="btn btn-primary p-1 bottons col-5" href="editar_grupo.php?id=<?= $grupo['id'] ?>">Editar</a>
                                <a class="btn btn-danger p-1  bottons col-5" href="excluir_grupo.php?id=<?= $grupo['id'] ?>"
                                    onclick="return confirm('Tem certeza que deseja excluir este grupo e todos os seus produtos?');">Excluir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

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