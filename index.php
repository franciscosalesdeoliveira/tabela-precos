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

            <a class="btn btn-primary mt-2" target="_blank" href="tabela_precos.php" style="width:100%; height: 70px; padding: 20px; color: white; text-align: center; font-size: 20px;">
                Tabela de Preços
            </a>

            <a class=" btn btn-primary mt-2 " color=" white" target="_blank" href="cadastro_grupos.php" style="width:100%; height: 70px; padding: 20px; color: white; text-align: center;font-size: 20px;">Cadastro de Grupos</a>

            <a class="btn btn-primary mt-2 " color="white" target="_blank" href="cadastro_produtos.php" style="width:100%; height: 70px; padding: 20px; color: white; text-align: center;font-size: 20px;">Cadastro de Produtos</a>


        </div>
    </main>

</body>

</html>