<?php
$titulo = "Página Inicial";
include_once 'header.php';
include_once 'connection.php';
?>

<head>
    <style>
        /* Estilos gerais */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        main {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 80%;
            height: 100vh;
            max-width: 600px;

            /* Ajuste a largura máxima conforme necessário */
        }

        .container {
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        p {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Estilos dos botões */
        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 15px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
            /* Garante que o padding não aumenta o tamanho total */
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        /* Animações sutis */
        .btn-primary {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        h1 {
            animation: fadeIn 1s ease-in-out;
        }

        p {
            animation: slideIn 1s ease-out 0.5s forwards;
            opacity: 0;
        }

        .btn-primary {
            animation: slideUp 0.7s ease-out forwards;
            opacity: 0;
        }

        .btn-primary:nth-child(1) {
            animation-delay: 0.7s;
        }

        .btn-primary:nth-child(2) {
            animation-delay: 0.9s;
        }

        .btn-primary:nth-child(3) {
            animation-delay: 1.1s;
        }

        .btn-primary:nth-child(4) {
            animation-delay: 1.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="container col-12" id="paginainicial">
            <nav>
                <h1>Bem-vindo à Tabela de Preços</h1>
                <p class="mt-3 botao">Clique nos botões abaixo para navegar pela Tabela de Preços.</p>
                <div class="btn-container">
                    <a class="btn btn-primary" href="tabela_precos.php" target="_blank">Tabela de Preços</a>
                    <a class="btn btn-primary" href="cadastro_grupos.php" target="_blank">Cadastro de Grupos</a>
                    <a class="btn btn-primary" href="cadastro_produtos.php" target="_blank">Cadastro de Produtos</a>
                    <a class="btn btn-primary" href="configuracoes.php" target="_blank">Configurações Tabela</a>
                </div>
            </nav>
        </div>
    </main>
</body>

</html>