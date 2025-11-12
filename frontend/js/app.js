class CBFSystem {
    constructor() {
        this.apiBase = '/api';
        this.token = 'cbf_admin_token'; // Em produção, obter do login
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboard();
    }

    bindEvents() {
        // Navegação
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.showPage(e.target.getAttribute('data-page'));
            });
        });

        // Modal de atleta
        document.getElementById('add-athlete-btn').addEventListener('click', () => {
            this.showAthleteModal();
        });

        document.querySelector('.close').addEventListener('click', () => {
            this.hideAthleteModal();
        });

        document.getElementById('athlete-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createAthlete();
        });

        // Filtros
        document.getElementById('search-athlete').addEventListener('input', (e) => {
            this.searchAthletes(e.target.value);
        });

        document.getElementById('test-filter').addEventListener('change', (e) => {
            this.loadTests({ status: e.target.value });
        });

        // Relatórios
        document.getElementById('generate-report').addEventListener('click', () => {
            this.generateReport();
        });

        document.getElementById('export-csv').addEventListener('click', () => {
            this.exportCSV();
        });

        // Registrar teste
        document.getElementById('register-test-btn').addEventListener('click', () => {
            this.registerTest();
        });
    }

    showPage(pageName) {
        // Esconde todas as páginas
        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });

        // Remove classe active de todos os links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Mostra página selecionada
        document.getElementById(`${pageName}-page`).classList.add('active');
        document.querySelector(`[data-page="${pageName}"]`).classList.add('active');

        // Carrega dados da página
        switch(pageName) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'athletes':
                this.loadAthletes();
                break;
            case 'tests':
                this.loadTests();
                break;
            case 'reports':
                this.loadReports();
                break;
        }
    }

    async apiCall(endpoint, options = {}) {
        const url = `${this.apiBase}${endpoint}`;
        
        const config = {
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error.message);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            this.showError(error.message);
            throw error;
        }
    }

    async loadDashboard() {
        try {
            // Carrega estatísticas
            const [athletesData, testsData, statsData] = await Promise.all([
                this.apiCall('/athletes?limit=5'),
                this.apiCall('/doping-tests?limit=10'),
                this.apiCall('/doping-tests?statistics=true')
            ]);

            // Atualiza estatísticas
            document.getElementById('total-athletes').textContent = 
                athletesData.data.pagination.total;
            document.getElementById('total-tests').textContent = 
                testsData.data.pagination.total;
            
            const pendingTests = testsData.data.data.filter(test => test.status === 'pending').length;
            const positiveTests = statsData.data.reduce((sum, stat) => sum + (stat.positive_tests || 0), 0);

            document.getElementById('pending-tests').textContent = pendingTests;
            document.getElementById('positive-tests').textContent = positiveTests;

            // Atualiza atividade recente
            this.renderRecentTests(testsData.data.data);

        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    async loadAthletes(filters = {}) {
        try {
            const queryString = new URLSearchParams(filters).toString();
            const data = await this.apiCall(`/athletes?${queryString}`);
            this.renderAthletes(data.data.data);
        } catch (error) {
            console.error('Error loading athletes:', error);
        }
    }

    async loadTests(filters = {}) {
        try {
            const queryString = new URLSearchParams(filters).toString();
            const data = await this.apiCall(`/doping-tests?${queryString}`);
            this.renderTests(data.data.data);
        } catch (error) {
            console.error('Error loading tests:', error);
        }
    }

    renderAthletes(athletes) {
        const container = document.getElementById('athletes-list');
        
        if (athletes.length === 0) {
            container.innerHTML = '<p>Nenhum atleta encontrado.</p>';
            return;
        }

        const html = `
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Registro</th>
                            <th>Clube</th>
                            <th>Federação</th>
                            <th>Esporte</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${athletes.map(athlete => `
                            <tr>
                                <td>${athlete.name}</td>
                                <td>${athlete.registration_number}</td>
                                <td>${athlete.club}</td>
                                <td>${athlete.federation}</td>
                                <td>${athlete.sport}</td>
                                <td>
                                    <button class="btn btn-secondary" onclick="system.viewAthlete(${athlete.id})">Ver</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

    renderTests(tests) {
        const container = document.getElementById('tests-list');
        
        if (tests.length === 0) {
            container.innerHTML = '<p>Nenhum teste encontrado.</p>';
            return;
        }

        const html = `
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Código Amostra</th>
                            <th>Atleta</th>
                            <th>Tipo</th>
                            <th>Data</th>
                            <th>Laboratório</th>
                            <th>Status</th>
                            <th>Resultado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tests.map(test => `
                            <tr>
                                <td>${test.sample_code}</td>
                                <td>${test.athlete_name}</td>
                                <td>${this.formatTestType(test.test_type)}</td>
                                <td>${this.formatDate(test.test_date)}</td>
                                <td>${test.laboratory_name}</td>
                                <td>
                                    <span class="status-badge status-${test.status}">
                                        ${this.formatStatus(test.status)}
                                    </span>
                                </td>
                                <td>
                                    ${test.result ? `
                                        <span class="status-badge status-${test.result}">
                                            ${this.formatResult(test.result)}
                                        </span>
                                    ` : '-'}
                                </td>
                                <td>
                                    ${test.status === 'pending' ? `
                                        <button class="btn btn-primary" onclick="system.updateTestResult(${test.id})">
                                            Lançar Resultado
                                        </button>
                                    ` : `
                                        <button class="btn btn-secondary" onclick="system.viewTest(${test.id})">
                                            Detalhes
                                        </button>
                                    `}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

    renderRecentTests(tests) {
        const container = document.getElementById('recent-tests');
        const recentTests = tests.slice(0, 5);

        if (recentTests.length === 0) {
            container.innerHTML = '<p>Nenhuma atividade recente.</p>';
            return;
        }

        const html = `
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Atleta</th>
                            <th>Tipo</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${recentTests.map(test => `
                            <tr>
                                <td>${test.athlete_name}</td>
                                <td>${this.formatTestType(test.test_type)}</td>
                                <td>${this.formatDate(test.test_date)}</td>
                                <td>
                                    <span class="status-badge status-${test.status}">
                                        ${this.formatStatus(test.status)}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

    async createAthlete() {
        const form = document.getElementById('athlete-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            await this.apiCall('/athletes', {
                method: 'POST',
                body: JSON.stringify(data)
            });

            this.hideAthleteModal();
            this.showSuccess('Atleta cadastrado com sucesso!');
            this.loadAthletes();
            form.reset();

        } catch (error) {
            console.error('Error creating athlete:', error);
        }
    }

    showAthleteModal() {
        document.getElementById('athlete-modal').style.display = 'block';
    }

    hideAthleteModal() {
        document.getElementById('athlete-modal').style.display = 'none';
    }

    async searchAthletes(term) {
        if (term.length >= 3) {
            try {
                const data = await this.apiCall(`/athletes?search=${encodeURIComponent(term)}`);
                this.renderAthletes(data.data);
            } catch (error) {
                console.error('Error searching athletes:', error);
            }
        } else if (term.length === 0) {
            this.loadAthletes();
        }
    }

    async generateReport() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const reportType = document.getElementById('report-type').value;

        try {
            const data = await this.apiCall(
                `/reports/general?start_date=${startDate}&end_date=${endDate}&type=${reportType}`
            );
            this.renderReport(data.data);
        } catch (error) {
            console.error('Error generating report:', error);
        }
    }

    renderReport(reportData) {
        const container = document.getElementById('report-results');
        
        let html = `
            <h3>Relatório ${this.formatReportType(reportData.report_type)}</h3>
            <p>Período: ${this.formatDate(reportData.period.start_date)} até ${this.formatDate(reportData.period.end_date)}</p>
        `;

        if (reportData.data.length === 0) {
            html += '<p>Nenhum dado encontrado para o período selecionado.</p>';
        } else {
            html += `
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                ${Object.keys(reportData.data[0]).map(key => 
                                    `<th>${this.formatHeader(key)}</th>`
                                ).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${reportData.data.map(row => `
                                <tr>
                                    ${Object.values(row).map(value => 
                                        `<td>${value || '-'}</td>`
                                    ).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    async exportCSV() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const reportType = document.getElementById('report-type').value;

        window.open(
            `${this.apiBase}/reports/general?start_date=${startDate}&end_date=${endDate}&type=${reportType}&format=csv&token=${this.token}`,
            '_blank'
        );
    }

    // Métodos auxiliares de formatação
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('pt-BR');
    }

    formatTestType(type) {
        const types = {
            'blood': 'Sangue',
            'urine': 'Urina',
            'both': 'Ambos'
        };
        return types[type] || type;
    }

    formatStatus(status) {
        const statusMap = {
            'pending': 'Pendente',
            'completed': 'Concluído',
            'in_progress': 'Em Análise'
        };
        return statusMap[status] || status;
    }

    formatResult(result) {
        const resultMap = {
            'positive': 'Positivo',
            'negative': 'Negativo',
            'inconclusive': 'Inconclusivo'
        };
        return resultMap[result] || result;
    }

    formatReportType(type) {
        const typeMap = {
            'general': 'Geral',
            'detailed': 'Detalhado',
            'laboratory': 'Por Laboratório',
            'federation': 'Por Federação'
        };
        return typeMap[type] || type;
    }

    formatHeader(header) {
        const headers = {
            'test_day': 'Data',
            'tests_count': 'Total de Testes',
            'positive_count': 'Positivos',
            'negative_count': 'Negativos',
            'test_type': 'Tipo de Teste',
            'laboratory_name': 'Laboratório',
            'federation': 'Federação',
            'total_tests': 'Total de Testes',
            'positive_tests': 'Testes Positivos',
            'negative_tests': 'Testes Negativos',
            'avg_processing_days': 'Dias Médios de Processamento'
        };
        return headers[header] || header;
    }

    showError(message) {
        alert(`Erro: ${message}`);
    }

    showSuccess(message) {
        alert(`Sucesso: ${message}`);
    }

    // Métodos para funcionalidades futuras
    viewAthlete(id) {
        alert(`Visualizar atleta ${id} - Funcionalidade em desenvolvimento`);
    }

    viewTest(id) {
        alert(`Visualizar teste ${id} - Funcionalidade em desenvolvimento`);
    }

    updateTestResult(id) {
        alert(`Atualizar resultado do teste ${id} - Funcionalidade em desenvolvimento`);
    }

    registerTest() {
        alert('Registrar novo teste - Funcionalidade em desenvolvimento');
    }
}

// Inicializa o sistema quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.system = new CBFSystem();
});