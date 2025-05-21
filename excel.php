<?php
$titulo = "Importar Arquivo CSV";
require_once 'header.php';
require_once 'connection.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Arquivo CSV</title>
    <!-- Adicionando Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --error: #e63946;
            --border: #dee2e6;
            --light-gray: #f8f9fa;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-800: #343a40;
            --text-color: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
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

        .form-group {
            margin-bottom: 25px;
            text-align: center;
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
            border: 1px solid var(--border);
            border-radius: 4px;
            background-color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .file-input-container {
            position: relative;
            width: 100%;
            border: 2px dashed var(--border);
            border-radius: 6px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-input-container:hover {
            border-color: var(--primary);
        }

        .file-input-container.active {
            border-color: var(--success);
            background-color: rgba(16, 185, 129, 0.05);
        }

        .file-input-icon {
            font-size: 40px;
            color: var(--primary);
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
            color: var(--error);
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
            background-color: var(--primary);
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

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .excel-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            animation: fadeIn 0.6s ease;
        }

        .excel-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .excel-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .excel-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .excel-content {
            padding: 30px;
        }

        .excel-options {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .excel-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }

        .excel-wave svg {
            display: block;
            width: calc(100% + 1.3px);
            height: 46px;
            transform: scaleY(-1);
        }

        .excel-wave .shape-fill {
            fill: #FFFFFF;
        }

        footer {
            text-align: center;
        }

        footer .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .excel-content {
                padding: 20px;
            }

            .excel-title {
                font-size: 1.8rem;
            }

            .excel-subtitle {
                font-size: 1rem;
            }

            h1 {
                font-size: 20px;
            }

            .file-input-container {
                padding: 20px 15px;
            }
        }

        @media (max-width: 480px) {
            .excel-title {
                font-size: 1.5rem;
            }

            .excel-subtitle {
                font-size: 0.9rem;
            }

            main {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <main>
            <div class="main-content">
                <div class="excel-container">
                    <div class="excel-header">
                        <h1 class="excel-title">Importar Arquivo CSV</h1>
                        <p class="excel-subtitle text-white">
                            <i class="fas fa-file-csv" style="font-size: 24px; margin-right: 8px;"></i>
                            Selecione o arquivo CSV para importar
                        </p>
                        <div class="excel-wave">
                            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="excel-content">
                        <div class="excel-options">
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
                                        include_once 'connection.php';
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

                                <div class="form-group">
                                    <button type="submit" class="btn" id="submitBtn">
                                        <i class="fas fa-upload"></i>
                                        Importar Arquivo
                                    </button>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="fas fa-home me-1"></i> Página Inicial
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <?php require_once 'footer.php'; ?>
        </footer>
    </div>

    <!-- Adicionando Bootstrap JS e Popper.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
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

            // Adicionar efeito ao botão de voltar
            const btnBack = document.querySelector('.btn-back');
            if (btnBack) {
                btnBack.addEventListener('mouseenter', function() {
                    this.querySelector('i').style.transform = 'translateX(-3px)';
                    this.querySelector('i').style.transition = 'transform 0.3s';
                });

                btnBack.addEventListener('mouseleave', function() {
                    this.querySelector('i').style.transform = 'translateX(0)';
                });
            }
        });
    </script>
</body>

</html>