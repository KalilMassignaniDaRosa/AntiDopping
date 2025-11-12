-- Banco de dados do Sistema Antidoping CBF
CREATE DATABASE IF NOT EXISTS cbf_antidoping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cbf_antidoping;

-- Tabela de Atletas
CREATE TABLE athletes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    birth_date DATE NOT NULL,
    federation VARCHAR(100) NOT NULL,
    club VARCHAR(100) NOT NULL,
    sport VARCHAR(50) NOT NULL,
    position VARCHAR(50),
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_athletes_cpf (cpf),
    INDEX idx_athletes_status (status),
    INDEX idx_athletes_federation (federation),
    INDEX idx_athletes_club (club),
    INDEX idx_athletes_registration (registration_number)
);

-- Tabela de Laboratórios
CREATE TABLE laboratories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    accreditation_status ENUM('accredited', 'suspended', 'revoked') DEFAULT 'accredited',
    accreditation_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_labs_status (accreditation_status),
    INDEX idx_labs_code (code)
);

-- Tabela de Testes Antidoping
CREATE TABLE doping_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    athlete_id INT NOT NULL,
    test_type ENUM('blood', 'urine', 'both') NOT NULL,
    test_date DATE NOT NULL,
    laboratory_id INT NOT NULL,
    collection_officer VARCHAR(255) NOT NULL,
    sample_code VARCHAR(50) UNIQUE NOT NULL,
    collection_location VARCHAR(255),
    test_reason ENUM('routine', 'competition', 'suspicion', 'random') DEFAULT 'routine',
    result ENUM('positive', 'negative', 'inconclusive'),
    result_date DATE,
    analyzed_substances TEXT,
    technical_manager VARCHAR(255),
    notes TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT,
    FOREIGN KEY (laboratory_id) REFERENCES laboratories(id) ON DELETE RESTRICT,
    INDEX idx_tests_athlete (athlete_id),
    INDEX idx_tests_laboratory (laboratory_id),
    INDEX idx_tests_status (status),
    INDEX idx_tests_date (test_date),
    INDEX idx_tests_sample_code (sample_code)
);

-- Tabela de Substâncias Proibidas (Cache da WADA)
CREATE TABLE prohibited_substances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    substance_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    prohibited_status ENUM('prohibited', 'allowed') DEFAULT 'prohibited',
    prohibited_in_competition BOOLEAN DEFAULT TRUE,
    prohibited_out_competition BOOLEAN DEFAULT TRUE,
    update_date DATE NOT NULL,
    source VARCHAR(50) DEFAULT 'WADA',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_substances_name (substance_name),
    INDEX idx_substances_category (category)
);

-- Tabela de Alertas para Resultados Positivos
CREATE TABLE positive_result_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    alert_level ENUM('low', 'medium', 'high') DEFAULT 'high',
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    reviewed_by VARCHAR(100),
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES doping_tests(id) ON DELETE CASCADE,
    INDEX idx_alerts_test (test_id),
    INDEX idx_alerts_status (status)
);

-- Tabela de Logs do Sistema
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('INFO', 'WARNING', 'ERROR', 'DEBUG') DEFAULT 'INFO',
    message TEXT NOT NULL,
    category VARCHAR(50),
    user_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_level (level),
    INDEX idx_logs_category (category),
    INDEX idx_logs_created (created_at)
);

-- Tabela de Usuários do Sistema
CREATE TABLE system_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'user') DEFAULT 'user',
    federation VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_username (username),
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
);

-- Inserir dados iniciais
INSERT INTO laboratories (name, code, address, contact_email, contact_phone) VALUES
('Laboratório Brasileiro de Controle de Dopagem - LBCD', 'LBCD-BR', 'Av. Professor Lineu Prestes, 2242, São Paulo - SP', 'contato@lbcd.org.br', '(11) 3091-2934'),
('Laboratório Olímpico do Brasil - LABDOP', 'LABDOP-RJ', 'Rua Paulino Fernandes, 100, Rio de Janeiro - RJ', 'contato@labdop.gov.br', '(21) 3433-5777');

INSERT INTO system_users (username, email, password_hash, full_name, role, federation) VALUES
('cbf_admin', 'admin@cbf.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador CBF', 'admin', 'CBF'),
('lab_technician', 'tecnico@lbcd.org.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Técnico Laboratorial', 'technician', NULL);

-- Inserir algumas substâncias proibidas exemplo
INSERT INTO prohibited_substances (substance_name, category, prohibited_status, update_date) VALUES
('Eritropoietina (EPO)', 'Agentes Anabólicos', 'prohibited', '2024-01-01'),
('Testosterona', 'Esteróides Androgênicos Anabólicos', 'prohibited', '2024-01-01'),
('Cocaína', 'Estimulantes', 'prohibited', '2024-01-01'),
('Cannabis', 'Canabinóides', 'prohibited', '2024-01-01'),
('Furosemida', 'Diuréticos e Mascarantes', 'prohibited', '2024-01-01');