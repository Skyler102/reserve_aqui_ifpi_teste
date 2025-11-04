-- Garante que o banco de dados seja recriado do zero a cada execução
DROP DATABASE IF EXISTS resolva_aqui_ifpi;

-- Criação do banco de dados com o conjunto de caracteres correto para português
CREATE DATABASE resolva_aqui_ifpi
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Seleciona o banco de dados para uso
USE resolva_aqui_ifpi;

-- -----------------------------------------------------
-- Tabela: matriculas_geradas
-- Armazena matrículas pré-aprovadas para cadastro de professores e admins.
-- -----------------------------------------------------
CREATE TABLE matriculas_geradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(12) NOT NULL UNIQUE,
    tipo_usuario ENUM('professor', 'admin') NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: usuarios
-- Armazena os dados dos usuários do sistema (professores e administradores).
-- -----------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    matricula VARCHAR(12) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('professor', 'admin') NOT NULL DEFAULT 'professor',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: recursos
-- Armazena os locais físicos que podem ser agendados (laboratórios, quadras, etc).
-- -----------------------------------------------------
CREATE TABLE recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo_recurso ENUM('laboratorio', 'quadra', 'auditorio', 'sala') NOT NULL,
    localizacao VARCHAR(100),
    capacidade INT,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: disciplinas (NOVA)
-- Armazena as disciplinas/matérias que serão lecionadas.
-- -----------------------------------------------------
CREATE TABLE disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: turmas (NOVA)
-- Armazena as turmas de alunos.
-- -----------------------------------------------------
CREATE TABLE turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: agendamentos (REFATORADA E CORRIGIDA)
-- Tabela principal que conecta usuários, recursos, disciplinas e turmas em um horário.
-- -----------------------------------------------------
CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_recurso INT NOT NULL,
    id_disciplina INT NULL,  -- Pode ser nulo para eventos não acadêmicos
    id_turma INT NULL,       -- Pode ser nulo para eventos não acadêmicos
    motivo TEXT,             -- Motivo agora é opcional, pois a disciplina/turma já descreve o propósito
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME NOT NULL,
    status ENUM('pendente', 'aprovado', 'recusado', 'cancelado') NOT NULL DEFAULT 'pendente',
    tipo_agendamento ENUM('unico', 'fixo') NOT NULL DEFAULT 'unico',
    
    -- ⭐️ MUDANÇA AQUI: Coluna correta para o accordion funcionar
    grupo_recorrencia_id VARCHAR(50) NULL DEFAULT NULL, 
    
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_recurso) REFERENCES recursos(id),
    FOREIGN KEY (id_disciplina) REFERENCES disciplinas(id),
    FOREIGN KEY (id_turma) REFERENCES turmas(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabela: check_in_out
-- Registra o uso efetivo dos recursos agendados.
-- -----------------------------------------------------
CREATE TABLE check_in_out (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agendamento INT NOT NULL,
    hora_check_in DATETIME NOT NULL,
    hora_check_out DATETIME,
    observacoes TEXT,
    FOREIGN KEY (id_agendamento) REFERENCES agendamentos(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- ÍNDICES PARA OTIMIZAÇÃO DE CONSULTAS
-- -----------------------------------------------------
CREATE INDEX idx_agendamentos_datas ON agendamentos(data_hora_inicio, data_hora_fim);
CREATE INDEX idx_agendamentos_status ON agendamentos(status);
CREATE INDEX idx_recursos_tipo ON recursos(tipo_recurso);
-- ⭐️ MUDANÇA AQUI: Adiciona índice na nova coluna para performance
CREATE INDEX idx_agendamentos_grupo_rec ON agendamentos(grupo_recorrencia_id);

-- -----------------------------------------------------
-- DADOS INICIAIS
-- -----------------------------------------------------
-- Cria um usuário administrador padrão para o primeiro acesso ao sistema.
-- Senha: admin123
INSERT INTO usuarios (nome_completo, matricula, email, senha_hash, tipo_usuario)
VALUES ('Administrador Padrão', 'admin001', 'admin@ifpi.edu.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
