<?php
$titulo = "Editar Grupo";
require_once "connection.php";
require_once "header.php";
include_once 'functions.php'; // Arquivo sugerido para funções auxiliares

// Inicializar variáveis para mensagens de feedback
$mensagem = '';
$tipo_mensagem = '';

// Geração de token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar se o ID foi fornecido
if (!isset($_GET["id"]) || empty($_GET["id"]) || !is_numeric($_GET["id"])) {
    $_SESSION['mensagem'] = "ID do grupo não informado ou inválido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadastro_grupos.php");
    exit;
}

$id = (int)$_GET["id"];

// Processamento do formulário de atualização
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro de validação do formulário.";
        $tipo_mensagem = "danger";
    } else {
        // Validação de entrada
        $id_form = (int)$_POST["id"];
        $novoNome = trim($_POST["nome"]);

        if ($id_form !== $id) {
            $mensagem = "ID do formulário não corresponde ao ID da URL.";
            $tipo_mensagem = "danger";
        } elseif (empty($novoNome)) {
            $mensagem = "O nome do grupo não pode estar vazio.";
            $tipo_mensagem = "danger";
        } else {
            try {
                $sql = "UPDATE grupos SET nome = :nome WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":nome", $novoNome);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    // Armazenar mensagem em sessão para exibir após redirecionamento
                    $_SESSION['mensagem'] = "Grupo atualizado com sucesso!";
                    $_SESSION['tipo_mensagem'] = "success";
                    header("Location: cadastro_grupos.php");
                    exit;
                } else {
                    $mensagem = "Erro ao atualizar o grupo.";
                    $tipo_mensagem = "danger";
                }
            } catch (PDOException $e) {
                $mensagem = "Erro ao atualizar: " . filter_var($e->getMessage(), FILTER_SANITIZE_SPECIAL_CHARS);
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Carregar dados do grupo existente
try {
    $sql = "SELECT nome FROM grupos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grupo) {
        $_SESSION['mensagem'] = "Grupo não encontrado.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: cadastro_grupos.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro ao buscar grupo: " . filter_var($e->getMessage(), FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadastro_grupos.php");
    exit;
}
?>

<style>
    html,
    body {
        height: 100%;
        margin: 0;
    }

    .wrapper {
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }

    main {
        flex: 1;
    }

    /* footer {
        background: #ccc;
        padding: 20px;
        text-align: center;
    } */
</style>


<body>

    <div class="container py-5 wrapper">
        <main>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <!-- Mensagens de feedback -->
                    <?php if (!empty($mensagem)): ?>
                        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($mensagem) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0 text-center">Editar Grupo</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" id="formEditar" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome do Grupo</label>
                                    <input type="text" class="form-control" id="nome" name="nome"
                                        value="<?= htmlspecialchars($grupo['nome']) ?>" required>
                                    <div class="invalid-feedback">
                                        O nome do grupo não pode estar vazio.
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="cadastro_grupos.php" class="btn btn-secondary">
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
        </main>
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

        // Fechar alertas automaticamente após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alertList = document.querySelectorAll('.alert');
            alertList.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
    <footer>
        <?php require_once 'footer.php'; ?>
        </footerv>
</body>