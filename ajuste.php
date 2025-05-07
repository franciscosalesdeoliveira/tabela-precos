<?php if (!$isAjax): ?>
<script>
    // ... (código anterior permanece igual)

    // Atualização automática - só executa se o tempo for maior que 0
    <?php if (isset($_GET['atualizacao_auto']) && $_GET['atualizacao_auto'] > 0): ?>
        setInterval(function() {
            fetch('tabela_precos.php?limite=<?= $limiteGrupo ?>&tempo=<?= $tempoSlide / 1000 ?>&tema=<?= $tema ?>&rolagem=<?= $tempoRolagem ?>&atualizacao_auto=<?= $_GET['atualizacao_auto'] ?>&grupo=<?= $grupoSelecionado ?>', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    document.querySelector('.container-fluid').innerHTML = html;
                    const carousel = document.querySelector('#grupoCarousel');
                    if (carousel) {
                        const bsCarousel = new bootstrap.Carousel(carousel);
                        bsCarousel.cycle();
                    }
                    setupAutoScroll();
                })
                .catch(error => console.error('Erro ao atualizar preços:', error));
        }, <?= $_GET['atualizacao_auto'] * 60 * 1000 ?>); // Converte minutos para milissegundos
    <?php endif; ?>

    // ... (restante do código permanece igual)
</script>
<?php endif; ?>