<?php
$titulo = "Configurações Tabela";
include_once 'header.php';
include_once 'connection.php';
?>



<div class="container mt-4">
    <form class="row g-3 justify-content-center" onsubmit="redirecionarTabela(event)" onsubmit="tempoTabela(event)">
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

    <!-- Formulário de Tempo -->
    <form class="row g-3 justify-content-center mb-4 form-area">
        <div class="col-auto">
            <label for="tempo" class="col-form-label">Tempo por slide (segundos):</label>
        </div>
        <div class="col-auto">
            <input type="number" class="form-control" id="tempo" name="tempo" placeholder="Ex: 60">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary mb-3">Ver Tabela</button>
        </div>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    function redirecionarTabela(event) {
        event.preventDefault();
        const limite = document.getElementById('limite').value;
        if (limite) {
            window.location.href = `tabela_precos.php?limite=${limite}`;
        } else {
            window.location.href = 'tabela_precos.php';
        }
    }


    // <!-- JS Bootstrap + Função do Tempo -->

    let carouselInterval;

    function tempoTabela(event) {
        event.preventDefault();
        const tempo = parseInt(document.getElementById('tempo').value);
        if (isNaN(tempo) || tempo <= 0) {
            alert('Informe um tempo válido (em segundos).');
            return;
        }

        const ms = tempo * 1000;
        const carousel = document.querySelector('#carouselTabelas');
        const instance = bootstrap.Carousel.getInstance(carousel) || new bootstrap.Carousel(carousel, {
            interval: ms,
            ride: 'carousel',
            wrap: true // importante para loop infinito
        });

        if (carouselInterval) clearInterval(carouselInterval);
        instance._config.interval = ms;
        instance.cycle();

        carouselInterval = setInterval(() => {
            instance.next();
        }, ms);
    }
</script>