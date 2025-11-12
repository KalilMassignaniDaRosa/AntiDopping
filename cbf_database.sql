-- Banco de Dados: cbf_antidoping
-- Sistema de Cadastro de Atletas e Acompanhamento Antidoping

CREATE DATABASE IF NOT EXISTS cbf_antidoping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cbf_antidoping;

-- Tabela de Usuários do Sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'federacao', 'clube', 'laboratorio') DEFAULT 'clube',
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB;

-- Tabela de Atletas
CREATE TABLE atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    clube VARCHAR(100),
    posicao VARCHAR(50),
    federacao VARCHAR(50),
    foto VARCHAR(255),
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_cpf (cpf),
    INDEX idx_clube (clube),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tabela de Testes Antidoping
CREATE TABLE testes_antidoping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,
    data_coleta DATE NOT NULL,
    tipo_teste ENUM('urina', 'sangue', 'ambos') NOT NULL,
    laboratorio VARCHAR(100),
    resultado ENUM('negativo', 'positivo', 'pendente', 'invalido') DEFAULT 'pendente',
    substancia_detectada VARCHAR(255),
    observacoes TEXT,
    data_resultado DATE,
    responsavel VARCHAR(100),
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    INDEX idx_atleta (atleta_id),
    INDEX idx_data_coleta (data_coleta),
    INDEX idx_resultado (resultado)
) ENGINE=InnoDB;

-- Tabela de Competições
CREATE TABLE competicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('estadual', 'nacional', 'internacional') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    status ENUM('programada', 'em_andamento', 'finalizada') DEFAULT 'programada',
    INDEX idx_tipo (tipo),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tabela de Testes por Competição
CREATE TABLE testes_competicao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teste_id INT NOT NULL,
    competicao_id INT NOT NULL,
    FOREIGN KEY (teste_id) REFERENCES testes_antidoping(id) ON DELETE CASCADE,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
    INDEX idx_teste (teste_id),
    INDEX idx_competicao (competicao_id)
) ENGINE=InnoDB;

-- Tabela de Logs de Auditoria
CREATE TABLE logs_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(50),
    registro_id INT,
    detalhes TEXT,
    ip_address VARCHAR(45),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (data_hora)
) ENGINE=InnoDB;

-- Inserir usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@cbf.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir dados de exemplo
INSERT INTO atletas (nome, cpf, data_nascimento, clube, posicao, federacao, status, usuario_id) VALUES
('João Silva', '123.456.789-00', '1995-03-15', 'Flamengo', 'Atacante', 'RJ', 'ativo', 1),
('Maria Santos', '987.654.321-00', '1998-07-22', 'Palmeiras', 'Meio-campo', 'SP', 'ativo', 1),
('Pedro Oliveira', '456.789.123-00', '1997-11-10', 'Grêmio', 'Zagueiro', 'RS', 'ativo', 1);

INSERT INTO competicoes (nome, tipo, data_inicio, data_fim, status) VALUES
('Campeonato Brasileiro Série A 2025', 'nacional', '2025-04-12', '2025-12-08', 'programada'),
('Copa do Brasil 2025', 'nacional', '2025-02-18', '2025-09-25', 'programada'),
('Campeonato Carioca 2025', 'estadual', '2025-01-15', '2025-04-20', 'em_andamento');