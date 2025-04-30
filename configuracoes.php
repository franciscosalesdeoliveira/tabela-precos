<?php
$titulo = "Configurações Tabela";
include_once 'header.php';
include_once 'connection.php';
?>
<!-- <form method="GET">
    <label for="limite">Limite de produtos por grupos:</label>
    <input type="number" name="limite" id="limite" min="1" value="<?= isset($_GET['limite']) ? $_GET['limite'] : 5 ?>">
    <button type="submit">Aplicar</button>
</form> -->

<label for="colorPicker">Escolha uma cor:</label>
<input type="color" id="colorPicker" value="#3498db">

<div class="container mt-4">
    <form class="row g-3 justify-content-center" onsubmit="redirecionarTabela(event)">
        <div class="col-auto">
            <label for="limite" class="col-form-label">Quantidade de itens por grupo:</label>
        </div>
        <div class="col-auto">
            <input type="number" class="form-control" id="limite" name="limite">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary mb-3">Ver Tabela</button>
        </div>
    </form>
</div>

<script>
    function redirecionarTabela(event) {
        event.preventDefault();
        const limite = document.getElementById('limite').value;
        if (limite) {
            window.location.href = `tabela_precos.php?limite=${limite}`;
        } else {
            window.location.href = 'tabela_precos.php';
        }
    }

    // Adiciona um evento de mudança ao seletor de cores
    // document.addEventListener('DOMContentLoaded', () => {
    //     const colorInput = document.getElementById('colorPicker');

    //     colorInput.addEventListener('input', function() {
    //         const color = this.value;
    //         // Altera diretamente a cor do body, pois variáveis CSS não afetam propriedades diretamente já definidas em outros seletores
    //         document.body.style.backgroundColor = color;
    //     });
    // });
</script>