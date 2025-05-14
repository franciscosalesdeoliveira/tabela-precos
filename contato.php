<?php
require_once 'connection.php';
$titulo = "Fale Conosco";
require_once 'header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensagem_status = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST['nome'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $assunto = htmlspecialchars($_POST['assunto'] ?? '');
    $mensagem = htmlspecialchars($_POST['mensagem'] ?? '');

    // Validação básica
    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
        $mensagem_status = '<div class="alert-danger">Por favor, preencha todos os campos.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_status = '<div class="alert-danger">Por favor, insira um email válido.</div>';
    } else {
        try {
            // Carrega o autoload do Composer
            require 'vendor/autoload.php'; // Se usou Composer

            // Ou inclua manualmente os arquivos (se fez download manual):
            // require 'PHPMailer/src/Exception.php';
            // require 'PHPMailer/src/PHPMailer.php';
            // require 'PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);

            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Ex: smtp.gmail.com, smtp.office365.com
            $mail->SMTPAuth = true;
            $mail->Username = 'franciscos.oliveira.filho@gmail.com'; // Seu email completo
            $mail->Password = 'wdol lqeb rygx qfqn'; // Senha do email ou senha de app
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL/TLS
            $mail->Port = 465; // Porta do SMTP (465 para SSL, 587 para TLS)

            // Remetente e destinatário
            $mail->setFrom('nao-responder@seusite.com', 'Sistema de Contato');
            $mail->addAddress('contato@seusite.com', 'Nome do Destinatário');
            $mail->addReplyTo($email, $nome);

            // Conteúdo do email
            $mail->isHTML(true);
            $mail->Subject = "Contato via Site: $assunto";
            $mail->Body = "
                <h2>Nova mensagem de contato</h2>
                <p><strong>Nome:</strong> $nome</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Assunto:</strong> $assunto</p>
                <p><strong>Mensagem:</strong></p>
                <p>$mensagem</p>
            ";
            $mail->AltBody = "Nome: $nome\nEmail: $email\nAssunto: $assunto\nMensagem:\n$mensagem";

            $mail->send();
            $mensagem_status = '<div class="alert-success">Mensagem enviada com sucesso! Entraremos em contato em breve.</div>';
        } catch (Exception $e) {
            $mensagem_status = '<div class="alert-danger">Erro ao enviar mensagem. Erro: ' . $mail->ErrorInfo . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fale Conosco - Sistema de Tabela de Preços</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-800: #343a40;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .contact-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.6s ease;
        }

        .contact-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .contact-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .contact-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .contact-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }

        .contact-wave svg {
            display: block;
            width: calc(100% + 1.3px);
            height: 46px;
        }

        .contact-wave .shape-fill {
            fill: #FFFFFF;
        }

        .contact-content {
            padding: 40px 30px;
        }

        .contact-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .contact-form-container,
        .whatsapp-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
            padding: 25px;
            animation: fadeInUp 0.8s ease forwards;
        }

        .contact-form-container {
            display: flex;
            flex-direction: column;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-800);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
        }

        .whatsapp-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .whatsapp-container p {
            margin-bottom: 20px;
            color: #6c757d;
            line-height: 1.6;
        }

        .whatsapp-button {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #25D366;
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            width: 80%;
            max-width: 300px;
        }

        .whatsapp-button:hover {
            background-color: #128C7E;
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(37, 211, 102, 0.2);
        }

        .whatsapp-button i {
            margin-right: 10px;
            font-size: 1.3rem;
        }

        .whatsapp-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 10px;
        }

        .whatsapp-hours {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--gray-100);
            border-radius: 8px;
            color: var(--gray-800);
            width: 100%;
        }

        .whatsapp-hours h4 {
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .whatsapp-hours p {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-top: 30px;
            transition: all 0.3s ease;
        }

        .back-link i {
            margin-right: 8px;
        }

        .back-link:hover {
            color: var(--primary-hover);
            transform: translateX(-5px);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #28a745;
        }

        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .contact-title {
                font-size: 1.8rem;
            }

            .contact-content {
                padding: 30px 20px;
            }

            .contact-options {
                grid-template-columns: 1fr;
            }

            .whatsapp-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="contact-container">
        <div class="contact-header">
            <h1 class="contact-title">Fale Conosco</h1>
            <p class="contact-subtitle">Estamos à disposição para ajudar você</p>
            <div class="contact-wave">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                </svg>
            </div>
        </div>

        <div class="contact-content">
            <?php echo $mensagem_status; ?>

            <div class="contact-options">
                <div class="contact-form-container">
                    <h2 class="section-title">Envie sua mensagem</h2>

                    <form method="post" action="">
                        <div class="form-group">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="assunto" class="form-label">Assunto</label>
                            <input type="text" id="assunto" name="assunto" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="mensagem" class="form-label">Mensagem</label>
                            <textarea id="mensagem" name="mensagem" class="form-control" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-block">Enviar Mensagem</button>
                    </form>
                </div>

                <div class="whatsapp-container">
                    <h2 class="section-title">Atendimento via WhatsApp</h2>
                    <p>Precisa de ajuda imediata? Entre em contato conosco pelo WhatsApp para um atendimento rápido e personalizado.</p>

                    <a href="https://wa.me/5515981813900" class="whatsapp-button" target="_blank">
                        <i class="fab fa-whatsapp"></i> Iniciar Conversa
                    </a>

                    <div class="whatsapp-number">
                        <i class="fas fa-phone"></i> (15) 98181-3900
                    </div>

                    <div class="whatsapp-hours">
                        <h4><i class="far fa-clock"></i> Horários de Atendimento</h4>
                        <p>Segunda à Sexta: 08:00 - 18:00</p>
                        <p>Sábado: 09:00 - 12:00</p>
                        <p>Domingo e Feriados: Fechado</p>
                    </div>
                </div>
            </div>

            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>
</body>

</html>