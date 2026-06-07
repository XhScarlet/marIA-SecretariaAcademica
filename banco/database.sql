CREATE DATABASE IF NOT EXISTS fatec_secretaria
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fatec_secretaria;

-- ==========================
-- TABELA ALUNOS
-- ==========================
CREATE TABLE alunos (
    ra VARCHAR(15) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    curso VARCHAR(50) NOT NULL,
    semestre INT,
    turno VARCHAR(10),
    status ENUM('Ativo','Inativo','Trancado','Formado') DEFAULT 'Ativo',
    email VARCHAR(150) UNIQUE
) ENGINE=InnoDB;

-- Insert tabela: Alunos
INSERT INTO alunos(ra, nome, curso, semestre, turno, status, email) VALUES
('1111111111','Carlos Oliveira','DSM',5,'Noite','Ativo',NULL),
('123456789','Gabriela','DSM',2,'Manhã','Ativo',NULL);

-- ==========================
-- TABELA PROFESSORES/DISCIPLINAS
-- ==========================
CREATE TABLE professores_disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_materia VARCHAR(100) NOT NULL,
    professor VARCHAR(100) NOT NULL,
    email_prof VARCHAR(100),
    ementa_url VARCHAR(255)
) ENGINE=InnoDB;

-- Insert tabela: Professores e Disciplinas
INSERT INTO professores_disciplinas
(nome_materia, professor, email_prof, ementa_url)
VALUES
('Engenharia de Software II','Josenyr S. Rosa', 'josenyr.rosa@fatec.sp.gov.br', 'https://fateczs.edu.br/ementas/es2.pdf'),
('Desenvolvimento de Software Multiplataforma', 'Tiago Silva', 'tiago.silva@fatec.sp.gov.br', 'https://fateczs.edu.br/ementas/dsm.pdf'),
('Banco de Dados Relacional', 'Fernanda Souza', 'fernanda.souza@fatec.sp.gov.br', 'https://fateczs.edu.br/ementas/bd1.pdf');

-- ==========================
-- TABELA USUÁRIOS SECRETARIA
-- ==========================
CREATE TABLE usuarios_secretaria (
    id CHAR(36) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assinatura_img VARCHAR(255)
) ENGINE=InnoDB;

-- Insert tabela: Usuários Secretaria
INSERT INTO usuarios_secretaria (id, nome, usuario, senha, criado_em, assinatura_img)
VALUES 
('9ead217d-a5cf-498b-9cf6-37b8a347aee2', 'Gabriela Cardoso dos Santos', 'admin', '$2y$10$wTnTVDnbUKdZAAQf7j1l0.ixp8esOspMZIQsHFMZa.sIEkUmGMnqm', '2026-05-12 16:44:29', 'assinatura_admin.png');

-- ==========================
-- TABELA PROTOCOLOS
-- ==========================
CREATE TABLE protocolos (
    id_protocolo VARCHAR(20) PRIMARY KEY,
    ra_aluno VARCHAR(15) NOT NULL,
    tipo_servico VARCHAR(50) NOT NULL,
    detalhes TEXT,
    arquivo_comprovante VARCHAR(255),
    status ENUM('Pendente','Em andamento','Concluído','Cancelado')
        DEFAULT 'Pendente',
    data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_protocolo_aluno
        FOREIGN KEY (ra_aluno)
        REFERENCES alunos(ra)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Insert tabela: Protocolos
INSERT INTO protocolos (id_protocolo, ra_aluno, tipo_servico, detalhes, arquivo_comprovante, status, data_abertura)
VALUES
('#20260527-01', '1111111111', 'Atendimento Geral', 'RA 1111111111 solicitou transferência para turno da noite devido a estágio', NULL, 'Pendente', '2026-05-27 20:56:19'),
('#20260527-02', '123456789', 'Atendimento Geral', 'Mudança para o turno da noite devido ao estágio, comprovante recebido.', 'doc_6a175bf42e3bc5.89639916.pdf', 'Concluído', '2026-05-27 21:02:47');

