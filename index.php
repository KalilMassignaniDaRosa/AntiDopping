<?php
require_once 'config.php';
verificar_login();

// Estatísticas
$sql_atletas = "SELECT COUNT(*) as total FROM atletas WHERE status = 'ativo'";
$total_atletas = $pdo->query($sql_atletas)->fetch()['total'];

$sql_testes = "SELECT COUNT(*) as total FROM testes_antidoping WHERE MONTH(data_coleta) = MONTH(CURRENT_DATE)";
$testes_mes = $pdo->query($sql_testes)->fetch()['total'];

$sql_pendentes = "SELECT COUNT(*) as total FROM testes_antidoping WHERE resultado = 'pendente'";
$testes_pendentes = $pdo->query($sql_pendentes)->fetch()['total'];

$sql_positivos = "SELECT COUNT(*) as total FROM testes_antidoping WHERE resultado = 'positivo'";
$testes_positivos = $pdo->query($sql_positivos)->fetch()['total'];

// Últimos testes
$sql_ultimos = "SELECT t.*, a.nome as atleta_nome, a.clube 
                FROM testes_antidoping t 
                JOIN atletas a ON t.atleta_id = a.id 
                ORDER BY t.data_registro DESC LIMIT 5";
$ultimos_testes = $pdo->query($sql_ultimos)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema CBF Antidoping</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container">
        <h1>Dashboard</h1>
        <p class="subtitle">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</p>
        
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?= $total_atletas ?></h3>
                    <p>Atletas Ativos</p>
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon">🧪</div>
                <div class="stat-info">
                    <h3><?= $testes_mes ?></h3>
                    <p>Testes Este Mês</p>
                </div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?= $testes_pendentes ?></h3>
                    <p>Testes Pendentes</p>
                </div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <h3><?= $testes_positivos ?></h3>
                    <p>Testes Positivos</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Últimos Testes Realizados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data Coleta</th>
                        <th>Atleta</th>
                        <th>Clube</th>
                        <th>Tipo</th>
                        <th>Resultado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimos_testes)): ?>
                        <tr><td colspan="6" class="text-center">Nenhum teste registrado</td></tr>
                    <?php else: ?>
                        <?php foreach ($ultimos_testes as $teste): ?>
                            <tr>
                                <td><?= formatar_data($teste['data_coleta']) ?></td>
                                <td><?= htmlspecialchars($teste['atleta_nome']) ?></td>
                                <td><?= htmlspecialchars($teste['clube']) ?></td>
                                <td><?= ucfirst($teste['tipo_teste']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $teste['resultado'] ?>">
                                        <?= ucfirst($teste['resultado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="teste_detalhes.php?id=<?= $teste['id'] ?>" class="btn-small">Ver</a>
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