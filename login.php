<?php
include_once 'connection.php';
// session_start();

// try {
// Testa se a conexão foi bem-sucedida
//     $pdo->query("SELECT 1");
// echo "Conexão bem-sucedida";
// } catch (PDOException $e) {
//     echo "Falha na conexão: " . $e->getMessage();
//     exit;
// }
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .login {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 320px;
            display: flex;
            flex-direction: column;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
        }

        a {
            padding: 10px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: block;
            text-align: center;
            cursor: pointer
        }

        a:hover {
            background-color: var(--primary-hover);
        }

        body {
            background-image: linear-gradient(to right, var(--primary), var(--secondary));
            font-family: Arial, sans-serif;
            color: white;

        }

        body h1 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
        }
    </style>
</head>

<body>
    <div class="container login">
        <h1>Login</h1>
        <form action="login.php" method="POST">
            <input type="text" id="username" name="username" placeholder="Digite seu usuário" required>
            <br><br>
            <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
            <br><br>
            <a href="#" type="submit">Acessar</a>
    </div>
    c
</body>

</html>