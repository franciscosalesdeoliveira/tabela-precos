<?php
include_once 'connection.php';
session_start();

// Verificar se já está logado
if (isset($_SESSION['user_id'])) {
    header("Location: tabela_precos.php");
    exit;
}

$error_message = '';
$success_message = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Buscar usuário no banco (pode ser email ou nome)
            $sql = "SELECT u.id, u.uuid, u.nome, u.email, u.senha, u.ativo, u.empresaid, e.razao_social 
                    FROM usuarios u 
                    JOIN empresas e ON u.empresaid = e.id 
                    WHERE (u.email = :username OR u.nome = :username) 
                    AND u.apagado_em IS NULL 
                    AND u.ativo = 'S' 
                    AND e.ativo = 'S'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['senha'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_uuid'] = $user['uuid'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['empresa_id'] = $user['empresaid'];
                $_SESSION['empresa_nome'] = $user['razao_social'];
                $_SESSION['login_time'] = time();
                
                // Atualizar último acesso
                $updateStmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW(), atualizado_em = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Redirecionar para tabela de preços
                header("Location: tabela_precos.php");
                exit;
                
            } else {
                $error_message = "Usuário ou senha incorretos.";
            }
            
        } catch (PDOException $e) {
            $error_message = "Erro ao processar login. Tente novamente.";
            error_log("Erro de login: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Preços</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --error: #dc3545;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-800: #343a40;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 3rem;
            color: var(--light);
            margin-bottom: 15px;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 10px;
            color: var(--light);
        }

        .login-header p {
            opacity: 0.8;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group input:focus {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.3);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent), var(--success));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(72, 149, 239, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            border-left-color: #28a745;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border-left-color: var(--error);
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 15px;
        }

        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: white;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.8rem;
            opacity: 0.6;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .login-header h1 {
                font-size: 1.7rem;
            }

            .login-header i {
                font-size: 2.5rem;
            }
        }

        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-chart-line"></i>
            <h1>Sistema de Preços</h1>
            <p>Acesse sua conta para continuar</p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Usuário ou Email
                </label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" 
                           placeholder="Digite seu nome de usuário ou email" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required autocomplete="username">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" 
                           placeholder="Digite sua senha" 
                           required autocomplete="current-password">
                    <i class="fas fa-eye input-icon" id="togglePassword" style="cursor: pointer;"></i>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Verificando credenciais...</p>
            </div>
        </form>

        <div class="forgot-password">
            <a href="#" onclick="alert('Entre em contato com o administrador do sistema.')">
                <i class="fas fa-question-circle"></i> Esqueceu sua senha?
            </a>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Preços. Todos os direitos reservados.</p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this;
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form submission with loading
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            
            loginBtn.style.display = 'none';
            loading.style.display = 'block';
            
            // Se houver erro, restaurar o botão após 3 segundos
            setTimeout(function() {
                if (loading.style.display !== 'none') {
                    loginBtn.style.display = 'block';
                    loading.style.display = 'none';
                }
            }, 3000);
        });

        // Focus animations
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-focus no primeiro campo
        document.getElementById('username').focus();

        // Enter key navigation
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>

</html>