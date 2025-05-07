<?php
$titulo = "Gerenciar Propagandas";
include_once 'header.php';
include_once 'connection.php';

// Verificar se existe pasta de uploads e criar se não existir
$uploadDir = 'uploads/propagandas/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Processar exclusão de propaganda
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    try {
        // Buscar nome da imagem antes de excluir
        $stmt = $pdo->prepare("SELECT imagem FROM propagandas WHERE id = ?");
        $stmt->execute([$_GET['excluir']]);
        $imagem = $stmt->fetchColumn();
        
        // Excluir registro do banco
        $stmt = $pdo->prepare("DELETE FROM propagandas WHERE id = ?");
        $stmt->execute([$_GET['excluir']]);
        
        // Remover arquivo se existir
        if ($imagem && file_exists($uploadDir . $imagem)) {
            unlink($uploadDir . $imagem);
        }
        
        $mensagem = "Propaganda excluída com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir propaganda: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Processar alteração de status
if (isset($_GET['alterarstatus']) && is_numeric($_GET['alterarstatus'])) {
    try {
        // Buscar status atual
        $stmt = $pdo->prepare("SELECT ativo FROM propagandas WHERE id = ?");
        $stmt->execute([$_GET['alterarstatus']]);
        $statusAtual = $stmt->fetchColumn();
        
        // Inverter status
        $novoStatus = $statusAtual ? 0 : 1;
        
        // Atualizar no banco
        $stmt = $pdo->prepare("UPDATE propagandas SET ativo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$novoStatus, $_GET['alterarstatus']]);
        
        $mensagem = "Status da propaganda atualizado com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem = "Erro ao atualizar status: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Processar envio de nova propaganda ou edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = htmlspecialchars($_POST['titulo']);
    $descricao = htmlspecialchars($_POST['descricao']);
    $ordem = isset($_POST['ordem']) ? intval($_POST['ordem']) : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    
    // Flag para controlar se há upload de arquivo
    $temArquivo = isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE;
    
    try {
        // Processar o upload da imagem se houver
        $nomeArquivo = null;
        if ($temArquivo) {
            // Verificar extensão
            $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($extensao, $extensoesPermitidas)) {
                throw new Exception("Formato de arquivo não permitido. Use: " . implode(', ', $extensoesPermitidas));
            }
            
            // Gerar nome único para o arquivo
            $nomeArquivo = 'propaganda_' . time() . '_' . uniqid() . '.' . $extensao;
            
            // Mover o arquivo para a pasta de uploads
            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadDir . $nomeArquivo)) {
                throw new Exception("Falha ao salvar o arquivo. Verifique as permissões da pasta.");
            }
        }
        
        // Decidir entre update ou insert
        if ($id) {
            // Se for edição
            if ($temArquivo) {
                // Se tem nova imagem, buscar nome da anterior para excluir
                $stmt = $pdo->prepare("SELECT imagem FROM propagandas WHERE id = ?");
                $stmt->execute([$id]);
                $imagemAntiga = $stmt->fetchColumn();
                
                // Atualizar com nova imagem
                $stmt = $pdo->prepare("UPDATE propagandas SET 
                    titulo = ?, descricao = ?, imagem = ?, ativo = ?, ordem = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?");
                $stmt->execute([$titulo, $descricao, $nomeArquivo, $ativo, $ordem, $id]);
                
                // Remover imagem antiga
                if ($imagemAntiga && file_exists($uploadDir . $imagemAntiga)) {
                    unlink($uploadDir . $imagemAntiga);
                }
            } else {
                // Atualizar sem alterar imagem
                $stmt = $pdo->prepare("UPDATE propagandas SET 
                    titulo = ?, descricao = ?, ativo = ?, ordem = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?");
                $stmt->execute([$titulo, $descricao, $ativo, $ordem, $id]);
            }
            
            $mensagem = "Propaganda atualizada com sucesso!";
        } else {
            // Se for nova propaganda
            if (!$temArquivo) {
                throw new Exception("É necessário enviar uma imagem para a propaganda.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO propagandas (titulo, descricao, imagem, ativo, ordem) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $nomeArquivo, $ativo, $ordem]);
            
            $mensagem = "Nova propaganda cadastrada com sucesso!";
        }
        
        $tipoMensagem = "success";
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = "danger";
        
        // Remover arquivo enviado em caso de erro no banco
        if (isset($nomeArquivo) && file_exists($uploadDir . $nomeArquivo)) {
            unlink($uploadDir . $nomeArquivo);
        }
    }
}

// Buscar propaganda para edição
$propagandaEdicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM propagandas WHERE id = ?");
        $stmt->execute([$_GET['editar']]);
        $propagandaEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar propaganda para edição: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Buscar todas as propagandas para listar
try {
    $stmt = $pdo->query("SELECT * FROM propagandas ORDER BY ordem, titulo");
    $propagandas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao listar propagandas: " . $e->getMessage();
    $tipoMensagem = "danger";
    $propagandas = [];
}
?>

<div class="container mt-4">
    <h1 class="text-center mb-4" style="color: white;"><?= $titulo ?></h1>
    
    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show">
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Formulário de cadastro/edição -->
    <div class="card mb-4 shadow">
        <div class="card-header bg-primary text-white">
            <?= $propagandaEdicao ? 'Editar Propaganda' : 'Nova Propaganda' ?>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?php if ($propagandaEdicao): ?>
                    <input type="hidden" name="id" value="<?= $propagandaEdicao['id'] ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="titulo" class="form-label">Título da Propaganda</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" 
                            value="<?= $propagandaEdicao ? htmlspecialchars($propagandaEdicao['titulo']) : '' ?>" 
                            required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="ordem" class="form-label">Ordem de Exibição</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" 
                            value="<?= $propagandaEdicao ? $propagandaEdicao['ordem'] : '0' ?>" 
                            min="0">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                <?= (!$propagandaEdicao || $propagandaEdicao['ativo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= $propagandaEdicao ? htmlspecialchars($propagandaEdicao['descricao']) : '' ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="imagem" class="form-label">
                        <?= $propagandaEdicao ? 'Alterar Imagem (opcional)' : 'Imagem da Propaganda' ?>
                    </label>
                    <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*"
                        <?= $propagandaEdicao ? '' : 'required' ?>>
                    <div class="form-text">Formatos suportados: JPG, PNG, GIF.</div>
                </div>
                
                <?php if ($propagandaEdicao && $propagandaEdicao['imagem']): ?>
                    <div class="mb-3">
                        <label class="form-label">Imagem Atual</label>
                        <div class="border p-2 rounded">
                            <img src="<?= $uploadDir . htmlspecialchars($propagandaEdicao['imagem']) ?>" 
                                class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> 
                        <?= $propagandaEdicao ? 'Atualizar Propaganda' : 'Salvar Nova Propaganda' ?>
                    </button>
                    <?php if ($propagandaEdicao): ?>
                        <a href="propagandas.php" class="btn btn-outline-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de propagandas -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            Propagandas Cadastradas
        </div>
        <div class="card-body">
            <?php if (empty($propagandas)): ?>
                <div class="alert alert-info">
                    Nenhuma propaganda cadastrada. Adicione sua primeira propaganda usando o formulário acima.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Ordem</th>
                                <th style="width: 100px;">Imagem</th>
                                <th>Título</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propagandas as $propaganda): ?>
                                <tr>
                                    <td class="text-center"><?= $propaganda['ordem'] ?></td>
                                    <td>
                                        <?php if ($propaganda['imagem'] && file_exists($uploadDir . $propaganda['imagem'])): ?>
                                            <img src="<?= $uploadDir . htmlspecialchars($propaganda['imagem']) ?>" 
                                                class="img-thumbnail" style="max-height: 60px;">
                                        <?php else: ?>
                                            <span class="badge bg-danger">Sem imagem</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($propaganda['titulo']) ?></strong>
                                        <?php if ($propaganda['descricao']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($propaganda['descricao']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($propaganda['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?alterarstatus=<?= $propaganda['id'] ?>" class="btn btn-outline-<?= $propaganda['ativo'] ? 'secondary' : 'success' ?>" title="<?= $propaganda['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                                <i class="bi bi-<?= $propaganda['ativo'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                            </a>
                                            <a href="?editar=<?= $propaganda['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?excluir=<?= $propaganda['id'] ?>" class="btn btn-outline-danger" 
                                               onclick="return confirm('Tem certeza que deseja excluir esta propaganda?')" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-4 mb-5 text-center">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-house"></i> Voltar para Início
        </a>
        <a href="configuracoes.php" class="btn btn-outline-primary">
            <i class="bi bi-gear"></i> Configurações da Tabela
        </a>
    </div>
</div>

<?php include_once 'footer.php'; ?>