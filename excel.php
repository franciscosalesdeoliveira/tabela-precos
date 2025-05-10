<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Arquivo CSV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --text-color: #333;
            --light-gray: #f5f7fa;
            --border-color: #ddd;
            --success-color: #10b981;
            --error-color: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            margin-bottom: 40px;
        }

        header .container {
            display: flex;
            align-items: center;
            padding-top: 0;
            padding-bottom: 0;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-left: 10px;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        select,
        button {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .file-input-container {
            position: relative;
            width: 100%;
            border: 2px dashed var(--border-color);
            border-radius: 6px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-input-container:hover {
            border-color: var(--primary-color);
        }

        .file-input-container.active {
            border-color: var(--success-color);
            background-color: rgba(16, 185, 129, 0.05);
        }

        .file-input-icon {
            font-size: 40px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .file-input-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }

        .file-input-subtext {
            font-size: 14px;
            color: #888;
        }

        .file-name {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: var(--light-gray);
            border-radius: 4px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }

        .file-name-text {
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .remove-file {
            color: var(--error-color);
            cursor: pointer;
            font-size: 18px;
            margin-left: 10px;
        }

        #arquivo {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        .btn:disabled {
            background-color: #a0a0a0;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .card {
                padding: 20px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="container">
                <i class="fas fa-file-csv" style="font-size: 24px; color: var(--primary-color);"></i>
                <h1>Importar Arquivo CSV</h1>
            </div>
            <div class="container">
                <a href="index.php" style="text-decoration: none; color: var(--primary-color); font-size: 24px; display: flex; align-items: center;">
                    <i class="fas fa-home me-1"></i> Página Inicial
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <form method="post" action="processa_csv.php" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label for="arquivo">Arquivo CSV</label>
                    <div class="file-input-container" id="dropZone">
                        <i class="fas fa-cloud-upload-alt file-input-icon"></i>
                        <div class="file-input-text">Arraste e solte seu arquivo CSV aqui</div>
                        <div class="file-input-subtext">ou clique para selecionar</div>
                        <input type="file" name="arquivo" id="arquivo" accept=".csv" required>
                    </div>
                    <div class="file-name" id="fileName">
                        <span class="file-name-text" id="fileNameText"></span>
                        <i class="fas fa-times remove-file" id="removeFile"></i>
                    </div>
                    <div class="help-text">Formatos aceitos: .csv (máximo 10MB)</div>
                </div>

                <div class="form-group">
                    <label for="grupo_selecionado">Selecione o Grupo (opcional)</label>
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
                    </select>
                    <div class="help-text">Selecione um grupo para sobrescrever o grupo indicado no arquivo</div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-upload"></i>
                    Importar Arquivo
                </button>
            </form>
        </div>
    </div>

    <script>
        // Script para melhorar a experiência do usuário
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('arquivo');
            const fileName = document.getElementById('fileName');
            const fileNameText = document.getElementById('fileNameText');
            const removeFile = document.getElementById('removeFile');
            const submitBtn = document.getElementById('submitBtn');
            const uploadForm = document.getElementById('uploadForm');

            // Atualizar interface quando arquivo for selecionado
            fileInput.addEventListener('change', function() {
                updateFileInfo();
            });

            // Remover arquivo
            removeFile.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.value = '';
                fileName.style.display = 'none';
                dropZone.classList.remove('active');
                submitBtn.disabled = true;
            });

            // Efeito de arrastar e soltar
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropZone.classList.add('active');
            }

            function unhighlight() {
                dropZone.classList.remove('active');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                updateFileInfo();
            }

            // Atualizar informações do arquivo
            function updateFileInfo() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];

                    // Verificar se é um arquivo CSV
                    if (!file.name.toLowerCase().endsWith('.csv')) {
                        alert('Por favor, selecione um arquivo CSV válido.');
                        fileInput.value = '';
                        return;
                    }

                    // Exibir nome do arquivo
                    fileNameText.textContent = file.name;
                    fileName.style.display = 'flex';
                    dropZone.classList.add('active');
                    submitBtn.disabled = false;
                } else {
                    fileName.style.display = 'none';
                    dropZone.classList.remove('active');
                    submitBtn.disabled = true;
                }
            }

            // Adicionar evento de submit ao formulário
            uploadForm.addEventListener('submit', function(e) {
                if (fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Por favor, selecione um arquivo CSV para importar.');
                    return false;
                }

                // Verificar se o arquivo é um CSV
                if (!fileInput.files[0].name.toLowerCase().endsWith('.csv')) {
                    e.preventDefault();
                    alert('Por favor, selecione um arquivo CSV válido.');
                    return false;
                }

                // Se tudo estiver correto, exibir mensagem de espera
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                submitBtn.disabled = true;

                // Permitir que o formulário seja enviado naturalmente
                return true;
            });

            // Inicializar estado do botão
            submitBtn.disabled = !fileInput.value;
        });
    </script>
</body>

</html>