<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Arquivo Excel</title>
</head>

<body>
    <h1>Importar Arquivo CSV</h1>

    <form method="post" action="processa_csv.php" enctype="multipart/form-data">
        <label for="">Arquivo</label>
        <input type="file" name="arquivo" id="arquivo" accept=".csv" required><br><br>

        <button type="submit">Enviar</button>

    </form>
</body>

</html>