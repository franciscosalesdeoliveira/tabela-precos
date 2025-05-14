<!-- Footer -->
<footer class="footer py-2 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-12 text-center">
                <p class="mb-1 small">&copy; <?php echo date('Y'); ?> TadsBr Softwares. Todos os direitos reservados.</p>
                <p class="mb-0 small">
                    <i class="fab fa-whatsapp" style="color: green"></i>
                    <a href="https://wa.me/5515981813900" target="_blank" class="text-light text-decoration-none">(15) 98181-3900</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Carregamento automático do Font Awesome -->
<script>
    // Verifica se o Font Awesome já foi carregado
    if (document.querySelectorAll('link[href*="font-awesome"]').length === 0) {
        // Adiciona o link para o CSS do Font Awesome
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(link);
    }
</script>
<!-- End of Footer -->