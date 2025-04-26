<?php
$titulo = "Página Inicial";
include_once 'header.php';
include_once 'connection.php';
?>

<body>

    <main>
        <div class="container col-12" id="paginainicial">
            <h1 class="mt-5 ">Bem-vindo à Tabela de Preços</h1>
            <p class="mt-3 botao text-center">Clique nos botões abaixo para navegar pela Tabela de Preços.</p>
            <button class="btn btn-primary mt-2" color="white"><a class="links" target="_blank" href="tabela_precos.php">Tabela de Preços</a></button>
            <button class="btn btn-primary mt-2 " color="white"><a class="links" target="_blank" href="cadastro_grupos.php">Cadastro de Grupos</a></button>
            <button class="btn btn-primary mt-2 " color="white"><a class="links" target="_blank" href="cadastro_produtos.php">Cadastro de Produtos</a></button>
        </div>
    </main>

</body>

</html>