<?php
$titulo = "Configurações Tabela";
include_once 'connection.php';
include_once 'header.php';


// Recuperar valores anteriores (se existirem) para preencher os campos
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
$tempo = isset($_GET['tempo']) ? intval($_GET['tempo']) : 10;
$tema = isset($_GET['tema']) ? $_GET['tema'] : 'padrao';
$grupo_selecionado = isset($_GET['grupo']) ? $_GET['grupo'] : 'todos';
$atualizacao_auto = isset($_GET['atualizacao_auto']) ? intval($_GET['atualizacao_auto']) : 10; // Valor padrão: 10 minutos

// Novos parâmetros para propagandas
// Importante: Mudamos para verificar se o parâmetro existe em $_GET, não apenas o valor
// $propagandas_ativas = 1; // Sempre ativado por padrão
$propagandas_ativas = isset($_GET['propagandas_ativas']) && $_GET['propagandas_ativas'] == '1' ? 1 : 0;
$tempo_propagandas = isset($_GET['tempo_propagandas']) ? intval($_GET['tempo_propagandas']) : 5; // Tempo em segundos

// Lista de temas disponíveis
$temas = [
    'padrao' => 'Padrão (Azul)',
    'supermercado' => 'Supermercado (Verde)',
    'padaria' => 'Padaria (Amarelo)'
];

// Opções de tempo de atualização automática (em minutos)
$opcoes_atualizacao = [
    1 => '1 minuto',
    5 => '5 minutos',
    10 => '10 minutos',
    15 => '15 minutos',
    30 => '30 minutos',
    60 => '1 hora',
    0 => 'Desativar'
];

// Buscar grupos disponíveis no banco de dados
$grupos = ['todos' => 'Todos os Grupos'];
try {
    $stmt = $pdo->query("SELECT id, nome FROM grupos ORDER BY nome");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grupos[$row['id']] = $row['nome'];
    }
} catch (PDOException $e) {
    // Se ocorrer erro, mantém apenas a opção "Todos"
}
?>

<div class="container mt-5">
    <h2 class="mb-4 text-center" style="color: white;">Configurações da Tabela de Preços</h2>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <!-- Formulário Unificado -->
                    <form id="formConfiguracoes" action="tabela_precos.php" method="GET">
                        <!-- Seleção de Grupo -->
                        <div class="mb-3">
                            <label for="grupo" class="form-label fw-bold">Grupo a ser exibido:</label>
                            <select class="form-select" id="grupo" name="grupo">
                                <?php foreach ($grupos as $id => $nome): ?>
                                    <option value="<?= $id ?>" <?= ($grupo_selecionado == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nome) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Escolha um grupo específico ou todos os grupos.</div>
                        </div>

                        <!-- Limite de Itens -->
                        <div class="mb-3">
                            <label for="limite" class="form-label fw-bold">Quantidade de itens por grupo:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="limite" name="limite"
                                    min="1" value="<?php echo $limite; ?>" placeholder="Ex: 10" required>
                                <span class="input-group-text">itens</span>
                            </div>
                            <div class="form-text">Defina quantos itens serão exibidos em cada grupo.</div>
                        </div>

                        <!-- Tempo por Slide -->
                        <div class="mb-3">
                            <label for="tempo" class="form-label fw-bold">Tempo por slide (segundos):</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tempo" name="tempo"
                                    min="1" value="<?php echo $tempo; ?>" placeholder="Ex: 60" required>
                                <span class="input-group-text">segundos</span>
                            </div>
                            <div class="form-text">Defina o tempo em segundos que cada slide ficará visível.</div>
                        </div>

                        <!-- Controle de Propagandas-->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Exibição de Propagandas:</label>
                            <!-- Importante: Alteramos para usar um campo oculto que sempre será enviado -->
                            <input type="hidden" name="propagandas_ativas" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="propagandas_ativas" name="propagandas_ativas" value="1"
                                    <?= $propagandas_ativas ? 'checked' : '' ?>>
                                <label class="form-check-label" for="propagandas_ativas">Ativar exibição de propagandas</label>
                            </div>
                            <div class="input-group mt-2">
                                <label for="tempo_propagandas" class="form-label">Tempo de exibição (segundos):</label>
                                <input type="number" class="form-control" id="tempo_propagandas" name="tempo_propagandas"
                                    min="1" value="<?php echo $tempo_propagandas; ?>" placeholder="Ex: 5"
                                    <?= $propagandas_ativas ? '' : 'disabled' ?>>
                                <span class="input-group-text">segundos</span>
                            </div>
                            <div class="form-text">Defina se as propagandas serão exibidas e por quanto tempo.</div>
                        </div>

                        <!-- Tempo de Atualização Automática -->
                        <div class="mb-3">
                            <label for="atualizacao_auto" class="form-label fw-bold">Atualização automática:</label>
                            <select class="form-select" id="atualizacao_auto" name="atualizacao_auto">
                                <?php foreach ($opcoes_atualizacao as $valor => $texto): ?>
                                    <option value="<?= $valor ?>" <?= ($atualizacao_auto == $valor) ? 'selected' : '' ?>>
                                        <?= $texto ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Defina com que frequência a tabela será atualizada automaticamente.</div>
                        </div>

                        <!-- Seleção de Tema -->
                        <div class="mb-4">
                            <label for="tema" class="form-label fw-bold">Tema visual:</label>
                            <select class="form-select" id="tema" name="tema">
                                <?php foreach ($temas as $valor => $nome): ?>
                                    <option value="<?= $valor ?>" <?= ($tema == $valor) ? 'selected' : '' ?>>
                                        <?= $nome ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Escolha o estilo visual para a tabela de preços.</div>
                        </div>

                        <!-- Botão de Visualização -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-eye"></i> Visualizar Tabela
                            </button>
                        </div>
                    </form>

                    <!-- Visão Prévia dos Temas -->
                    <div class="mt-4">
                        <h5 class="border-bottom pb-2">Pré-visualização dos temas</h5>
                        <div class="row mt-3">
                            <?php foreach ($temas as $valor => $nome):
                                // Define as cores do tema para a prévia
                                $corFundo = $valor == 'padrao' ? 'bg-primary' : ($valor == 'supermercado' ? 'bg-success' : 'bg-warning');
                                $corTexto = $valor == 'padaria' ? 'text-dark' : 'text-white';
                            ?>
                                <div class="col-md-4 mb-2">
                                    <div class="card border">
                                        <div class="card-header <?= $corFundo ?> <?= $corTexto ?> text-center">
                                            <?= $nome ?>
                                        </div>
                                        <div class="card-body p-2 text-center" style="font-size: 0.8rem;">
                                            <span class="badge <?= $corFundo ?> <?= $corTexto ?> d-block mb-1">Amostra</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Botões adicionais -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-house"></i> Página Inicial
                        </a>
                        <a href="propagandas.php" class="btn btn-outline-success">
                            <i class="bi bi-image"></i> Gerenciar Propagandas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validação do formulário
        document.getElementById('formConfiguracoes').addEventListener('submit', function(event) {
            const limite = document.getElementById('limite').value;
            const tempo = document.getElementById('tempo').value;
            const propagandasAtivas = document.getElementById('propagandas_ativas').checked;
            const tempoPropagandas = document.getElementById('tempo_propagandas').value;

            if (!limite || parseInt(limite) <= 0) {
                event.preventDefault();
                alert('Por favor, insira um número válido de itens maior que zero.');
                document.getElementById('limite').focus();
                return false;
            }

            if (!tempo || parseInt(tempo) <= 0) {
                event.preventDefault();
                alert('Por favor, insira um tempo válido em segundos maior que zero.');
                document.getElementById('tempo').focus();
                return false;
            }

            if (propagandasAtivas && (!tempoPropagandas || parseInt(tempoPropagandas) <= 0)) {
                event.preventDefault();
                alert('Por favor, insira um tempo válido para as propagandas em segundos maior que zero.');
                document.getElementById('tempo_propagandas').focus();
                return false;
            }

            // Se tudo estiver correto, o formulário será enviado normalmente
            return true;
        });

        // Habilitar/desabilitar campo de tempo de propagandas
        document.getElementById('propagandas_ativas').addEventListener('change', function() {
            document.getElementById('tempo_propagandas').disabled = !this.checked;
        });

        // Visualização rápida do tema selecionado
        document.getElementById('tema').addEventListener('change', function() {
            const temaAtual = this.value;
            const exemplos = document.querySelectorAll('.card-header');

            exemplos.forEach(function(exemplo) {
                exemplo.classList.remove('bg-primary', 'bg-success', 'bg-warning', 'text-white', 'text-dark');

                if (temaAtual === 'padrao') {
                    exemplo.classList.add('bg-primary', 'text-white');
                } else if (temaAtual === 'supermercado') {
                    exemplo.classList.add('bg-success', 'text-white');
                } else if (temaAtual === 'padaria') {
                    exemplo.classList.add('bg-warning', 'text-dark');
                } else if (temaAtual === 'informatica') {
                    exemplo.classList.add('bg-secondary', 'text-white');
                }
            });
        });
    });
</script>

<?php
include_once 'footer.php';
?>
</body>

</html>