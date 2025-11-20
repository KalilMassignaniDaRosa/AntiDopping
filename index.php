<?php
// Inicia sessão se necessário
session_start();

$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$api_url = rtrim($base_url, '/') . '/api/';

// Configurações
date_default_timezone_set('America/Sao_Paulo');

// Se for uma requisição API, redireciona
if (isset($_GET['api']) || strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    require_once __DIR__ . '/api/index.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CBF - Sistema Antidoping</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .nav {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav button {
            padding: 12px 20px;
            border: none;
            background: #667eea;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95em;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .nav button.active {
            background: #1e3c72;
        }

        .content {
            padding: 30px;
            min-height: 500px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.4s;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            font-size: 0.9em;
            border-radius: 8px;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            font-size: 0.9em;
            border-radius: 8px;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
            padding: 8px 16px;
            font-size: 0.9em;
            border-radius: 8px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 8px 16px;
            font-size: 0.9em;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-info:hover {
            background: #138496;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }

        .loading::after {
            content: "⏳";
            font-size: 3em;
            display: block;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            display: block;
        }

        h2 {
            color: #1e3c72;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>⚽ CBF - Sistema Antidoping</h1>
            <p>Gestão Integrada de Atletas e Testes Antidoping</p>
        </div>

        <div class="nav">
            <button onclick="showSection('dashboard', this)" class="active">📊 Dashboard</button>
            <button onclick="showSection('atletas', this)">👥 Atletas</button>
            <button onclick="showSection('cadastro-atleta', this)" id="btn-novo-atleta">➕ Novo Atleta</button>
            <button onclick="showSection('testes', this)">🔬 Testes</button>
            <button onclick="showSection('novo-teste', this)">➕ Novo Teste</button>
            <button onclick="showSection('relatorios', this)">📈 Relatórios</button>
        </div>

        <div class="content">
            <div id="alert-container"></div>

            <!-- Dashboard -->
            <div id="dashboard" class="section active">
                <h2>Dashboard - Visão Geral do Sistema</h2>
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="total-atletas-ativos">-</h3>
                        <p>Atletas Ativos</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-testes">-</h3>
                        <p>Testes Realizados</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="testes-pendentes">-</h3>
                        <p>Testes Pendentes</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="testes-positivos">-</h3>
                        <p>Testes Positivos</p>
                    </div>
                </div>
            </div>

            <!-- Lista de Atletas -->
            <div id="atletas" class="section">
                <h2>Atletas Cadastrados</h2>
                <div class="search-box">
                    <input type="text" id="search-atleta" placeholder="Buscar por nome, CPF ou clube..." onkeyup="filtrarAtletas()">
                </div>
                <div class="filters">
                    <label><strong>Filtrar por Status:</strong></label>
                    <select id="filter-status" onchange="carregarAtletas()" style="padding: 8px; border-radius: 6px; border: 2px solid #e0e0e0; margin-left: 10px;">
                        <option value="">Todos</option>
                        <option value="ativo">Ativos</option>
                        <option value="inativo">Inativos</option>
                        <option value="suspenso">Suspensos</option>
                    </select>
                </div>
                <div id="atletas-loading" class="loading" style="display:none;">Carregando atletas...</div>
                <div class="table-container">
                    <table id="tabela-atletas">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Idade</th>
                                <th>Clube</th>
                                <th>Posição</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dados carregados via API -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cadastro de Atleta -->
            <div id="cadastro-atleta" class="section">
                <h2 id="titulo-cadastro-atleta">Cadastrar Novo Atleta</h2>
                <form id="form-atleta" onsubmit="salvarAtleta(event)">
                    <input type="hidden" name="id" id="atleta-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="cpf" placeholder="000.000.000-00" required maxlength="14" oninput="formatarCPF(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" required>
                        </div>
                        <div class="form-group">
                            <label>Clube *</label>
                            <select name="clube_id" id="select-clube" required>
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Posição *</label>
                            <select name="posicao" required>
                                <option value="">Selecione</option>
                                <option value="Goleiro">Goleiro</option>
                                <option value="Zagueiro">Zagueiro</option>
                                <option value="Lateral">Lateral</option>
                                <option value="Volante">Volante</option>
                                <option value="Meio-Campo">Meio-Campo</option>
                                <option value="Atacante">Atacante</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="suspenso">Suspenso</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="observacoes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btn-salvar-atleta">✓ Cadastrar Atleta</button>
                </form>
            </div>

            <!-- Lista de Testes -->
            <div id="testes" class="section">
                <h2>Testes Antidoping Realizados</h2>
                <div class="filters">
                    <label><strong>Filtrar:</strong></label>
                    <select id="filter-resultado" onchange="carregarTestes()" style="padding: 8px; border-radius: 6px; border: 2px solid #e0e0e0; margin-left: 10px;">
                        <option value="">Todos os Resultados</option>
                        <option value="pendente">Pendentes</option>
                        <option value="negativo">Negativos</option>
                        <option value="positivo">Positivos</option>
                        <option value="inconclusivo">Inconclusivos</option>
                    </select>
                </div>
                <div id="testes-loading" class="loading" style="display:none;">Carregando testes...</div>
                <div class="table-container">
                    <table id="tabela-testes">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Atleta</th>
                                <th>Clube</th>
                                <th>Laboratório</th>
                                <th>Tipo</th>
                                <th>Resultado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dados carregados via API -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Novo Teste -->
            <div id="novo-teste" class="section">
                <h2>Registrar Novo Teste Antidoping</h2>
                <form id="form-teste" onsubmit="salvarTeste(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Atleta *</label>
                            <select name="atleta_id" id="select-atleta-teste" required>
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Laboratório *</label>
                            <select name="laboratorio_id" id="select-laboratorio" required>
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Data da Coleta *</label>
                            <input type="date" name="data_coleta" required>
                        </div>
                        <div class="form-group">
                            <label>Hora da Coleta *</label>
                            <input type="time" name="hora_coleta" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipo de Teste *</label>
                            <select name="tipo_teste" required>
                                <option value="">Selecione</option>
                                <option value="urina">Urina</option>
                                <option value="sangue">Sangue</option>
                                <option value="ambos">Ambos (Urina e Sangue)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Resultado</label>
                            <select name="resultado">
                                <option value="pendente">Pendente</option>
                                <option value="negativo">Negativo</option>
                                <option value="positivo">Positivo</option>
                                <option value="inconclusivo">Inconclusivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Substância Detectada (se positivo)</label>
                            <input type="text" name="substancia_detectada">
                        </div>
                        <div class="form-group">
                            <label>Nível da Substância</label>
                            <input type="text" name="nivel_substancia" placeholder="Ex: 10 ng/mL">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="observacoes" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">✓ Registrar Teste</button>
                </form>
            </div>

            <!-- Relatórios -->
            <div id="relatorios" class="section">
                <h2>Relatórios e Estatísticas</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Data Início *</label>
                        <input type="date" id="data-inicio-relatorio">
                    </div>
                    <div class="form-group">
                        <label>Data Fim *</label>
                        <input type="date" id="data-fim-relatorio">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="gerarRelatorio()">📊 Gerar Relatório</button>
                <div id="resultado-relatorio"></div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '<?php echo $api_url; ?>';

        function showAlert(message, type = 'success') {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `<strong>${type === 'success' ? '✅' : '⚠️'}</strong> ${message}`;
            container.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        async function fetchJson(url, options = {}) {
            try {
                // Se a URL já é completa, usa diretamente
                if (url.startsWith('http')) {
                    // URL completa - usa como está
                } else if (url.startsWith('/')) {
                    // URL absoluta - adiciona apenas a origem
                    url = window.location.origin + url;
                } else {
                    // URL relativa - adiciona base API
                    url = API_URL + url;
                }

                console.log('Fetch URL:', url);

                const defaultOptions = {
                    headers: {}
                };

                // Se não é FormData e não tem Content-Type definido, usa JSON
                if (!(options.body instanceof FormData) && !options.headers?.['Content-Type']) {
                    defaultOptions.headers['Content-Type'] = 'application/json';
                }

                defaultOptions.headers['Accept'] = 'application/json';

                const finalOptions = {
                    ...defaultOptions,
                    ...options
                };

                // Se o body é objeto e não FormData, converte para JSON
                if (finalOptions.body && typeof finalOptions.body === 'object' && !(finalOptions.body instanceof FormData)) {
                    finalOptions.body = JSON.stringify(finalOptions.body);
                }

                const res = await fetch(url, finalOptions);

                if (!res.ok) {
                    const errorText = await res.text();
                    throw new Error(`HTTP ${res.status} - ${res.statusText}. Response: ${errorText.substring(0, 200)}`);
                }

                const text = await res.text();

                if (!text) {
                    return {
                        success: true,
                        data: null
                    };
                }

                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('Resposta não-JSON:', text.substring(0, 500));
                    throw new Error('Resposta do servidor não é JSON válido: ' + text.substring(0, 100));
                }

            } catch (err) {
                console.error('Erro fetch:', err.message, 'URL:', url);
                throw err;
            }
        }

        function getBaseUrl() {
            const path = window.location.pathname;
            const projectPath = path.substring(0, path.lastIndexOf('/'));
            return window.location.origin + projectPath + '/api/';
        }


        function showSection(sectionId, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav button').forEach(b => b.classList.remove('active'));

            document.getElementById(sectionId).classList.add('active');
            btn.classList.add('active');

            if (sectionId === 'atletas') carregarAtletas();
            if (sectionId === 'testes') carregarTestes();
            if (sectionId === 'novo-teste') {
                carregarAtletasSelect();
                carregarLaboratorios();
            }
            if (sectionId === 'cadastro-atleta') carregarClubes();
            if (sectionId === 'dashboard') carregarDashboard();
            if (sectionId === 'cadastro-atleta') resetFormAtleta();
        }

        async function carregarDashboard() {
            try {
                const result = await fetchJson(`${API_URL}testes/dashboard`);
                if (result && result.success) {
                    const stats = result.data || {};

                    document.getElementById('total-atletas-ativos').textContent = stats.total_atletas_ativos || 0;
                    document.getElementById('total-testes').textContent = stats.total_testes || 0;
                    document.getElementById('testes-pendentes').textContent = stats.testes_pendentes || 0;
                    document.getElementById('testes-positivos').textContent = stats.testes_positivos || 0;

                } else {
                    showAlert('Erro ao carregar dados do dashboard', 'danger');
                }
            } catch (error) {
                showAlert('Erro ao carregar dashboard: ' + error.message, 'danger');

                document.getElementById('total-atletas-ativos').textContent = '0';
                document.getElementById('total-testes').textContent = '0';
                document.getElementById('testes-pendentes').textContent = '0';
                document.getElementById('testes-positivos').textContent = '0';
            }
        }

        async function carregarAtletas() {
            const loading = document.getElementById('atletas-loading');
            const tbody = document.querySelector('#tabela-atletas tbody');

            loading.style.display = 'block';
            tbody.innerHTML = '';

            try {
                const status = document.getElementById('filter-status').value;
                const url = status ? `${API_URL}atletas/status/${status}` : `${API_URL}atletas`;

                const result = await fetchJson(url);

                if (result && result.success && Array.isArray(result.data)) {
                    if (result.data.length > 0) {
                        result.data.forEach(atleta => {
                            const statusClass = atleta.status === 'ativo' ? 'badge-success' :
                                atleta.status === 'suspenso' ? 'badge-danger' : 'badge-warning';

                            // Dentro do loop forEach na função carregarAtletas, substitua a parte dos botões:
                            tbody.innerHTML += `
                                <tr>
                                    <td><strong>${escapeHtml(atleta.nome)}</strong></td>
                                    <td>${escapeHtml(atleta.cpf || '-')}</td>
                                    <td>${atleta.idade ? atleta.idade + ' anos' : '-'}</td>
                                    <td>${escapeHtml(atleta.clube_nome || '-')}</td>
                                    <td>${escapeHtml(atleta.posicao || '-')}</td>
                                    <td><span class="badge ${atleta.status === 'ativo' ? 'badge-success' : atleta.status === 'suspenso' ? 'badge-warning' : 'badge-danger'}">${atleta.status.toUpperCase()}</span></td>
                                    <td class="actions">
                                        <button class="btn-success" onclick="verHistoricoTestes(${atleta.id}, '${escapeJs(atleta.nome)}')">📋 Histórico</button>
                                        <button class="btn-warning" onclick="editarAtleta(${atleta.id})">✏️ Editar</button>
                                        <button class="btn-info" onclick="mostrarModalStatus(${atleta.id}, '${escapeJs(atleta.nome)}', '${atleta.status}')">
                                            🔄 Status
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Nenhum atleta encontrado</td></tr>';
                    }
                }
            } catch (error) {
                showAlert('Erro ao carregar atletas', 'danger');
            } finally {
                loading.style.display = 'none';
            }
        }

        async function carregarTestes() {
            const loading = document.getElementById('testes-loading');
            const tbody = document.querySelector('#tabela-testes tbody');

            loading.style.display = 'block';
            tbody.innerHTML = '';

            try {
                const result = await fetchJson(`${API_URL}testes`);
                if (result && result.success && Array.isArray(result.data)) {
                    let testes = result.data;
                    const filtro = document.getElementById('filter-resultado').value;

                    if (filtro) {
                        testes = testes.filter(t => t.resultado === filtro);
                    }

                    if (testes.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Nenhum teste encontrado</td></tr>';
                        return;
                    }

                    testes.forEach(teste => {
                        const resultadoClass = teste.resultado === 'negativo' ? 'badge-success' :
                            teste.resultado === 'positivo' ? 'badge-danger' :
                            teste.resultado === 'inconclusivo' ? 'badge-info' : 'badge-warning';

                        const dataHora = teste.data_coleta ? `${new Date(teste.data_coleta).toLocaleDateString('pt-BR')} ${teste.hora_coleta || ''}` : '-';

                        tbody.innerHTML += `
                            <tr>
                                <td>${dataHora}</td>
                                <td><strong>${escapeHtml(teste.atleta_nome || '-')}</strong></td>
                                <td>${escapeHtml(teste.clube_nome || '-')}</td>
                                <td>${escapeHtml(teste.laboratorio_nome || '-')}</td>
                                <td>${escapeHtml(teste.tipo_teste || '-')}</td>
                                <td><span class="badge ${resultadoClass}">${teste.resultado.toUpperCase()}</span></td>
                                <td class="actions">
                                    ${teste.resultado === 'pendente' ?
                                        `<button class="btn-warning" onclick="atualizarResultado(${teste.id})">✏️ Atualizar</button>` :
                                        `<button class="btn-success" onclick="verDetalhes(${teste.id})">👁️ Ver</button>`
                                    }
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Nenhum teste encontrado</td></tr>';
                }
            } catch (error) {
                showAlert('Erro ao carregar testes', 'danger');
            } finally {
                loading.style.display = 'none';
            }
        }

        async function carregarClubes() {
            try {
                const result = await fetchJson(`clubes`);
                const select = document.getElementById('select-clube');

                if (!select) {
                    console.error('❌ Elemento select-clube não encontrado');
                    return;
                }

                select.innerHTML = '<option value="">Selecione um clube</option>';

                if (result && result.success && Array.isArray(result.data)) {
                    result.data.forEach(clube => {
                        const option = document.createElement('option');
                        option.value = clube.id;
                        option.textContent = `${clube.nome} - ${clube.cidade || ''}`;
                        select.appendChild(option);
                    });
                    console.log('✅ Clubes carregados:', result.data.length);
                } else {
                    console.error('❌ Erro ao carregar clubes:', result);
                }
            } catch (error) {
                console.error('❌ Erro ao carregar clubes:', error);
            }
        }

        async function carregarAtletasSelect() {
            try {
                const result = await fetchJson(`${API_URL}atletas/status/ativo`);
                const select = document.getElementById('select-atleta-teste');
                select.innerHTML = '<option value="">Selecione um atleta</option>';

                if (result && result.success && Array.isArray(result.data)) {
                    result.data.forEach(atleta => {
                        const option = document.createElement('option');
                        option.value = atleta.id;
                        option.textContent = `${atleta.nome} - ${atleta.clube_nome || ''}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Erro atletas select:', error);
            }
        }

        async function carregarLaboratorios() {
            try {
                const result = await fetchJson(`${API_URL}laboratorios`);
                const select = document.getElementById('select-laboratorio');
                select.innerHTML = '<option value="">Selecione um laboratório</option>';

                if (result && result.success && Array.isArray(result.data)) {
                    result.data.forEach(lab => {
                        const wada = lab.credenciado_wada ? '✓ WADA' : '';
                        const option = document.createElement('option');
                        option.value = lab.id;
                        option.textContent = `${lab.nome} - ${lab.cidade || ''} ${wada}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Erro laboratórios:', error);
            }
        }

        async function salvarAtleta(e) {
            e.preventDefault();

            const id = document.getElementById('atleta-id').value;
            const isEdit = !!id;

            try {
                // Coletar dados do formulário de forma direta
                const formData = {
                    nome: document.querySelector('[name="nome"]').value,
                    cpf: document.querySelector('[name="cpf"]').value.replace(/\D/g, ''),
                    data_nascimento: document.querySelector('[name="data_nascimento"]').value,
                    clube_id: parseInt(document.querySelector('[name="clube_id"]').value),
                    posicao: document.querySelector('[name="posicao"]').value,
                    status: document.querySelector('[name="status"]').value,
                    observacoes: document.querySelector('[name="observacoes"]').value
                };

                console.log('🚀 ENVIANDO ATUALIZAÇÃO:', {
                    id,
                    isEdit,
                    formData
                });

                const url = isEdit ? `atletas/${id}` : 'atletas';
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(API_URL + url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const responseText = await response.text();
                console.log('📨 RESPOSTA DO SERVIDOR:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    throw new Error(`Resposta não é JSON: ${responseText.substring(0, 100)}`);
                }

                if (result.success) {
                    showAlert(`Atleta ${isEdit ? 'atualizado' : 'cadastrado'} com sucesso!`, 'success');
                    resetFormAtleta();
                    carregarAtletas();
                    // Forçar recarregamento da seção atletas
                    showSection('atletas', document.querySelector('.nav button[onclick*="atletas"]'));
                } else {
                    throw new Error(result.message || 'Erro desconhecido');
                }

            } catch (error) {
                console.error('💥 ERRO NA ATUALIZAÇÃO:', error);
                showAlert(`❌ Erro: ${error.message}`, 'danger');
            }
        }

        function resetFormAtleta() {
            document.getElementById('form-atleta').reset();
            document.getElementById('atleta-id').value = '';
            document.getElementById('titulo-cadastro-atleta').textContent = 'Cadastrar Novo Atleta';
            document.getElementById('btn-salvar-atleta').textContent = '✅ Cadastrar Atleta';
            console.log('🔄 Formulário resetado para modo de criação');
        }

        async function editarAtleta(id) {
            try {
                console.log('🔍 Carregando dados do atleta ID:', id);

                const result = await fetchJson(`atletas/${id}`);

                if (result.success && result.data) {
                    const atleta = result.data;
                    console.log('📋 Dados do atleta carregados:', atleta);

                    // Mudar para a seção de cadastro primeiro
                    showSection('cadastro-atleta', document.getElementById('btn-novo-atleta'));

                    // Pequeno delay para garantir que o DOM está pronto
                    await new Promise(resolve => setTimeout(resolve, 100));

                    // Formatar CPF
                    let cpfFormatado = atleta.cpf;
                    if (cpfFormatado && cpfFormatado.length === 11) {
                        cpfFormatado = cpfFormatado.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    }

                    // Carregar clubes
                    await carregarClubes();

                    // Preenchimento FORÇADO - tentar todas as formas possíveis
                    const setValue = (selector, value) => {
                        const element = document.querySelector(selector);
                        if (element) {
                            element.value = value;
                            console.log(`✅ Set ${selector} = ${value}`);
                        } else {
                            console.error(`❌ Elemento não encontrado: ${selector}`);
                        }
                    };

                    setValue('#form-atleta [name="nome"]', atleta.nome || '');
                    setValue('#form-atleta [name="cpf"]', cpfFormatado || '');
                    setValue('#form-atleta [name="data_nascimento"]', atleta.data_nascimento || '');
                    setValue('#form-atleta [name="clube_id"]', atleta.clube_id || '');
                    setValue('#form-atleta [name="posicao"]', atleta.posicao || '');
                    setValue('#form-atleta [name="status"]', atleta.status || 'ativo');
                    setValue('#form-atleta [name="observacoes"]', atleta.observacoes || '');

                    // Configurar modo de edição
                    document.getElementById('atleta-id').value = atleta.id;
                    document.getElementById('titulo-cadastro-atleta').textContent = 'Editar Atleta';
                    document.getElementById('btn-salvar-atleta').textContent = '💾 Salvar Alterações';

                    console.log('✅ Formulário preenchido para edição');

                } else {
                    throw new Error(result.message || 'Dados do atleta não encontrados');
                }
            } catch (error) {
                console.error('❌ Erro ao carregar dados do atleta:', error);
                showAlert('❌ Erro ao carregar dados do atleta: ' + error.message, 'danger');
            }
        }

        // Sistema completo de gerenciamento de status do atleta
        async function gerenciarStatusAtleta(id, acao, atletaNome) {
            const acoes = {
                'ativar': {
                    status: 'ativo',
                    mensagem: 'ativar',
                    classe: 'btn-success'
                },
                'desativar': {
                    status: 'inativo',
                    mensagem: 'desativar',
                    classe: 'btn-danger'
                },
                'suspender': {
                    status: 'suspenso',
                    mensagem: 'suspender',
                    classe: 'btn-warning'
                }
            };

            const config = acoes[acao];

            if (!confirm(`Tem certeza que deseja ${config.mensagem} o atleta "${atletaNome}"?`)) return;

            try {
                const result = await fetchJson(`atletas/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: config.status
                    })
                });

                if (result.success) {
                    showAlert(`Atleta ${config.mensagem} com sucesso!`, 'success');
                    carregarAtletas();
                } else {
                    showAlert('Erro ao alterar status do atleta: ' + result.message, 'danger');
                }
            } catch (error) {
                showAlert('Erro ao processar requisição: ' + error.message, 'danger');
            }
        }

        // Modal para gerenciar status
        function mostrarModalStatus(atletaId, atletaNome, statusAtual) {
            const modal = `
                <div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; display:flex; align-items:center; justify-content:center;" onclick="this.remove()">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%;" onclick="event.stopPropagation()">
                        <h2>Gerenciar Status - ${escapeHtml(atletaNome)}</h2>
                        <p style="margin-bottom: 20px; color: #666;">Status atual:
                            <span class="badge ${statusAtual === 'ativo' ? 'badge-success' : statusAtual === 'suspenso' ? 'badge-warning' : 'badge-danger'}">
                                ${statusAtual.toUpperCase()}
                            </span>
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            ${statusAtual !== 'ativo' ?
                                `<button class="btn btn-success" onclick="gerenciarStatusAtleta(${atletaId}, 'ativar', '${escapeJs(atletaNome)}'); this.closest('.modal-overlay').remove();">
                                    ✅ Ativar Atleta
                                </button>` : ''
                            }

                            ${statusAtual !== 'inativo' ?
                                `<button class="btn btn-danger" onclick="gerenciarStatusAtleta(${atletaId}, 'desativar', '${escapeJs(atletaNome)}'); this.closest('.modal-overlay').remove();">
                                    🚫 Desativar Atleta
                                </button>` : ''
                            }

                            ${statusAtual !== 'suspenso' ?
                                `<button class="btn btn-warning" onclick="gerenciarStatusAtleta(${atletaId}, 'suspender', '${escapeJs(atletaNome)}'); this.closest('.modal-overlay').remove();">
                                    ⚠️ Suspender Atleta
                                </button>` : ''
                            }
                        </div>

                        <div style="text-align:right; margin-top:20px;">
                            <button class="btn btn-primary" onclick="this.closest('.modal-overlay').remove()">Fechar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
        }

        async function salvarTeste(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const result = await fetchJson(`${API_URL}testes`, {
                    method: 'POST',
                    body: formData
                });

                if (result && result.success) {
                    showAlert('Teste registrado com sucesso!', 'success');
                    e.target.reset();
                    showSection('testes', document.querySelector('.nav button[onclick*="testes"]'));
                } else {
                    showAlert(result.message || 'Erro ao registrar teste', 'danger');
                }
            } catch (error) {
                showAlert('Erro ao processar requisição', 'danger');
            }
        }

        async function gerarRelatorio() {
            const dataInicio = document.getElementById('data-inicio-relatorio').value;
            const dataFim = document.getElementById('data-fim-relatorio').value;

            if (!dataInicio || !dataFim) {
                showAlert('Selecione as datas de início e fim', 'danger');
                return;
            }

            try {
                const result = await fetchJson(`${API_URL}testes/relatorio?data_inicio=${dataInicio}&data_fim=${dataFim}`);

                if (result && result.success) {
                    const relatorio = result.data || [];

                    let html = `
                        <div class="stats" style="margin-top: 30px;">
                            <div class="stat-card">
                                <h3>${relatorio.length}</h3>
                                <p>Total de Testes</p>
                            </div>
                            <div class="stat-card">
                                <h3>${relatorio.filter(t => t.resultado === 'negativo').length}</h3>
                                <p>Negativos</p>
                            </div>
                            <div class="stat-card">
                                <h3>${relatorio.filter(t => t.resultado === 'positivo').length}</h3>
                                <p>Positivos</p>
                            </div>
                            <div class="stat-card">
                                <h3>${relatorio.filter(t => t.resultado === 'pendente').length}</h3>
                                <p>Pendentes</p>
                            </div>
                        </div>
                        <h3 style="margin-top: 30px;">Detalhamento dos Testes</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Atleta</th>
                                        <th>Clube</th>
                                        <th>Laboratório</th>
                                        <th>Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    relatorio.forEach(t => {
                        const resultadoClass = t.resultado === 'negativo' ? 'badge-success' :
                            t.resultado === 'positivo' ? 'badge-danger' : 'badge-warning';
                        html += `
                            <tr>
                                <td>${t.data_coleta ? new Date(t.data_coleta).toLocaleDateString('pt-BR') : '-'}</td>
                                <td>${escapeHtml(t.atleta_nome || '-')}</td>
                                <td>${escapeHtml(t.clube_nome || '-')}</td>
                                <td>${escapeHtml(t.laboratorio_nome || '-')}</td>
                                <td><span class="badge ${resultadoClass}">${t.resultado.toUpperCase()}</span></td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table></div>`;
                    document.getElementById('resultado-relatorio').innerHTML = html;
                }
            } catch (error) {
                showAlert('Erro ao gerar relatório', 'danger');
            }
        }

        function filtrarAtletas() {
            const busca = document.getElementById('search-atleta').value.toLowerCase();
            const linhas = document.querySelectorAll('#tabela-atletas tbody tr');

            linhas.forEach(linha => {
                const texto = linha.textContent.toLowerCase();
                linha.style.display = texto.includes(busca) ? '' : 'none';
            });
        }

        async function verHistoricoTestes(atletaId, atletaNome) {
            try {
                const result = await fetchJson(`${API_URL}testes/atleta/${atletaId}`);

                if (result && result.success) {
                    const testes = result.data || [];
                    let html = `
                        <div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; display:flex; align-items:center; justify-content:center;" onclick="this.remove()">
                            <div style="background: white; padding: 30px; border-radius: 15px; max-width: 900px; max-height: 80vh; overflow-y: auto;" onclick="event.stopPropagation()">
                                <h2>Histórico de Testes - ${escapeHtml(atletaNome)}</h2>
                                <p style="margin-bottom: 20px; color: #666;">Total de testes: ${testes.length}</p>
                    `;

                    if (testes.length > 0) {
                        html += `<div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Laboratório</th>
                                        <th>Tipo</th>
                                        <th>Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        testes.forEach(teste => {
                            const resultadoClass = teste.resultado === 'negativo' ? 'badge-success' :
                                teste.resultado === 'positivo' ? 'badge-danger' : 'badge-warning';
                            html += `
                                <tr>
                                    <td>${teste.data_coleta ? new Date(teste.data_coleta).toLocaleDateString('pt-BR') : '-'}</td>
                                    <td>${escapeHtml(teste.laboratorio_nome || '-')}</td>
                                    <td>${escapeHtml(teste.tipo_teste || '-')}</td>
                                    <td><span class="badge ${resultadoClass}">${teste.resultado.toUpperCase()}</span></td>
                                </tr>
                            `;
                        });
                        html += `</tbody></table></div>`;
                    } else {
                        html += '<p class="empty-state">Nenhum teste registrado</p>';
                    }

                    html += `
                                <div style="text-align:right; margin-top:20px;">
                                    <button class="btn btn-primary" onclick="this.closest('.modal-overlay').remove()">Fechar</button>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.insertAdjacentHTML('beforeend', html);
                }
            } catch (error) {
                showAlert('Erro ao carregar histórico', 'danger');
            }
        }

        async function verDetalhes(testeId) {
            try {
                const result = await fetchJson(`${API_URL}testes/${testeId}`);

                if (result && result.success) {
                    const teste = result.data || {};
                    const modal = `
                        <div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; display:flex; align-items:center; justify-content:center;" onclick="this.remove()">
                            <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%;" onclick="event.stopPropagation()">
                                <h2>Detalhes do Teste #${teste.id}</h2>
                                <div style="margin: 20px 0;">
                                    <p><strong>Atleta:</strong> ${escapeHtml(teste.atleta_nome || '-')}</p>
                                    <p><strong>Clube:</strong> ${escapeHtml(teste.clube_nome || '-')}</p>
                                    <p><strong>Data da Coleta:</strong> ${teste.data_coleta ? new Date(teste.data_coleta).toLocaleDateString('pt-BR') : '-'}</p>
                                    <p><strong>Laboratório:</strong> ${escapeHtml(teste.laboratorio_nome || '-')}</p>
                                    <p><strong>Tipo de Teste:</strong> ${escapeHtml(teste.tipo_teste || '-')}</p>
                                    <p><strong>Resultado:</strong> <span class="badge ${teste.resultado === 'negativo' ? 'badge-success' : teste.resultado === 'positivo' ? 'badge-danger' : 'badge-warning'}">${teste.resultado.toUpperCase()}</span></p>
                                    ${teste.substancia_detectada ? `<p><strong>Substância:</strong> ${escapeHtml(teste.substancia_detectada)}</p>` : ''}
                                    ${teste.observacoes ? `<p><strong>Observações:</strong> ${escapeHtml(teste.observacoes)}</p>` : ''}
                                </div>
                                <div style="text-align:right;">
                                    <button class="btn btn-primary" onclick="this.closest('.modal-overlay').remove()">Fechar</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modal);
                }
            } catch (error) {
                showAlert('Erro ao carregar detalhes', 'danger');
            }
        }

        function atualizarResultado(testeId) {
            const modal = `
                <div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; display:flex; align-items:center; justify-content:center;" onclick="this.remove()">
                    <div style="background: white; padding: 30px; border-radius: 15px; max-width: 600px; width: 90%;" onclick="event.stopPropagation()">
                        <h2>Atualizar Resultado do Teste</h2>
                        <form onsubmit="salvarResultado(event, ${testeId})">
                            <div class="form-group">
                                <label>Resultado *</label>
                                <select name="resultado" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                                    <option value="pendente">Pendente</option>
                                    <option value="negativo">Negativo</option>
                                    <option value="positivo">Positivo</option>
                                    <option value="inconclusivo">Inconclusivo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Substância Detectada (se positivo)</label>
                                <input type="text" name="substancia_detectada" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                            <div class="form-group">
                                <label>Nível da Substância</label>
                                <input type="text" name="nivel_substancia" placeholder="Ex: 10 ng/mL" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                            <div class="form-group">
                                <label>Observações</label>
                                <textarea name="observacoes" rows="3" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">💾 Salvar Resultado</button>
                                <button type="button" class="btn btn-danger" onclick="this.closest('.modal-overlay').remove()">❌ Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
        }

        async function salvarResultado(e, testeId) {
            e.preventDefault();
            const form = e.target;

            try {
                const formData = {
                    resultado: form.resultado.value,
                    substancia_detectada: form.substancia_detectada.value || null,
                    nivel_substancia: form.nivel_substancia?.value || null,
                    observacoes: form.observacoes.value || null,
                    // Se resultado não é pendente, definir data_resultado
                    data_resultado: form.resultado.value !== 'pendente' ? new Date().toISOString().split('T')[0] : null
                };

                console.log('Enviando dados do resultado:', formData);

                const result = await fetchJson(`testes/${testeId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                if (result && result.success) {
                    showAlert('Resultado atualizado com sucesso!', 'success');
                    document.querySelector('.modal-overlay').remove();
                    carregarTestes();
                } else {
                    showAlert(result.message || 'Erro ao atualizar resultado', 'danger');
                }
            } catch (error) {
                showAlert('Erro ao processar requisição: ' + error.message, 'danger');
            }
        }

        function formatarCPF(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }

            input.value = value;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escapeJs(str) {
            if (!str) return '';
            return str.toString()
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '\\"')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '\\r');
        }

        window.onload = function() {
            carregarDashboard();

            const hoje = new Date();
            const umMesAtras = new Date();
            umMesAtras.setMonth(umMesAtras.getMonth() - 1);

            document.getElementById('data-inicio-relatorio').value = umMesAtras.toISOString().split('T')[0];
            document.getElementById('data-fim-relatorio').value = hoje.toISOString().split('T')[0];
        };
    </script>
</body>
</html>
