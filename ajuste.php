<?php
$titulo = "Cadastro de Usuários";
include_once "header.php";
include_once "connection.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">



<style>
    :root {
        --primary-color: #3498db;
        --primary-dark: #2980b9;
        --secondary-color: #e7e9eb;
        --text-color: #333;
        --error-color: #e74c3c;
        --success-color: #2ecc71;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: #f5f7fa;
        color: var(--text-color);
        line-height: 1.6;
    }

    .container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h2 {
        font-weight: 600;
        font-size: 1.5rem;
    }

    .card-content {
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }

    .form-col {
        flex: 1;
        padding: 0 10px;
        min-width: 250px;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    input,
    select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #f9f9f9;
        font-size: 1rem;
    }

    input:focus,
    select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    .switch-field {
        display: flex;
        margin-top: 0.5rem;
    }

    .switch-field input {
        position: absolute !important;
        clip: rect(0, 0, 0, 0);
        height: 1px;
        width: 1px;
        border: 0;
        overflow: hidden;
    }

    .switch-field label {
        background-color: var(--secondary-color);
        color: var(--text-color);
        text-align: center;
        padding: 0.5rem 1rem;
        margin-right: -1px;
        transition: all 0.1s ease-in-out;
        font-weight: normal;
    }

    .switch-field label:first-of-type {
        border-radius: 4px 0 0 4px;
    }

    .switch-field label:last-of-type {
        border-radius: 0 4px 4px 0;
    }

    .switch-field input:checked+label {
        background-color: var(--primary-color);
        color: #fff;
        box-shadow: none;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: background-color 0.2s;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
    }

    .btn-outline {
        background-color: transparent;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline:hover {
        background-color: rgba(52, 152, 219, 0.1);
    }

    .actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .required::after {
        content: "*";
        color: var(--error-color);
        margin-left: 2px;
    }

    .helper-text {
        font-size: 0.8rem;
        color: #777;
        margin-top: 0.3rem;
    }

    .error-text {
        font-size: 0.8rem;
        color: var(--error-color);
        margin-top: 0.3rem;
        display: none;
    }

    input.error {
        border-color: var(--error-color);
    }

    @media (max-width: 768px) {
        .form-col {
            flex: 0 0 100%;
            margin-bottom: 1rem;
        }

        .actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
</style>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Cadastro de Usuário</h2>
            </div>
            <div class="card-content">
                <form id="userForm">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="nome" class="required">Nome completo</label>
                                <input type="text" id="nome" name="nome" required placeholder="Digite o nome completo">
                                <div id="nome-error" class="error-text">Nome é obrigatório</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email" class="required">E-mail</label>
                                <input type="email" id="email" name="email" required placeholder="email@exemplo.com">
                                <div id="email-error" class="error-text">E-mail inválido</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="empresa" class="required">Empresa</label>
                                <select id="empresa" name="empresaid" required>
                                    <option value="">Selecione uma empresa</option>
                                    <!-- Opções serão carregadas via JavaScript -->
                                </select>
                                <div id="empresa-error" class="error-text">Empresa é obrigatória</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="regras">Regras</label>
                                <select id="regras" name="regras">
                                    <option value="">Selecione uma regra</option>
                                    <option value="ADMIN">Administrador</option>
                                    <option value="GESTOR">Gestor</option>
                                    <option value="USUARIO">Usuário padrão</option>
                                    <option value="VISITANTE">Visitante</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="senha" class="required">Senha</label>
                                <input type="password" id="senha" name="senha" required placeholder="Digite a senha">
                                <div class="helper-text">Mínimo de 8 caracteres, incluindo letras e números</div>
                                <div id="senha-error" class="error-text">Senha deve ter no mínimo 8 caracteres</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="confirmarSenha" class="required">Confirmar senha</label>
                                <input type="password" id="confirmarSenha" name="confirmarSenha" required placeholder="Confirme a senha">
                                <div id="confirmarSenha-error" class="error-text">As senhas não conferem</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label>Status do usuário</label>
                                <div class="switch-field">
                                    <input type="radio" id="ativo-sim" name="ativo" value="S" checked>
                                    <label for="ativo-sim">Ativo</label>
                                    <input type="radio" id="ativo-nao" name="ativo" value="N">
                                    <label for="ativo-nao">Inativo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="button" class="btn btn-outline" id="limparBtn">Limpar</button>
                        <button type="submit" class="btn btn-primary" id="salvarBtn">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Função para carregar empresas (simulado)
            carregarEmpresas();

            // Configurar validações
            setupValidation();

            // Configurar botões
            setupButtons();
        });

        function carregarEmpresas() {
            // Simular carregamento de empresas de uma API
            const empresas = [{
                    id: 1,
                    nome: 'Empresa A'
                },
                {
                    id: 2,
                    nome: 'Empresa B'
                },
                {
                    id: 3,
                    nome: 'Empresa C'
                },
            ];

            const selectEmpresa = document.getElementById('empresa');

            empresas.forEach(empresa => {
                const option = document.createElement('option');
                option.value = empresa.id;
                option.textContent = empresa.nome;
                selectEmpresa.appendChild(option);
            });
        }

        function setupValidation() {
            const form = document.getElementById('userForm');

            // Validação ao enviar o formulário
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                let valid = true;

                // Validar nome
                const nome = document.getElementById('nome');
                if (!nome.value.trim()) {
                    showError(nome, 'nome-error');
                    valid = false;
                } else {
                    hideError(nome, 'nome-error');
                }

                // Validar email
                const email = document.getElementById('email');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email.value.trim() || !emailRegex.test(email.value)) {
                    showError(email, 'email-error');
                    valid = false;
                } else {
                    hideError(email, 'email-error');
                }

                // Validar empresa
                const empresa = document.getElementById('empresa');
                if (!empresa.value) {
                    showError(empresa, 'empresa-error');
                    valid = false;
                } else {
                    hideError(empresa, 'empresa-error');
                }

                // Validar senha
                const senha = document.getElementById('senha');
                if (senha.value.length < 8) {
                    showError(senha, 'senha-error');
                    valid = false;
                } else {
                    hideError(senha, 'senha-error');
                }

                // Validar confirmação de senha
                const confirmarSenha = document.getElementById('confirmarSenha');
                if (confirmarSenha.value !== senha.value) {
                    showError(confirmarSenha, 'confirmarSenha-error');
                    valid = false;
                } else {
                    hideError(confirmarSenha, 'confirmarSenha-error');
                }

                if (valid) {
                    // Preparar dados para envio
                    const formData = new FormData(form);
                    const userData = {
                        nome: formData.get('nome'),
                        email: formData.get('email'),
                        empresaid: formData.get('empresaid'),
                        regras: formData.get('regras'),
                        senha: formData.get('senha'),
                        ativo: formData.get('ativo'),
                        // Campos gerados automaticamente
                        uuid: generateUUID(),
                        criado_em: new Date().toISOString(),
                        criado_por: 'sistema', // Normalmente seria o usuário logado
                        atualizado_em: new Date().toISOString(),
                        atualizado_por: 'sistema' // Normalmente seria o usuário logado
                    };

                    // Em um cenário real, enviar para API
                    console.log('Dados a serem enviados:', userData);

                    // Simulação de envio para API
                    enviarDadosParaAPI(userData);
                }
            });

            // Validação em tempo real para certos campos
            document.getElementById('email').addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!this.value.trim() || !emailRegex.test(this.value)) {
                    showError(this, 'email-error');
                } else {
                    hideError(this, 'email-error');
                }
            });

            document.getElementById('confirmarSenha').addEventListener('input', function() {
                const senha = document.getElementById('senha').value;
                if (this.value !== senha) {
                    showError(this, 'confirmarSenha-error');
                } else {
                    hideError(this, 'confirmarSenha-error');
                }
            });
        }

        function setupButtons() {
            // Botão Limpar
            document.getElementById('limparBtn').addEventListener('click', function() {
                document.getElementById('userForm').reset();
                clearAllErrors();
            });
        }

        function showError(input, errorId) {
            input.classList.add('error');
            document.getElementById(errorId).style.display = 'block';
        }

        function hideError(input, errorId) {
            input.classList.remove('error');
            document.getElementById(errorId).style.display = 'none';
        }

        function clearAllErrors() {
            const errorTexts = document.querySelectorAll('.error-text');
            const inputs = document.querySelectorAll('input, select');

            errorTexts.forEach(errorText => {
                errorText.style.display = 'none';
            });

            inputs.forEach(input => {
                input.classList.remove('error');
            });
        }

        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        function enviarDadosParaAPI(dados) {
            // Simulação de envio para API
            setTimeout(() => {
                alert('Usuário cadastrado com sucesso!');
                document.getElementById('userForm').reset();
            }, 1000);
        }
    </script>
</body>

</html>