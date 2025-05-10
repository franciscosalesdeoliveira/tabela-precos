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
        <label for="arquivo">Arquivo</label>
        <input type="file" name="arquivo" id="arquivo" accept=".csv" required><br><br>

        <label for="grupo_selecionado">Selecione o Grupo (opcional):</label>
        <select name="grupo_selecionado" id="grupo_selecionado">
            <option value="">-- Usar grupo do CSV --</option>
            <?php
            // Incluir conexão com o banco
            include_once 'connection.php';

            // Consultar grupos disponíveis
            $query = "SELECT id, nome FROM grupos ORDER BY nome";
            $stmt = $pdo->prepare($query);
            $stmt->execute();

            while ($grupo = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<option value="' . $grupo['id'] . '">' . $grupo['nome'] . '</option>';
            }
            ?>
        </select><br><br>

        <button type="submit">Enviar</button>
    </form>
</body>

</html>