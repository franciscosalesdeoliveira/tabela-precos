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

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
    <!-- Bootstrap CSS incluído no header.php -->
    <style>
        .form-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .buttons-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .alert-container {
            max-width: 600px;
            margin: 10px auto;
        }
    </style>
</head>

<body>

    <!-- Mensagens de feedback -->
    <?php if (!empty($mensagem)): ?>
        <div class="alert-container">
            <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <h2 class="text-center mb-4" style="font-size: 24px; font-weight: bold; color: white;">Editar Grupo</h2>

        <form method="post" id="formEditar">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="mb-3">
                <label class="form-label" style="font-weight: bold; color: white;" for="nome">Nome do Grupo:</label>
                <input class="form-control" type="text" name="nome" id="nome"
                    value="<?= htmlspecialchars($grupo['nome']) ?>" required>
            </div>

            <div class="buttons-container">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a href="cadastro_grupos.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        // Validação do formulário no lado do cliente
        document.getElementById('formEditar').addEventListener('submit', function(event) {
            const nome = document.getElementById('nome').value.trim();

            if (nome === '') {
                event.preventDefault();
                alert('O nome do grupo não pode estar vazio.');
                document.getElementById('nome').focus();
            }
        });

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
</body>

</html>