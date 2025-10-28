-- Primeiro, apaga o banco de dados se ele existir
DROP DATABASE IF EXISTS resolva_aqui_ifpi;

-- Criação do banco de dados
CREATE DATABASE resolva_aqui_ifpi
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE resolva_aqui_ifpi;

-- Tabela de matrículas geradas (deve ser criada primeiro)
CREATE TABLE matriculas_geradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(12) NOT NULL UNIQUE,
    tipo_usuario ENUM('professor', 'admin') NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usado BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    matricula VARCHAR(12) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('professor', 'admin') NOT NULL DEFAULT 'professor',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de recursos
CREATE TABLE recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo_recurso ENUM('laboratorio', 'quadra') NOT NULL,
    localizacao VARCHAR(100) NOT NULL,
    capacidade INT,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de agendamentos
CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_recurso INT NOT NULL,
    motivo TEXT NOT NULL,
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME NOT NULL,
    status ENUM('pendente', 'aprovado', 'recusado') NOT NULL DEFAULT 'pendente',
    tipo_agendamento ENUM('unico', 'fixo') NOT NULL DEFAULT 'unico',
    recorrencia_info JSON,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_recurso) REFERENCES recursos(id)
) ENGINE=InnoDB;

-- Tabela para armazenar matrículas geradas
CREATE TABLE IF NOT EXISTS resolva_aqui_ifpi.matriculas_geradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(12) NOT NULL UNIQUE,
    tipo_usuario ENUM('professor', 'admin') NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usado BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

-- Tabela para registrar uso efetivo dos recursos
CREATE TABLE IF NOT EXISTS check_in_out (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agendamento INT NOT NULL,
    hora_check_in DATETIME NOT NULL,
    hora_check_out DATETIME,
    observacoes TEXT,
    FOREIGN KEY (id_agendamento) REFERENCES agendamentos(id)
) ENGINE=InnoDB;

-- Índices para otimizar consultas
CREATE INDEX idx_agendamentos_datas ON agendamentos(data_hora_inicio, data_hora_fim);
CREATE INDEX idx_agendamentos_status ON agendamentos(status);
CREATE INDEX idx_recursos_tipo ON recursos(tipo_recurso);

-- Criar usuário admin inicial (senha: admin123)
INSERT INTO usuarios (nome_completo, matricula, email, senha_hash, tipo_usuario)
VALUES ('Administrador', 'admin001', 'admin@ifpi.edu.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');