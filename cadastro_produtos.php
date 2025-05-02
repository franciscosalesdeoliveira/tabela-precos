<?php
$titulo = "Cadastro de Produtos";
require_once 'connection.php';
require_once 'header.php';

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

if (!empty($_GET['search'])) {
    $data = "%" . $_GET['search'] . "%"; // adiciona os % para o LIKE
    $sql = "SELECT * FROM produtos 
    WHERE unaccent(nome) ILIKE unaccent(:data)
       OR unaccent(descricao) ILIKE unaccent(:data)
       OR CAST(id AS TEXT) ILIKE :data
    ORDER BY id ASC";
    // unaccent é usado para remover acentos e permitir comparação sem acentuação
    // ILIKE é usado para comparação sem diferenciar maiúsculas de minúsculas
    // CAST(id AS TEXT) é usado para permitir a pesquisa pelo ID como texto

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':data', $data, PDO::PARAM_STR);
    $stmt->execute();
} else {
    $sql = "SELECT * FROM produtos ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<body>
    <section>
        <!-- Formulário para cadastrar produtos -->
        <div class="cadastro_produtos mt-3">
            <div class="cadastro_produtos_1">
                <form method="POST">
                    <div class="itens">
                        <label for="nome">Nome:</label>
                        <input type="text" name="nome" id="nome" required>
                    </div>
                    <div class="itens">
                        <label for="descricao">Descrição:</label>
                        <input type="text" name="descricao" id="descricao">
                    </div>
                    <div class="itens">
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
                    </div>
                    <div class="itens">
                        <label for="preco">Preço:</label>
                        <input type="number" name="preco" id="preco" step="0.01" required>
                    </div>
                    <div class="itens">
                        <button class="btn btn-warning mt-2" type="reset">Limpar</button>
                        <button class="btn btn-success mt-2" type="submit">Cadastrar</button>
                    </div>
            </div>
            </form>
            <div class="cadastro_produtos_2">
                <h3 class="text-center">Links Úteis</h3>
                <a href="index.php" class="btn btn-primary " ;>Página Inicial</a>
                <a href="cadastro_grupos.php" class="btn btn-primary" target=_blank ;>Cadastro de Grupos</a>
                <a href="configuracoes.php" class="btn btn-primary " ;>Configurações Tabela</a>

            </div>
        </div>
    </section>

    <!-- Pesquisa de Produtos -->
    <div class="box-search mt-3">
        <input class="form-control w-25" type="search" id="pesquisar" placeholder="Pesquisar ...">
        <button onclick="searchData()" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
            </svg></button>
    </div>


    <!-- Listagem de Produtos -->


    <section>
        <h2 class="text-center m-2" style="color: white;">Produtos</h2>
        <div class="overflow-y-auto" tabela-produtos style="max-height: 450px; max-width: 90%; margin: 0 auto;">
            <table cellpadding='8' class="table table-striped border border-black-3" style='max-width: 90%; margin: 0 auto;'>
                <tr>
                    <th class="text-center">ID</th>
                    <th class="text-center">Nome</th>
                    <th class="text-center">Descrição</th>
                    <th class="text-center">Grupo</th>
                    <th class="text-center">Preço</th>
                    <th class="text-center">Ações</th>
                </tr>
                <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td class="text-center"><?= $produto['id'] ?></td>
                        <td class="text-center"><?= htmlspecialchars($produto['nome']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($produto['descricao']) ?></td>
                        <td class="text-center"><?= $produto['grupo_id'] ?></td>
                        <td class="text-center"><?= $produto['preco'] ?></td>
                        <td class="text-center">
                            <div class=" col-12 d-flex justify-content-between">
                                <a class="btn btn-primary p-1 bottons w-50 text-center" href="editar_produto.php?id=<?= $produto['id'] ?>">Editar</a>
                                <a class="btn btn-danger p-1  bottons w-50 text-center" href="excluir_produto.php?id=<?= $produto['id'] ?>"
                                    onclick="return confirm('Tem certeza que deseja excluir este produto?');">Excluir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </section>

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
        window.location = 'cadastro_produtos.php?search=' + search.value;
    }
</script>