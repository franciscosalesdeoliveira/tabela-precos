<h2>Status da Importação</h2>
<div id="status" style="font-family: Arial; font-size: 16px; margin-top: 20px;">
    Aguardando início da importação...
</div>

<script>
// Faz consulta AJAX a cada 2 segundos
function checkStatus() {
    fetch('status.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById('status').innerHTML = data.message;

        if (!data.error && data.imported < data.total) {
            setTimeout(checkStatus, 2000);
        } else if (data.error) {
            document.getElementById('status').innerHTML += '<br><strong>Erro na importação!</strong>';
        } else {
            document.getElementById('status').innerHTML += '<br><strong>Importação concluída!</strong>';
        }
    });
}

checkStatus();
</script>
