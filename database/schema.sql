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
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de Clubes
CREATE TABLE clubes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    federacao_id INT NOT NULL,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE RESTRICT,
    FOREIGN KEY (laboratorio_id) REFERENCES laboratorios(id) ON DELETE RESTRICT
);

-- Inserção de dados de exemplo
INSERT INTO federacoes (nome, estado, responsavel, email, telefone) VALUES
('Federação Paulista de Futebol', 'SP', 'João Paulo Silva', 'contato@fpf.org.br', '(11) 3456-7890'),
('Federação de Futebol do Rio de Janeiro', 'RJ', 'Carlos Alberto Santos', 'contato@ferj.com.br', '(21) 2345-6789'),
('Federação Gaúcha de Futebol', 'RS', 'Pedro Machado', 'contato@fgf.org.br', '(51) 3234-5678'),
('Federação Mineira de Futebol', 'MG', 'Ana Cristina Oliveira', 'contato@fmf.com.br', '(31) 3456-7890'),
('Federação Catarinense de Futebol', 'SC', 'Roberto Almeida', 'contato@fcf.com.br', '(48) 3234-5678');

INSERT INTO clubes (nome, federacao_id, cidade, estado) VALUES
('Palmeiras', 1, 'São Paulo', 'SP'),
('Corinthians', 1, 'São Paulo', 'SP'),
('Flamengo', 2, 'Rio de Janeiro', 'RJ'),
('Grêmio', 3, 'Porto Alegre', 'RS'),
('Atlético Mineiro', 4, 'Belo Horizonte', 'MG'),
('Cruzeiro', 4, 'Belo Horizonte', 'MG'),
('Fluminense', 2, 'Rio de Janeiro', 'RJ'),
('Internacional', 3, 'Porto Alegre', 'RS'),
('São Paulo', 1, 'São Paulo', 'SP'),
('Avaí', 5, 'Florianópolis', 'SC');

INSERT INTO laboratorios (nome, cidade, estado, pais, credenciamento, credenciado_wada, email, telefone) VALUES
('LADETEC - UFRJ', 'Rio de Janeiro', 'RJ', 'Brasil', 'WADA-2024-001', TRUE, 'contato@ladetec.ufrj.br', '(21) 3938-7000'),
('LAB-SP Antidoping', 'São Paulo', 'SP', 'Brasil', 'WADA-2024-002', TRUE, 'contato@labsp.com.br', '(11) 3091-5000'),
('Laboratório de Análises DF', 'Brasília', 'DF', 'Brasil', 'WADA-2024-003', TRUE, 'contato@labdf.com.br', '(61) 3345-6789'),
('Lab Minas Gerais', 'Belo Horizonte', 'MG', 'Brasil', 'WADA-2024-004', TRUE, 'contato@labmg.com.br', '(31) 3456-7890');

INSERT INTO atletas (nome, cpf, data_nascimento, clube_id, posicao, status, observacoes) VALUES
('Gabriel Mendes Silva', '12345678900', '1995-03-15', 1, 'Atacante', 'ativo', 'Capitão do time'),
('Rafael Costa Santos', '23456789011', '1997-07-22', 1, 'Meio-Campo', 'ativo', NULL),
('Lucas Oliveira Souza', '34567890122', '1993-11-30', 2, 'Zagueiro', 'ativo', 'Experiente'),
('Carlos Eduardo Lima', '45678901233', '1998-05-10', 3, 'Goleiro', 'ativo', 'Revelação'),
('Fernando Rodrigues', '56789012344', '1994-09-18', 4, 'Atacante', 'ativo', NULL),
('Marcos Vinicius Alves', '67890123455', '1996-12-25', 5, 'Meio-Campo', 'ativo', 'Volante'),
('Ricardo Pereira', '78901234566', '1992-02-14', 6, 'Lateral', 'ativo', NULL),
('Diego Santos', '89012345677', '1999-08-30', 7, 'Atacante', 'ativo', 'Jovem promessa'),
('André Costa', '90123456788', '1991-04-05', 8, 'Zagueiro', 'suspenso', 'Suspenso por doping'),
('Paulo Roberto', '01234567899', '1997-11-12', 9, 'Meio-Campo', 'ativo', NULL);

INSERT INTO testes_antidoping (atleta_id, laboratorio_id, data_coleta, hora_coleta, tipo_teste, resultado, substancia_detectada, nivel_substancia, data_resultado, observacoes) VALUES
(1, 1, '2024-11-01', '10:30:00', 'urina', 'negativo', NULL, NULL, '2024-11-05', 'Coleta realizada no CT'),
(2, 1, '2024-11-01', '11:00:00', 'sangue', 'negativo', NULL, NULL, '2024-11-06', NULL),
(3, 2, '2024-11-05', '14:20:00', 'ambos', 'pendente', NULL, NULL, NULL, 'Aguardando resultado'),
(4, 3, '2024-10-28', '09:15:00', 'urina', 'negativo', NULL, NULL, '2024-11-02', NULL),
(5, 1, '2024-11-10', '16:45:00', 'sangue', 'positivo', 'Estanozolol', '15 ng/mL', '2024-11-15', 'Atleta suspenso'),
(6, 4, '2024-11-08', '08:30:00', 'urina', 'negativo', NULL, NULL, '2024-11-12', NULL),
(7, 2, '2024-11-12', '11:20:00', 'ambos', 'negativo', NULL, NULL, '2024-11-18', NULL),
(8, 3, '2024-11-15', '15:10:00', 'sangue', 'pendente', NULL, NULL, NULL, 'Aguardando análise'),
(9, 1, '2024-09-20', '10:00:00', 'urina', 'positivo', 'Eritropoietina', '8.5 UI/L', '2024-09-28', 'Caso grave - suspensão de 2 anos'),
(10, 4, '2024-11-18', '13:45:00', 'urina', 'negativo', NULL, NULL, '2024-11-22', NULL);