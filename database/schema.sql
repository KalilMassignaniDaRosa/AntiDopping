-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS cbf_antidoping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cbf_antidoping;

-- Tabela de Federações
CREATE TABLE federacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL,
    responsavel VARCHAR(100),
    email VARCHAR(100),
    telefone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Clubes
CREATE TABLE clubes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    federacao_id INT NOT NULL,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (federacao_id) REFERENCES federacoes(id) ON DELETE RESTRICT
);

-- Tabela de Atletas
CREATE TABLE atletas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    clube_id INT NOT NULL,
    posicao VARCHAR(50),
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clube_id) REFERENCES clubes(id) ON DELETE RESTRICT
);

-- Tabela de Laboratórios
CREATE TABLE laboratorios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    pais VARCHAR(50) DEFAULT 'Brasil',
    credenciamento VARCHAR(50),
    credenciado_wada BOOLEAN DEFAULT FALSE,
    email VARCHAR(100),
    telefone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Testes Antidoping
CREATE TABLE testes_antidoping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    atleta_id INT NOT NULL,
    laboratorio_id INT NOT NULL,
    data_coleta DATE NOT NULL,
    hora_coleta TIME,
    tipo_teste ENUM('urina', 'sangue', 'ambos') NOT NULL,
    resultado ENUM('negativo', 'positivo', 'pendente', 'inconclusivo') DEFAULT 'pendente',
    substancia_detectada VARCHAR(200),
    nivel_substancia VARCHAR(100),
    observacoes TEXT,
    usuario_registro VARCHAR(100),
    data_resultado DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE RESTRICT,
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorios(id) ON DELETE RESTRICT
);

-- Inserção de dados de exemplo
INSERT INTO federacoes (nome, estado, responsavel, email, telefone) VALUES
('Federação Paulista de Futebol', 'SP', 'João Paulo Silva', 'contato@fpf.org.br', '(11) 3456-7890'),
('Federação de Futebol do Rio de Janeiro', 'RJ', 'Carlos Alberto Santos', 'contato@ferj.com.br', '(21) 2345-6789'),
('Federação Gaúcha de Futebol', 'RS', 'Pedro Machado', 'contato@fgf.org.br', '(51) 3234-5678');

INSERT INTO clubes (nome, federacao_id, cidade, estado) VALUES
('Palmeiras', 1, 'São Paulo', 'SP'),
('Corinthians', 1, 'São Paulo', 'SP'),
('Flamengo', 2, 'Rio de Janeiro', 'RJ'),
('Grêmio', 3, 'Porto Alegre', 'RS');

INSERT INTO laboratorios (nome, cidade, estado, pais, credenciamento, credenciado_wada, email, telefone) VALUES
('LADETEC - UFRJ', 'Rio de Janeiro', 'RJ', 'Brasil', 'WADA-2024-001', TRUE, 'contato@ladetec.ufrj.br', '(21) 3938-7000'),
('LAB-SP Antidoping', 'São Paulo', 'SP', 'Brasil', 'WADA-2024-002', TRUE, 'contato@labsp.com.br', '(11) 3091-5000');

INSERT INTO atletas (nome, cpf, data_nascimento, clube_id, posicao, status) VALUES
('Gabriel Mendes Silva', '12345678900', '1995-03-15', 1, 'Atacante', 'ativo'),
('Rafael Costa Santos', '23456789011', '1997-07-22', 1, 'Meio-Campo', 'ativo'),
('Lucas Oliveira Souza', '34567890122', '1993-11-30', 2, 'Zagueiro', 'ativo');

INSERT INTO testes_antidoping (atleta_id, laboratorio_id, data_coleta, hora_coleta, tipo_teste, resultado, data_resultado) VALUES
(1, 1, '2024-11-01', '10:30:00', 'urina', 'negativo', '2024-11-05'),
(2, 1, '2024-11-01', '11:00:00', 'sangue', 'negativo', '2024-11-06'),
(3, 2, '2024-11-05', '14:20:00', 'ambos', 'pendente', NULL);
