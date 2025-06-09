<?php
require_once 'connection.php';
$titulo = "Cadastro de Usuário";
require_once 'header.php';

// Função para gerar UUID
function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

// Buscar empresas para o select
$empresas = [];
try {
    $stmt = $pdo->query("SELECT id, razao_social FROM empresas WHERE ativo = 'S' ORDER BY razao_social;");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao buscar empresas: " . $e->getMessage();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $empresaid = $_POST['empresaid'];
    $ativo = isset($_POST['ativo']) ? 'S' : 'N';

    $errors = [];

    // Validações
    if (empty($nome)) {
        $errors[] = "Nome é obrigatório";
    }

    if (empty($email)) {
        $errors[] = "Email é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }

    if (empty($senha)) {
        $errors[] = "Senha é obrigatória";
    } elseif (strlen($senha) < 6) {
        $errors[] = "Senha deve ter pelo menos 6 caracteres";
    }

    if ($senha !== $confirmar_senha) {
        echo "<script>alert('Senhas não conferem');</script>";
        $errors[] = "Senhas não conferem";
    }

    if (empty($empresaid)) {
        $errors[] = "Empresa é obrigatória";
    }

    // Verificar se email já existe
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND apagado_em IS NULL");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email já está em uso";
            }
        } catch (PDOException $e) {
            $errors[] = "Erro ao verificar email";
        }
    }

    // Inserir usuário
    if (empty($errors)) {
        try {
            $uuid = generateUUID();
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $criado_por = 'Sistema'; // Ajuste conforme necessário

            $sql = "INSERT INTO usuarios (uuid, empresaid, nome, email, senha, ativo, criado_em, criado_por, atualizado_em, atualizado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uuid, $empresaid, $nome, $email, $senha_hash, $ativo, $criado_por, $criado_por]);

            $success_message = "Usuário cadastrado com sucesso!";

            // Limpar campos após sucesso
            $nome = $email = $empresaid = '';

            header("Location: cadastro_usuarios.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erro ao cadastrar usuário: " . $e->getMessage();
        }
    }
}


?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .form-container {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
            transform: translateY(-2px);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 45px;
            color: #666;
            pointer-events: none;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            transform: scale(1.2);
        }

        .btn-primary {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            background: #dc3545;
            width: 33%;
        }

        .strength-medium {
            background: #ffc107;
            width: 66%;
        }

        .strength-strong {
            background: #28a745;
            width: 100%;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .form-container {
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4facfe;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .button {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            gap: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Cadastro</h1>
            <p>Criar nova conta de usuário</p>
        </div>

        <div class="form-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="userForm">
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-user"></i> Nome Completo *
                    </label>
                    <input type="text" id="nome" name="nome"
                        value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                        required maxlength="150">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" id="email" name="email"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required maxlength="150">
                </div>

                <div class="form-group">
                    <label for="empresaid">
                        <i class="fas fa-building"></i> Empresa *
                    </label>
                    <select id="empresaid" name="empresaid" required>
                        <option value="">Selecione uma empresa</option>
                        <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"
                                <?php echo (isset($empresaid) && $empresaid == $empresa['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empresa['razao_social']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="senha">
                            <i class="fas fa-lock"></i> Senha *
                        </label>
                        <input type="password" id="senha" name="senha" required minlength="6">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha">
                            <i class="fas fa-lock"></i> Confirmar Senha *
                        </label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="ativo" name="ativo"
                        <?php echo (!isset($ativo) || $ativo !== 'N') ? 'checked' : ''; ?>>
                    <label for="ativo">Usuário ativo</label>
                </div>

                <div class="button">
                    <button type="submit" class="btn-primary" title="Cadastrar Usuário" aria-label="Cadastrar usuário">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="reset" class="btn-primary" title="Limpar" araia-label="Limpar formulário">
                        <i class="fa-solid fa-eraser"></i>
                    </button>
                </div>

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Cadastrando usuário...</p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validação de força da senha
        document.getElementById('senha').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength >= 1) strengthBar.classList.add('strength-weak');
            if (strength >= 2) strengthBar.classList.add('strength-medium');
            if (strength >= 3) strengthBar.classList.add('strength-strong');
        });

        // Validação em tempo real das senhas
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = this.value;

            if (confirmarSenha && senha !== confirmarSenha) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        // Loading no submit
        document.getElementById('userForm').addEventListener('submit', function() {
            document.querySelector('.btn-primary').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
        });

        // Animação nos inputs
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>