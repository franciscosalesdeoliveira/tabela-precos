<?php
$titulo = "Cadastro de Usuários";
include_once "header.php";
include_once "connection.php";
?>

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

    body {
        background-image: linear-gradient(to right, var(--primary), var(--secondary));
        font-family: Arial, sans-serif;
        color: white;
    }

    .box {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 450px;
        height: 500px;
        display: flex;
        flex-direction: column;
        background-color: rgba(0, 0, 0, 0.6);
        padding: 20px;
        border-radius: 10px;
        color: white;
    }

    fieldset {

        margin-top: 30px;
        padding: 30px;
        position: relative;
        border: 3px solid var(--primary);
        border-radius: 8px;
        width: 100%;

    }

    legend {
        position: absolute;
        top: -30px;
        margin-bottom: 20px;
        width: 90%;
        padding: 0 10px;
        background-color: var(--primary);
        color: white;
        border: 1px solid var(--primary);
        border-radius: 8px;
    }

    .inputBox {
        margin-bottom: 20px;
        position: relative;
        width: 100%;
        display: flex;

    }

    .inputUser {
        background: none;
        border: none;
        border-bottom: 1px solid var(--primary);
        outline: none;
        color: white;
        font-size: 18px;
        width: 100%;
        letter-spacing: 2px;
    }

    .labelInput {
        position: absolute;
        top: 0px;
        left: 0px;
        pointer-events: none;
        transition: .5s;
    }

    .inputUser:focus~.labelInput,
    .inputUser:valid~.labelInput {
        top: -20px;
        left: 0px;
        color: var(--primary);
        font-size: 12px;
    }
</style>

<body>
    <div class="box">
        <form action="">

            <fieldset>
                <legend>Cadastro de Usuários</legend>
                <div class="inputBox">
                    <input class="inputUser" type="text" name="uuid" id="uuid" required>
                    <label class="labelInput" for="uuid">UUID</label>
                </div>
                <div class="inputBox">
                    <input class="inputUser" type="text" name="nome" id="nome" required>
                    <label class="labelInput" for="nome">Nome</label>
                </div>
                <div class="inputBox">
                    <input class="inputUser" type="text" name="email" id="email" required>
                    <label class="labelInput" for="email">Email</label>
                </div>
                <div class="inputBox">
                    <input type="password" name="password" id="password" minlength="8" maxlength="200" required>
                    <label for="password">Senha</label>
                </div>
                <!-- <div class="selectBox">
                    <select name="regras" id="regras">
                        <option value="0">Selecione uma regra</option>
                        <option value="1">Administrador</option>
                        <option value="2">Usuário</option>
                        <option value="3">Convidado</option>
                    </select>
                    <label for="regras">Regras</label>
                </div> -->
                <div class="radioBox">
                    <p><b>Status</b></p>
                    <input type="radio" name="status" id="ativo" value="1" checked>
                    <label for="ativo">Ativo</label>
                    <input type="radio" name="status" id="inativo" value="0">
                    <label for="inativo">Inativo</label>
                </div>
                <input type="submit" value="Cadastrar">
                <input type="reset" value="Limpar">
            </fieldset>
        </form>
    </div>
    f
</body>

</html>