<?php
$titulo = "Cadastro de Empresa";
include_once "header.php";
include_once "connection.php";
?>
<style>
    .required-field::after {
        content: " *";
        color: red;
    }

    .card-header {
        background-color: #f8f9fa;
        font-weight: bold;
    }
</style>
</head>

<body>
    <div class="container py-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-header py-3">
                <h4 class="mb-0">Cadastro de Empresa</h4>
            </div>
            <div class="card-body">
                <form id="formEmpresa" action="processar_empresa.php" method="POST">
                    <!-- Campos ocultos -->
                    <input type="hidden" name="uuid" value="<?php echo uniqid() . '-' . bin2hex(random_bytes(8)); ?>">

                    <!-- Dados Principais -->
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <h5>Dados Principais</h5>
                            <hr>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="razao_social" class="form-label required-field">Razão Social</label>
                            <input type="text" class="form-control" id="razao_social" name="razao_social" maxlength="120" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fantasia" class="form-label required-field">Nome Fantasia</label>
                            <input type="text" class="form-control" id="fantasia" name="fantasia" maxlength="100" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cpf_cnpj" class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" maxlength="20">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="rg_ie" class="form-label">RG/IE</label>
                            <input type="text" class="form-control" id="rg_ie" name="rg_ie" maxlength="20">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="ativo" class="form-label required-field">Status</label>
                            <select class="form-select" id="ativo" name="ativo" required>
                                <option value="S" selected>Ativo</option>
                                <option value="N">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Contato -->
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <h5>Contato</h5>
                            <hr>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" maxlength="30">
                        </div>

                        <div class="col-md-8 mb-3">
                            <label for="email" class="form-label required-field">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="150" required>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <h5>Endereço</h5>
                            <hr>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="cep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cep" name="cep" maxlength="20">
                        </div>

                        <div class="col-md-7 mb-3">
                            <label for="endereco" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" maxlength="100">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" maxlength="20">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" maxlength="50">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" maxlength="100">
                            <input type="hidden" id="cidadeid" name="cidadeid">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Selecione</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="window.history.back();">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <script>
        $(document).ready(function() {
            // Aplicar máscaras
            $('#telefone').mask('(00) 00000-0000');
            $('#cep').mask('00000-000');

            // Máscara dinâmica para CPF/CNPJ
            var cpfCnpjOptions = {
                onKeyPress: function(cpf, e, field, options) {
                    var masks = ['000.000.000-00', '00.000.000/0000-00'];
                    var mask = (cpf.length > 14) ? masks[1] : masks[0];
                    $('#cpf_cnpj').mask(mask, options);
                }
            };
            $('#cpf_cnpj').mask('000.000.000-00', cpfCnpjOptions);

            // Busca CEP
            $('#cep').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length == 8) {
                    $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                        if (!data.erro) {
                            $('#endereco').val(data.logradouro);
                            $('#bairro').val(data.bairro);
                            $('#cidade').val(data.localidade);
                            $('#estado').val(data.uf);
                        }
                    });
                }
            });

            // Validação do formulário
            $('#formEmpresa').submit(function(event) {
                const razaoSocial = $('#razao_social').val().trim();
                const fantasia = $('#fantasia').val().trim();
                const email = $('#email').val().trim();

                if (!razaoSocial) {
                    alert('Razão Social é obrigatória');
                    $('#razao_social').focus();
                    event.preventDefault();
                    return false;
                }

                if (!fantasia) {
                    alert('Nome Fantasia é obrigatório');
                    $('#fantasia').focus();
                    event.preventDefault();
                    return false;
                }

                if (!email) {
                    alert('Email é obrigatório');
                    $('#email').focus();
                    event.preventDefault();
                    return false;
                }

                return true;
            });
        });
    </script>
</body>

</html>