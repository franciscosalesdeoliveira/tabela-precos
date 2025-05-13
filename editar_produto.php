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
        $erro = "Erro ao atualizar o produto.";
    }
} else {
    // Carrega os dados do produto
    if (!isset($_GET["id"])) {
        echo "<div class='alert alert-danger text-center'>Produto não informado.</div>";
        exit;
    }

    $id = $_GET["id"];
    $sql = "SELECT nome, descricao, grupo_id, preco FROM produtos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        echo "<div class='alert alert-danger text-center'>Produto não encontrado.</div>";
        exit;
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0 text-center">Editar Produto</h2>
                </div>

                <div class="card-body">
                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger"><?= $erro ?></div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="id" value="<?= $id ?>">

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                value="<?= htmlspecialchars($produto['nome']) ?>" required>
                            <div class="invalid-feedback">
                                Por favor, informe o nome do produto.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao"
                                rows="3"><?= htmlspecialchars($produto['descricao']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="grupo_id" class="form-label">Grupo</label>
                            <select class="form-select" id="grupo_id" name="grupo_id" required>
                                <option value="">Selecione um grupo</option>
                                <?php
                                $sql = "SELECT id, nome FROM grupos ORDER BY nome";
                                $stmt = $pdo->query($sql);
                                $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($grupos as $grupo) {
                                    $selected = ($grupo['id'] == $produto['grupo_id']) ? 'selected' : '';
                                    echo "<option value='{$grupo['id']}' {$selected}>{$grupo['nome']}</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um grupo.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="preco" class="form-label">Preço</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="preco" name="preco"
                                    value="<?= $produto['preco'] ?>" step="0.01" min="0" required>
                                <div class="invalid-feedback">
                                    Por favor, informe um preço válido.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="cadastro_produtos.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Validação do formulário
    (function() {
        'use strict';

        const forms = document.querySelectorAll('.needs-validation');

        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Formatar campo de preço para exibir duas casas decimais
    document.getElementById('preco').addEventListener('blur', function(e) {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
</script>