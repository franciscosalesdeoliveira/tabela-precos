<?php
$titulo = "Visualizar Empresa";
require_once 'connection.php';
require_once 'header.php';

// Verificar se foi passado um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: pesquisa_empresas.php');
    exit;
}

$empresa_id = (int)$_GET['id'];

try {
    // Buscar dados da empresa
    $sql = "SELECT * FROM empresas WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $empresa_id]);
    $empresa = $stmt->fetch();

    if (!$empresa) {
        header('Location: pesquisa_empresas.php?erro=empresa_nao_encontrada');
        exit;
    }
} catch (Exception $e) {
    header('Location: pesquisa_empresas.php?erro=erro_busca');
    exit;
}

// Função para formatar CNPJ
function formatarCNPJ($cnpj)
{
    if (strlen($cnpj) == 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    } elseif (strlen($cnpj) == 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
    }
    return $cnpj;
}

// Função para formatar telefone
function formatarTelefone($telefone)
{
    $telefone = preg_replace('/\D/', '', $telefone);
    if (strlen($telefone) == 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
    } elseif (strlen($telefone) == 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }
    return $telefone;
}

// Função para formatar CEP
function formatarCEP($cep)
{
    $cep = preg_replace('/\D/', '', $cep);
    if (strlen($cep) == 8) {
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
    }
    return $cep;
}

// Estados brasileiros
$estados = [
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AP' => 'Amapá',
    'AM' => 'Amazonas',
    'BA' => 'Bahia',
    'CE' => 'Ceará',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MT' => 'Mato Grosso',
    'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PR' => 'Paraná',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'SC' => 'Santa Catarina',
    'SP' => 'São Paulo',
    'SE' => 'Sergipe',
    'TO' => 'Tocantins'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Empresa</title>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --bg-color: #f8f9fa;
            --text-color: #333;
            --border-color: #ddd;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .page-title {
            color: var(--primary-dark);
            margin: 0;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .section-title {
            color: var(--primary-dark);
            margin: 0;
            font-size: 1.3em;
        }

        .section-icon {
            font-size: 1.5em;
            color: var(--primary-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #495057;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid var(--primary-color);
            min-height: 20px;
        }

        .info-value.empty {
            color: #6c757d;
            font-style: italic;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            width: fit-content;
        }

        .status-ativo {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 2px solid rgba(40, 167, 69, 0.3);
        }

        .status-inativo {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 2px solid rgba(220, 53, 69, 0.3);
        }

        .timestamp-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid var(--info-color);
        }

        .timestamp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .timestamp-item {
            text-align: center;
        }

        .timestamp-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .timestamp-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
        }

        .uuid-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            border-left: 4px solid var(--info-color);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #0c5460;
            border-left-color: var(--info-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .btn-group {
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .timestamp-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                justify-content: center;
            }
        }

        .loading {
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>Painel Administrativo</h1>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2 class="page-title">📋 Detalhes da Empresa</h2>
            <div class="btn-group">
                <a href="editar_empresa.php?id=<?= $empresa['id'] ?>" class="btn btn-warning">
                    ✏️ Editar
                </a>
                <a href="pesquisa_empresas.php" class="btn btn-secondary">
                    ← Voltar à Pesquisa
                </a>
            </div>
        </div>

        <!-- Informações Básicas -->
        <div class="card">
            <div class="section-header">
                <span class="section-icon">🏢</span>
                <h3 class="section-title">Informações Básicas</h3>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label class="info-label">Razão Social</label>
                    <div class="info-value">
                        <?= htmlspecialchars($empresa['razao_social']) ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Nome Fantasia</label>
                    <div class="info-value <?= empty($empresa['fantasia']) ? 'empty' : '' ?>">
                        <?= htmlspecialchars($empresa['fantasia'] ?: 'Não informado') ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">CNPJ/CPF</label>
                    <div class="info-value">
                        <?= formatarCNPJ($empresa['cpf_cnpj']) ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Status</label>
                    <div class="info-value">
                        <span class="status-badge <?= $empresa['ativo'] == 'S' ? 'status-ativo' : 'status-inativo' ?>">
                            <?php if ($empresa['ativo'] == 'S'): ?>
                                ✅ Ativo
                            <?php else: ?>
                                ❌ Inativo
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações de Contato -->
        <div class="card">
            <div class="section-header">
                <span class="section-icon">📞</span>
                <h3 class="section-title">Informações de Contato</h3>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label class="info-label">E-mail</label>
                    <div class="info-value <?= empty($empresa['email']) ? 'empty' : '' ?>">
                        <?php if (!empty($empresa['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($empresa['email']) ?>" style="color: var(--primary-color); text-decoration: none;">
                                <?= htmlspecialchars($empresa['email']) ?>
                            </a>
                        <?php else: ?>
                            Não informado
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Telefone</label>
                    <div class="info-value <?= empty($empresa['telefone']) ? 'empty' : '' ?>">
                        <?php if (!empty($empresa['telefone'])): ?>
                            <a href="tel:<?= preg_replace('/\D/', '', $empresa['telefone']) ?>" style="color: var(--primary-color); text-decoration: none;">
                                <?= formatarTelefone($empresa['telefone']) ?>
                            </a>
                        <?php else: ?>
                            Não informado
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Site</label>
                    <div class="info-value <?= empty($empresa['site']) ? 'empty' : '' ?>">
                        <?php if (!empty($empresa['site'])): ?>
                            <a href="<?= htmlspecialchars($empresa['site']) ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                <?= htmlspecialchars($empresa['site']) ?> 🔗
                            </a>
                        <?php else: ?>
                            Não informado
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div class="card">
            <div class="section-header">
                <span class="section-icon">📍</span>
                <h3 class="section-title">Endereço</h3>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label class="info-label">CEP</label>
                    <div class="info-value <?= empty($empresa['cep']) ? 'empty' : '' ?>">
                        <?= !empty($empresa['cep']) ? formatarCEP($empresa['cep']) : 'Não informado' ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Endereço</label>
                    <div class="info-value <?= empty($empresa['logradouro']) ? 'empty' : '' ?>">
                        <?= htmlspecialchars($empresa['logradouro'] ?: 'Não informado') ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Número</label>
                    <div class="info-value <?= empty($empresa['numero']) ? 'empty' : '' ?>">
                        <?= htmlspecialchars($empresa['numero'] ?: 'Não informado') ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Complemento</label>
                    <div class="info-value <?= empty($empresa['complemento']) ? 'empty' : '' ?>">
                        <?= htmlspecialchars($empresa['complemento'] ?: 'Não informado') ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Bairro</label>
                    <div class="info-value <?= empty($empresa['bairro']) ? 'empty' : '' ?>">
                        <?= htmlspecialchars($empresa['bairro'] ?: 'Não informado') ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Cidade</label>
                    <div class="info-value">
                        <?= htmlspecialchars($empresa['cidade']) ?>
                    </div>
                </div>

                <div class="info-item">
                    <label class="info-label">Estado</label>
                    <div class="info-value">
                        <?= htmlspecialchars($empresa['estado']) ?> - <?= $estados[$empresa['estado']] ?? $empresa['estado'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <?php if (!empty($empresa['observacoes'])): ?>
            <div class="card">
                <div class="section-header">
                    <span class="section-icon">📝</span>
                    <h3 class="section-title">Observações</h3>
                </div>

                <div class="info-value" style="white-space: pre-wrap; line-height: 1.6;">
                    <?= htmlspecialchars($empresa['observacoes']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Informações do Sistema -->
        <div class="card">
            <div class="section-header">
                <span class="section-icon">⚙️</span>
                <h3 class="section-title">Informações do Sistema</h3>
            </div>

            <div class="alert alert-info">
                ℹ️ <strong>UUID:</strong> Identificador único universal da empresa
            </div>

            <div class="info-item">
                <label class="info-label">UUID</label>
                <div class="uuid-info">
                    <?= htmlspecialchars($empresa['uuid']) ?>
                </div>
            </div>

            <div class="timestamp-info">
                <div class="timestamp-grid">
                    <div class="timestamp-item">
                        <div class="timestamp-label">📅 Data de Cadastro</div>
                        <div class="timestamp-value">
                            <?php
                            $data_cadastro = new DateTime($empresa['criado_em']);
                            echo $data_cadastro->format('d/m/Y \à\s H:i:s');
                            ?>
                        </div>
                    </div>
                    <div class="timestamp-item">
                        <div class="timestamp-label">🔄 Última Atualização</div>
                        <div class="timestamp-value">
                            <?php
                            if ($empresa['atualizado_em']) {
                                $data_atualizacao = new DateTime($empresa['atualizado_em']);
                                echo $data_atualizacao->format('d/m/Y \à\s H:i:s');
                            } else {
                                echo 'Nunca atualizado';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Finais -->
        <div class="card">
            <div style="text-align: center;">
                <div class="btn-group">
                    <a href="editar_empresa.php?id=<?= $empresa['id'] ?>" class="btn btn-warning">
                        ✏️ Editar Empresa
                    </a>
                    <a href="pesquisa_empresas.php" class="btn btn-primary">
                        🔍 Pesquisar Empresas
                    </a>
                    <a href="cadastro_empresa.php" class="btn btn-secondary">
                        ➕ Nova Empresa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar efeito de hover nos cartões
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'var(--shadow)';
                });
            });

            // Copiar UUID ao clicar
            const uuidElement = document.querySelector('.uuid-info');
            if (uuidElement) {
                uuidElement.style.cursor = 'pointer';
                uuidElement.title = 'Clique para copiar o UUID';

                uuidElement.addEventListener('click', function() {
                    const uuid = this.textContent.trim();

                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(uuid).then(() => {
                            showMessage('UUID copiado para a área de transferência!', 'success');
                        });
                    } else {
                        // Fallback para navegadores mais antigos
                        const textArea = document.createElement('textarea');
                        textArea.value = uuid;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        showMessage('UUID copiado para a área de transferência!', 'success');
                    }
                });
            }

            // Função para exibir mensagens
            function showMessage(message, type = 'info') {
                const messageDiv = document.createElement('div');
                messageDiv.className = `alert alert-${type}`;
                messageDiv.style.position = 'fixed';
                messageDiv.style.top = '20px';
                messageDiv.style.right = '20px';
                messageDiv.style.zIndex = '9999';
                messageDiv.style.maxWidth = '300px';
                messageDiv.innerHTML = message;

                document.body.appendChild(messageDiv);

                setTimeout(() => {
                    messageDiv.remove();
                }, 3000);
            }

            // Animação de entrada dos elementos
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>

</html>