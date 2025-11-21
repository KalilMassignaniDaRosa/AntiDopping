<?php
// Inicia sessão se necessário
session_start();

$url_base = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$url_api = rtrim($url_base, '/') . '/api/';

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
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>⚽ CBF - Sistema Antidoping</h1>
            <p>Gestão Integrada de Atletas e Testes Antidoping</p>
        </div>

        <div class="nav">
            <button onclick="mostrarSecao('dashboard', this)" class="active">📊 Dashboard</button>
            <button onclick="mostrarSecao('atletas', this)">👥 Atletas</button>
            <button onclick="mostrarSecao('cadastro-atleta', this)" id="btn-novo-atleta">➕ Novo Atleta</button>
            <button onclick="mostrarSecao('testes', this)">🔬 Testes</button>
            <button onclick="mostrarSecao('novo-teste', this)">➕ Novo Teste</button>
            <button onclick="mostrarSecao('relatorios', this)">📈 Relatórios</button>
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
                    <select id="filter-status" onchange="carregarAtletas()">
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
                    <select id="filter-resultado" onchange="carregarTestes()">
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
        // O JavaScript permanece EXATAMENTE o mesmo que na versão anterior
        const URL_API = '<?php echo $url_api; ?>';

        function mostrarAlerta(mensagem, tipo = 'success') {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${tipo}`;
            alert.innerHTML = `<strong>${tipo === 'success' ? '✅' : '⚠️'}</strong> ${mensagem}`;
            container.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        async function buscarJson(url, opcoes = {}) {
            try {
                if (url.startsWith('http')) {
                    // URL completa - usa como está
                } else if (url.startsWith('/')) {
                    url = window.location.origin + url;
                } else {
                    url = URL_API + url;
                }

                console.log('Fetch URL:', url);

                const opcoesPadrao = {
                    headers: {}
                };

                if (!(opcoes.body instanceof FormData) && !opcoes.headers?.['Content-Type']) {
                    opcoesPadrao.headers['Content-Type'] = 'application/json';
                }

                opcoesPadrao.headers['Accept'] = 'application/json';

                const opcoesFinais = {
                    ...opcoesPadrao,
                    ...opcoes
                };

                if (opcoesFinais.body && typeof opcoesFinais.body === 'object' && !(opcoesFinais.body instanceof FormData)) {
                    opcoesFinais.body = JSON.stringify(opcoesFinais.body);
                }

                const resposta = await fetch(url, opcoesFinais);

                if (!resposta.ok) {
                    const textoErro = await resposta.text();
                    throw new Error(`HTTP ${resposta.status} - ${resposta.statusText}. Response: ${textoErro.substring(0, 200)}`);
                }

                const texto = await resposta.text();

                if (!texto) {
                    return {
                        sucesso: true,
                        dados: null
                    };
                }

                try {
                    return JSON.parse(texto);
                } catch (erroParse) {
                    console.error('Resposta não-JSON:', texto.substring(0, 500));
                    throw new Error('Resposta do servidor não é JSON válido: ' + texto.substring(0, 100));
                }

            } catch (erro) {
                console.error('Erro fetch:', erro.message, 'URL:', url);
                throw erro;
            }
        }

        function mostrarSecao(idSecao, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav button').forEach(b => b.classList.remove('active'));

            document.getElementById(idSecao).classList.add('active');
            btn.classList.add('active');

            if (idSecao === 'atletas') carregarAtletas();
            if (idSecao === 'testes') carregarTestes();
            if (idSecao === 'novo-teste') {
                carregarAtletasSelect();
                carregarLaboratorios();
            }
            if (idSecao === 'cadastro-atleta') carregarClubes();
            if (idSecao === 'dashboard') carregarDashboard();
            if (idSecao === 'cadastro-atleta') resetarFormAtleta();
        }

        async function carregarDashboard() {
            try {
                const resultado = await buscarJson(`${URL_API}testes/dashboard`);
                if (resultado && resultado.sucesso) {
                    const estatisticas = resultado.dados || {};

                    document.getElementById('total-atletas-ativos').textContent = estatisticas.total_atletas_ativos || 0;
                    document.getElementById('total-testes').textContent = estatisticas.total_testes || 0;
                    document.getElementById('testes-pendentes').textContent = estatisticas.testes_pendentes || 0;
                    document.getElementById('testes-positivos').textContent = estatisticas.testes_positivos || 0;

                } else {
                    mostrarAlerta('Erro ao carregar dados do dashboard', 'danger');
                }
            } catch (erro) {
                mostrarAlerta('Erro ao carregar dashboard: ' + erro.message, 'danger');

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
                const url = status ? `${URL_API}atletas/status/${status}` : `${URL_API}atletas`;

                const resultado = await buscarJson(url);

                if (resultado && resultado.sucesso && Array.isArray(resultado.dados)) {
                    if (resultado.dados.length > 0) {
                        resultado.dados.forEach(atleta => {
                            const statusClass = atleta.status === 'ativo' ? 'badge-success' :
                                atleta.status === 'suspenso' ? 'badge-danger' : 'badge-warning';

                            tbody.innerHTML += `
                                <tr>
                                    <td><strong>${escaparHtml(atleta.nome)}</strong></td>
                                    <td>${escaparHtml(atleta.cpf || '-')}</td>
                                    <td>${atleta.idade ? atleta.idade + ' anos' : '-'}</td>
                                    <td>${escaparHtml(atleta.clube_nome || '-')}</td>
                                    <td>${escaparHtml(atleta.posicao || '-')}</td>
                                    <td><span class="badge ${atleta.status === 'ativo' ? 'badge-success' : atleta.status === 'suspenso' ? 'badge-warning' : 'badge-danger'}">${atleta.status.toUpperCase()}</span></td>
                                    <td class="actions">
                                        <button class="btn-success" onclick="verHistoricoTestes(${atleta.id}, '${escaparJs(atleta.nome)}')">📋 Histórico</button>
                                        <button class="btn-warning" onclick="editarAtleta(${atleta.id})">✏️ Editar</button>
                                        <button class="btn-info" onclick="mostrarModalStatus(${atleta.id}, '${escaparJs(atleta.nome)}', '${atleta.status}')">
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
            } catch (erro) {
                mostrarAlerta('Erro ao carregar atletas', 'danger');
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
                const resultado = await buscarJson(`${URL_API}testes`);
                if (resultado && resultado.sucesso && Array.isArray(resultado.dados)) {
                    let testes = resultado.dados;
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
                                <td><strong>${escaparHtml(teste.atleta_nome || '-')}</strong></td>
                                <td>${escaparHtml(teste.clube_nome || '-')}</td>
                                <td>${escaparHtml(teste.laboratorio_nome || '-')}</td>
                                <td>${escaparHtml(teste.tipo_teste || '-')}</td>
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
            } catch (erro) {
                mostrarAlerta('Erro ao carregar testes', 'danger');
            } finally {
                loading.style.display = 'none';
            }
        }

        async function carregarClubes() {
            try {
                const resultado = await buscarJson(`clubes`);
                const select = document.getElementById('select-clube');

                if (!select) {
                    console.error('❌ Elemento select-clube não encontrado');
                    return;
                }

                select.innerHTML = '<option value="">Selecione um clube</option>';

                if (resultado && resultado.sucesso && Array.isArray(resultado.dados)) {
                    resultado.dados.forEach(clube => {
                        const option = document.createElement('option');
                        option.value = clube.id;
                        option.textContent = `${clube.nome} - ${clube.cidade || ''}`;
                        select.appendChild(option);
                    });
                    console.log('✅ Clubes carregados:', resultado.dados.length);
                } else {
                    console.error('❌ Erro ao carregar clubes:', resultado);
                }
            } catch (erro) {
                console.error('❌ Erro ao carregar clubes:', erro);
            }
        }

        async function carregarAtletasSelect() {
            try {
                const resultado = await buscarJson(`${URL_API}atletas/status/ativo`);
                const select = document.getElementById('select-atleta-teste');
                select.innerHTML = '<option value="">Selecione um atleta</option>';

                if (resultado && resultado.sucesso && Array.isArray(resultado.dados)) {
                    resultado.dados.forEach(atleta => {
                        const option = document.createElement('option');
                        option.value = atleta.id;
                        option.textContent = `${atleta.nome} - ${atleta.clube_nome || ''}`;
                        select.appendChild(option);
                    });
                }
            } catch (erro) {
                console.error('Erro atletas select:', erro);
            }
        }

        async function carregarLaboratorios() {
            try {
                const resultado = await buscarJson(`${URL_API}laboratorios`);
                const select = document.getElementById('select-laboratorio');
                select.innerHTML = '<option value="">Selecione um laboratório</option>';

                if (resultado && resultado.sucesso && Array.isArray(resultado.dados)) {
                    resultado.dados.forEach(lab => {
                        const wada = lab.credenciado_wada ? '✓ WADA' : '';
                        const option = document.createElement('option');
                        option.value = lab.id;
                        option.textContent = `${lab.nome} - ${lab.cidade || ''} ${wada}`;
                        select.appendChild(option);
                    });
                }
            } catch (erro) {
                console.error('Erro laboratórios:', erro);
            }
        }

        async function salvarAtleta(e) {
            e.preventDefault();

            const id = document.getElementById('atleta-id').value;
            const isEdit = !!id;

            try {
                const dadosForm = {
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
                    dadosForm
                });

                const url = isEdit ? `atletas/${id}` : 'atletas';
                const metodo = isEdit ? 'PUT' : 'POST';

                const resposta = await fetch(URL_API + url, {
                    method: metodo,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dadosForm)
                });

                const textoResposta = await resposta.text();
                console.log('📨 RESPOSTA DO SERVIDOR:', textoResposta);

                let resultado;
                try {
                    resultado = JSON.parse(textoResposta);
                } catch (e) {
                    throw new Error(`Resposta não é JSON: ${textoResposta.substring(0, 100)}`);
                }

                if (resultado.sucesso) {
                    mostrarAlerta(`Atleta ${isEdit ? 'atualizado' : 'cadastrado'} com sucesso!`, 'success');
                    resetarFormAtleta();
                    carregarAtletas();
                    mostrarSecao('atletas', document.querySelector('.nav button[onclick*="atletas"]'));
                } else {
                    throw new Error(resultado.mensagem || 'Erro desconhecido');
                }

            } catch (erro) {
                console.error('💥 ERRO NA ATUALIZAÇÃO:', erro);
                mostrarAlerta(`❌ Erro: ${erro.message}`, 'danger');
            }
        }

        function resetarFormAtleta() {
            document.getElementById('form-atleta').reset();
            document.getElementById('atleta-id').value = '';
            document.getElementById('titulo-cadastro-atleta').textContent = 'Cadastrar Novo Atleta';
            document.getElementById('btn-salvar-atleta').textContent = '✅ Cadastrar Atleta';
            console.log('🔄 Formulário resetado para modo de criação');
        }

        async function editarAtleta(id) {
            try {
                console.log('🔍 Carregando dados do atleta ID:', id);

                const resultado = await buscarJson(`atletas/${id}`);

                if (resultado.sucesso && resultado.dados) {
                    const atleta = resultado.dados;
                    console.log('📋 Dados do atleta carregados:', atleta);

                    mostrarSecao('cadastro-atleta', document.getElementById('btn-novo-atleta'));

                    await new Promise(resolve => setTimeout(resolve, 100));

                    let cpfFormatado = atleta.cpf;
                    if (cpfFormatado && cpfFormatado.length === 11) {
                        cpfFormatado = cpfFormatado.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    }

                    await carregarClubes();

                    const setValue = (seletor, valor) => {
                        const elemento = document.querySelector(seletor);
                        if (elemento) {
                            elemento.value = valor;
                            console.log(`✅ Set ${seletor} = ${valor}`);
                        } else {
                            console.error(`❌ Elemento não encontrado: ${seletor}`);
                        }
                    };

                    setValue('#form-atleta [name="nome"]', atleta.nome || '');
                    setValue('#form-atleta [name="cpf"]', cpfFormatado || '');
                    setValue('#form-atleta [name="data_nascimento"]', atleta.data_nascimento || '');
                    setValue('#form-atleta [name="clube_id"]', atleta.clube_id || '');
                    setValue('#form-atleta [name="posicao"]', atleta.posicao || '');
                    setValue('#form-atleta [name="status"]', atleta.status || 'ativo');
                    setValue('#form-atleta [name="observacoes"]', atleta.observacoes || '');

                    document.getElementById('atleta-id').value = atleta.id;
                    document.getElementById('titulo-cadastro-atleta').textContent = 'Editar Atleta';
                    document.getElementById('btn-salvar-atleta').textContent = '💾 Salvar Alterações';

                    console.log('✅ Formulário preenchido para edição');

                } else {
                    throw new Error(resultado.mensagem || 'Dados do atleta não encontrados');
                }
            } catch (erro) {
                console.error('❌ Erro ao carregar dados do atleta:', erro);
                mostrarAlerta('❌ Erro ao carregar dados do atleta: ' + erro.message, 'danger');
            }
        }

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
                const resultado = await buscarJson(`atletas/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: config.status
                    })
                });

                if (resultado.sucesso) {
                    mostrarAlerta(`Atleta ${config.mensagem} com sucesso!`, 'success');
                    carregarAtletas();
                } else {
                    mostrarAlerta('Erro ao alterar status do atleta: ' + resultado.mensagem, 'danger');
                }
            } catch (erro) {
                mostrarAlerta('Erro ao processar requisição: ' + erro.message, 'danger');
            }
        }

        function mostrarModalStatus(atletaId, atletaNome, statusAtual) {
            const modal = `
                <div class="modal-overlay" onclick="this.remove()">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <h2>Gerenciar Status - ${escaparHtml(atletaNome)}</h2>
                        <p style="margin-bottom: 20px; color: #666;">Status atual:
                            <span class="badge ${statusAtual === 'ativo' ? 'badge-success' : statusAtual === 'suspenso' ? 'badge-warning' : 'badge-danger'}">
                                ${statusAtual.toUpperCase()}
                            </span>
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            ${statusAtual !== 'ativo' ?
                                `<button class="btn btn-success" onclick="gerenciarStatusAtleta(${atletaId}, 'ativar', '${escaparJs(atletaNome)}'); this.closest('.modal-overlay').remove();">
                                    ✅ Ativar Atleta
                                </button>` : ''
                            }

                            ${statusAtual !== 'inativo' ?
                                `<button class="btn btn-danger" onclick="gerenciarStatusAtleta(${atletaId}, 'desativar', '${escaparJs(atletaNome)}'); this.closest('.modal-overlay').remove();">
                                    🚫 Desativar Atleta
                                </button>` : ''
                            }

                            ${statusAtual !== 'suspenso' ?
                                `<button class="btn btn-warning" onclick="gerenciarStatusAtleta(${atletaId}, 'suspender', '${escaparJs(atletaNome)}'); this.closest('.modal-overlay').remove();">
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
            const dadosForm = new FormData(e.target);

            try {
                const resultado = await buscarJson(`${URL_API}testes`, {
                    method: 'POST',
                    body: dadosForm
                });

                if (resultado && resultado.sucesso) {
                    mostrarAlerta('Teste registrado com sucesso!', 'success');
                    e.target.reset();
                    mostrarSecao('testes', document.querySelector('.nav button[onclick*="testes"]'));
                } else {
                    mostrarAlerta(resultado.mensagem || 'Erro ao registrar teste', 'danger');
                }
            } catch (erro) {
                mostrarAlerta('Erro ao processar requisição', 'danger');
            }
        }

        async function gerarRelatorio() {
            const dataInicio = document.getElementById('data-inicio-relatorio').value;
            const dataFim = document.getElementById('data-fim-relatorio').value;

            if (!dataInicio || !dataFim) {
                mostrarAlerta('Selecione as datas de início e fim', 'danger');
                return;
            }

            try {
                const resultado = await buscarJson(`${URL_API}testes/relatorio?data_inicio=${dataInicio}&data_fim=${dataFim}`);

                if (resultado && resultado.sucesso) {
                    const relatorio = resultado.dados || [];

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
                                <td>${escaparHtml(t.atleta_nome || '-')}</td>
                                <td>${escaparHtml(t.clube_nome || '-')}</td>
                                <td>${escaparHtml(t.laboratorio_nome || '-')}</td>
                                <td><span class="badge ${resultadoClass}">${t.resultado.toUpperCase()}</span></td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table></div>`;
                    document.getElementById('resultado-relatorio').innerHTML = html;
                }
            } catch (erro) {
                mostrarAlerta('Erro ao gerar relatório', 'danger');
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
                const resultado = await buscarJson(`${URL_API}testes/atleta/${atletaId}`);

                if (resultado && resultado.sucesso) {
                    const testes = resultado.dados || [];
                    let html = `
                        <div class="modal-overlay" onclick="this.remove()">
                            <div class="modal-content" onclick="event.stopPropagation()">
                                <h2>Histórico de Testes - ${escaparHtml(atletaNome)}</h2>
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
                                    <td>${escaparHtml(teste.laboratorio_nome || '-')}</td>
                                    <td>${escaparHtml(teste.tipo_teste || '-')}</td>
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
            } catch (erro) {
                mostrarAlerta('Erro ao carregar histórico', 'danger');
            }
        }

        async function verDetalhes(testeId) {
            try {
                const resultado = await buscarJson(`${URL_API}testes/${testeId}`);

                if (resultado && resultado.sucesso) {
                    const teste = resultado.dados || {};
                    const modal = `
                        <div class="modal-overlay" onclick="this.remove()">
                            <div class="modal-content" onclick="event.stopPropagation()">
                                <h2>Detalhes do Teste #${teste.id}</h2>
                                <div style="margin: 20px 0;">
                                    <p><strong>Atleta:</strong> ${escaparHtml(teste.atleta_nome || '-')}</p>
                                    <p><strong>Clube:</strong> ${escaparHtml(teste.clube_nome || '-')}</p>
                                    <p><strong>Data da Coleta:</strong> ${teste.data_coleta ? new Date(teste.data_coleta).toLocaleDateString('pt-BR') : '-'}</p>
                                    <p><strong>Laboratório:</strong> ${escaparHtml(teste.laboratorio_nome || '-')}</p>
                                    <p><strong>Tipo de Teste:</strong> ${escaparHtml(teste.tipo_teste || '-')}</p>
                                    <p><strong>Resultado:</strong> <span class="badge ${teste.resultado === 'negativo' ? 'badge-success' : teste.resultado === 'positivo' ? 'badge-danger' : 'badge-warning'}">${teste.resultado.toUpperCase()}</span></p>
                                    ${teste.substancia_detectada ? `<p><strong>Substância:</strong> ${escaparHtml(teste.substancia_detectada)}</p>` : ''}
                                    ${teste.observacoes ? `<p><strong>Observações:</strong> ${escaparHtml(teste.observacoes)}</p>` : ''}
                                </div>
                                <div style="text-align:right;">
                                    <button class="btn btn-primary" onclick="this.closest('.modal-overlay').remove()">Fechar</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modal);
                }
            } catch (erro) {
                mostrarAlerta('Erro ao carregar detalhes', 'danger');
            }
        }

        function atualizarResultado(testeId) {
            const modal = `
                <div class="modal-overlay" onclick="this.remove()">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <h2>Atualizar Resultado do Teste</h2>
                        <form onsubmit="salvarResultado(event, ${testeId})">
                            <div class="modal-form-group">
                                <label>Resultado *</label>
                                <select name="resultado" required>
                                    <option value="pendente">Pendente</option>
                                    <option value="negativo">Negativo</option>
                                    <option value="positivo">Positivo</option>
                                    <option value="inconclusivo">Inconclusivo</option>
                                </select>
                            </div>
                            <div class="modal-form-group">
                                <label>Substância Detectada (se positivo)</label>
                                <input type="text" name="substancia_detectada">
                            </div>
                            <div class="modal-form-group">
                                <label>Nível da Substância</label>
                                <input type="text" name="nivel_substancia" placeholder="Ex: 10 ng/mL">
                            </div>
                            <div class="modal-form-group">
                                <label>Observações</label>
                                <textarea name="observacoes" rows="3"></textarea>
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
                const dadosForm = {
                    resultado: form.resultado.value,
                    substancia_detectada: form.substancia_detectada.value || null,
                    nivel_substancia: form.nivel_substancia?.value || null,
                    observacoes: form.observacoes.value || null,
                    data_resultado: form.resultado.value !== 'pendente' ? new Date().toISOString().split('T')[0] : null
                };

                console.log('Enviando dados do resultado:', dadosForm);

                const resultado = await buscarJson(`testes/${testeId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dadosForm)
                });

                if (resultado && resultado.sucesso) {
                    mostrarAlerta('Resultado atualizado com sucesso!', 'success');
                    document.querySelector('.modal-overlay').remove();
                    carregarTestes();
                } else {
                    mostrarAlerta(resultado.mensagem || 'Erro ao atualizar resultado', 'danger');
                }
            } catch (erro) {
                mostrarAlerta('Erro ao processar requisição: ' + erro.message, 'danger');
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

        function escaparHtml(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escaparJs(str) {
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