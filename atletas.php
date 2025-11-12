<?php
require_once 'config.php';
verificar_login();

$mensagem = '';
$tipo_mensagem = '';

// Adicionar/Editar atleta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = limpar_input($_POST['nome']);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $data_nascimento = $_POST['data_nascimento'];
    $clube = limpar_input($_POST['clube']);
    $posicao = limpar_input($_POST['posicao']);
    $federacao = limpar_input($_POST['federacao']);
    $status = $_POST['status'];
    
    try {
        if ($id) {
            $sql = "UPDATE atletas SET nome=?, cpf=?, data_nascimento=?, clube=?, posicao=?, federacao=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cpf, $data_nascimento, $clube, $posicao, $federacao, $status, $id]);
            registrar_log($pdo, $_SESSION['usuario_id'], 'Atleta atualizado', 'atletas', $id);
            $mensagem = "Atleta atualizado com sucesso!";
        } else {
            $sql = "INSERT INTO atletas (nome, cpf, data_nascimento, clube, posicao, federacao, status, usuario_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cpf, $data_nascimento, $clube, $posicao, $federacao, $status, $_SESSION['usuario_id']]);
            registrar_log($pdo, $_SESSION['usuario_id'], 'Atleta cadastrado', 'atletas', $pdo->lastInsertId());
            $mensagem = "Atleta cadastrado com sucesso!";
        }
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Buscar atletas
$busca = $_GET['busca'] ?? '';
$sql = "SELECT * FROM atletas WHERE nome LIKE ? OR cpf LIKE ? OR clube LIKE ? ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$busca%", "%$busca%", "%$busca%"]);
$atletas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atletas - Sistema CBF Antidoping</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container">
        <h1>Gestão de Atletas</h1>
        <p class="subtitle">Cadastro e controle de atletas</p>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Cadastrar Novo Atleta</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome Completo:</label>
                        <input type="text" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>CPF:</label>
                        <input type="text" name="cpf" placeholder="000.000.000-00" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Data de Nascimento:</label>
                        <input type="date" name="data_nascimento" required>
                    </div>
                    <div class="form-group">
                        <label>Clube:</label>
                        <input type="text" name="clube" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Posição:</label>
                        <select name="posicao" required>
                            <option value="">Selecione...</option>
                            <option value="Goleiro">Goleiro</option>
                            <option value="Zagueiro">Zagueiro</option>
                            <option value="Lateral">Lateral</option>
                            <option value="Volante">Volante</option>
                            <option value="Meio-campo">Meio-campo</option>
                            <option value="Atacante">Atacante</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Federação:</label>
                        <input type="text" name="federacao" placeholder="Ex: SP, RJ, MG" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="suspenso">Suspenso</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">Cadastrar Atleta</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Lista de Atletas</h2>
            
            <form method="GET" action="" style="margin-bottom: 20px;">
                <div class="form-group">
                    <input type="text" name="busca" placeholder="Buscar por nome, CPF ou clube..." value="<?= htmlspecialchars($busca) ?>">
                </div>
                <button type="submit" class="btn">Buscar</button>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Clube</th>
                        <th>Posição</th>
                        <th>Federação</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($atletas)): ?>
                        <tr><td colspan="7" class="text-center">Nenhum atleta encontrado</td></tr>
                    <?php else: ?>
                        <?php foreach ($atletas as $atleta): ?>
                            <tr>
                                <td><?= htmlspecialchars($atleta['nome']) ?></td>
                                <td><?= formatar_cpf($atleta['cpf']) ?></td>
                                <td><?= htmlspecialchars($atleta['clube']) ?></td>
                                <td><?= htmlspecialchars($atleta['posicao']) ?></td>
                                <td><?= htmlspecialchars($atleta['federacao']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $atleta['status'] ?>">
                                        <?= ucfirst($atleta['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="atleta_detalhes.php?id=<?= $atleta['id'] ?>" class="btn btn-small">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>