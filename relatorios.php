<?php
require_once 'config.php';
verificar_login();

// Estatísticas gerais
$stats = [];

// Total de atletas por status
$sql = "SELECT status, COUNT(*) as total FROM atletas GROUP BY status";
$stats['atletas_status'] = $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);

// Total de testes por resultado
$sql = "SELECT resultado, COUNT(*) as total FROM testes_antidoping GROUP BY resultado";
$stats['testes_resultado'] = $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);

// Testes por mês (últimos 6 meses)
$sql = "SELECT DATE_FORMAT(data_coleta, '%Y-%m') as mes, COUNT(*) as total 
        FROM testes_antidoping 
        WHERE data_coleta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY mes 
        ORDER BY mes DESC";
$stats['testes_mes'] = $pdo->query($sql)->fetchAll();

// Clubes com mais testes
$sql = "SELECT a.clube, COUNT(t.id) as total 
        FROM testes_antidoping t 
        JOIN atletas a ON t.atleta_id = a.id 
        GROUP BY a.clube 
        ORDER BY total DESC 
        LIMIT 10";
$stats['clubes_testes'] = $pdo->query($sql)->fetchAll();

// Testes positivos por substância
$sql = "SELECT substancia_detectada, COUNT(*) as total 
        FROM testes_antidoping 
        WHERE resultado = 'positivo' AND substancia_detectada IS NOT NULL 
        GROUP BY substancia_detectada 
        ORDER BY total DESC";
$stats['substancias'] = $pdo->query($sql)->fetchAll();

// Relatório de testes pendentes
$sql = "SELECT t.*, a.nome as atleta_nome, a.clube, a.cpf 
        FROM testes_antidoping t 
        JOIN atletas a ON t.atleta_id = a.id 
        WHERE t.resultado = 'pendente' 
        ORDER BY t.data_coleta ASC";
$testes_pendentes = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema CBF Antidoping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-section { margin-bottom: 30px; }
        .chart-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px;
        }
        .mini-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .mini-card h3 {
            color: #1e3c72;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-value {
            font-weight: bold;
            color: #1e3c72;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container">
        <h1>Relatórios e Estatísticas</h1>
        <p class="subtitle">Análise de dados do sistema antidoping</p>
        
        <div class="chart-container">
            <div class="mini-card">
                <h3>📊 Atletas por Status</h3>
                <?php foreach ($stats['atletas_status'] as $status => $total): ?>
                    <div class="stat-item">
                        <span><?= ucfirst($status) ?></span>
                        <span class="stat-value"><?= $total ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mini-card">
                <h3>🧪 Testes por Resultado</h3>
                <?php foreach ($stats['testes_resultado'] as $resultado => $total): ?>
                    <div class="stat-item">
                        <span><?= ucfirst($resultado) ?></span>
                        <span class="stat-value"><?= $total ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>📈 Testes Realizados (Últimos 6 Meses)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mês/Ano</th>
                        <th>Total de Testes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['testes_mes'])): ?>
                        <tr><td colspan="2" class="text-center">Nenhum teste registrado</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['testes_mes'] as $item): ?>
                            <tr>
                                <td><?= date('m/Y', strtotime($item['mes'] . '-01')) ?></td>
                                <td><strong><?= $item['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>⚽ Top 10 Clubes com Mais Testes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Clube</th>
                        <th>Total de Testes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['clubes_testes'])): ?>
                        <tr><td colspan="2" class="text-center">Nenhum dado disponível</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['clubes_testes'] as $clube): ?>
                            <tr>
                                <td><?= htmlspecialchars($clube['clube']) ?></td>
                                <td><strong><?= $clube['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($stats['substancias'])): ?>
            <div class="card">
                <h2>⚠️ Substâncias Detectadas (Testes Positivos)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Substância</th>
                            <th>Ocorrências</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['substancias'] as $sub): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['substancia_detectada']) ?></td>
                                <td><strong><?= $sub['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>⏳ Testes Pendentes de Resultado</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data Coleta</th>
                        <th>Atleta</th>
                        <th>CPF</th>
                        <th>Clube</th>
                        <th>Tipo</th>
                        <th>Laboratório</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($testes_pendentes)): ?>
                        <tr><td colspan="6" class="text-center">Nenhum teste pendente</td></tr>
                    <?php else: ?>
                        <?php foreach ($testes_pendentes as $teste): ?>
                            <tr>
                                <td><?= formatar_data($teste['data_coleta']) ?></td>
                                <td><?= htmlspecialchars($teste['atleta_nome']) ?></td>
                                <td><?= formatar_cpf($teste['cpf']) ?></td>
                                <td><?= htmlspecialchars($teste['clube']) ?></td>
                                <td><?= ucfirst($teste['tipo_teste']) ?></td>
                                <td><?= htmlspecialchars($teste['laboratorio']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="btn-group">
            <button onclick="window.print()" class="btn">🖨️ Imprimir Relatório</button>
        </div>
    </div>
</body>
</html>