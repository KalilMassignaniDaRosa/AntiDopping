<?php
require_once 'config.php';
verificar_login();

$mensagem = '';
$tipo_mensagem = '';

// Adicionar teste
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atleta_id = $_POST['atleta_id'];
    $data_coleta = $_POST['data_coleta'];
    $tipo_teste = $_POST['tipo_teste'];
    $laboratorio = limpar_input($_POST['laboratorio']);
    $resultado = $_POST['resultado'];
    $substancia = limpar_input($_POST['substancia'] ?? '');
    $observacoes = limpar_input($_POST['observacoes'] ?? '');
    $data_resultado = $_POST['data_resultado'] ?? null;
    $responsavel = limpar_input($_POST['responsavel']);
    
    try {
        $sql = "INSERT INTO testes_antidoping (atleta_id, data_coleta, tipo_teste, laboratorio, resultado, 
                substancia_detectada, observacoes, data_resultado, responsavel) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$atleta_id, $data_coleta, $tipo_teste, $laboratorio, $resultado, 
                       $substancia, $observacoes, $data_resultado, $responsavel]);
        
        registrar_log($pdo, $_SESSION['usuario_id'], 'Teste antidoping registrado', 'testes_antidoping', $pdo->lastInsertId());
        $mensagem = "Teste registrado com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Buscar testes
$filtro_resultado = $_GET['resultado'] ?? '';
$sql = "SELECT t.*, a.nome as atleta_nome, a.clube, a.cpf 
        FROM testes_antidoping t 
        JOIN atletas a ON t.atleta_id = a.id ";

if ($filtro_resultado) {
    $sql .= "WHERE t.resultado = ? ";
    $stmt = $pdo->prepare($sql . "ORDER BY t.data_coleta DESC");
    $stmt->execute([$filtro_resultado]);
} else {
    $sql .= "ORDER BY t.data_coleta DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$testes = $stmt->fetchAll();

// Buscar atletas para o select
$atletas_select = $pdo->query("SELECT id, nome, clube FROM atletas WHERE status = 'ativo' ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testes Antidoping - Sistema CBF</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container">
        <h1>Gestão de Testes Antidoping</h1>
        <p class="subtitle">Registro e acompanhamento de testes</p>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Registrar Novo Teste</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Atleta:</label>
                        <select name="atleta_id" required>
                            <option value="">Selecione um atleta...</option>
                            <?php foreach ($atletas_select as $a): ?>
                                <option value="<?= $a['id'] ?>">
                                    <?= htmlspecialchars($a['nome']) ?> - <?= htmlspecialchars($a['clube']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Data da Coleta:</label>
                        <input type="date" name="data_coleta" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo de Teste:</label>
                        <select name="tipo_teste" required>
                            <option value="urina">Urina</option>
                            <option value="sangue">Sangue</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Laboratório:</label>
                        <input type="text" name="laboratorio" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Resultado:</label>
                        <select name="resultado" id="resultado" required>
                            <option value="pendente">Pendente</option>
                            <option value="negativo">Negativo</option>
                            <option value="positivo">Positivo</option>
                            <option value="invalido">Inválido</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Data do Resultado:</label>
                        <input type="date" name="data_resultado">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Substância Detectada (se positivo):</label>
                    <input type="text" name="substancia">
                </div>
                
                <div class="form-group">
                    <label>Responsável:</label>
                    <input type="text" name="responsavel" required>
                </div>
                
                <div class="form-group">
                    <label>Observações:</label>
                    <textarea name="observacoes"></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">Registrar Teste</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Lista de Testes</h2>
            
            <form method="GET" action="" style="margin-bottom: 20px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Filtrar por Resultado:</label>
                        <select name="resultado">
                            <option value="">Todos</option>
                            <option value="pendente" <?= $filtro_resultado === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="negativo" <?= $filtro_resultado === 'negativo' ? 'selected' : '' ?>>Negativo</option>
                            <option value="positivo" <?= $filtro_resultado === 'positivo' ? 'selected' : '' ?>>Positivo</option>
                            <option value="invalido" <?= $filtro_resultado === 'invalido' ? 'selected' : '' ?>>Inválido</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">Filtrar</button>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>Data Coleta</th>
                        <th>Atleta</th>
                        <th>CPF</th>
                        <th>Clube</th>
                        <th>Tipo</th>
                        <th>Laboratório</th>
                        <th>Resultado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($testes)): ?>
                        <tr><td colspan="8" class="text-center">Nenhum teste encontrado</td></tr>
                    <?php else: ?>
                        <?php foreach ($testes as $teste): ?>
                            <tr>
                                <td><?= formatar_data($teste['data_coleta']) ?></td>
                                <td><?= htmlspecialchars($teste['atleta_nome']) ?></td>
                                <td><?= formatar_cpf($teste['cpf']) ?></td>
                                <td><?= htmlspecialchars($teste['clube']) ?></td>
                                <td><?= ucfirst($teste['tipo_teste']) ?></td>
                                <td><?= htmlspecialchars($teste['laboratorio']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $teste['resultado'] ?>">
                                        <?= ucfirst($teste['resultado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="teste_detalhes.php?id=<?= $teste['id'] ?>" class="btn btn-small">Ver</a>
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