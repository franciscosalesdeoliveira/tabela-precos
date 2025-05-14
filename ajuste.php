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
            $mail->Host = 'smtp.seuprovedor.com'; // Ex: smtp.gmail.com, smtp.office365.com
            $mail->SMTPAuth = true;
            $mail->Username = 'seuemail@dominio.com'; // Seu email completo
            $mail->Password = 'suasenha'; // Senha do email ou senha de app
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