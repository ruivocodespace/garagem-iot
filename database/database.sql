-- =====================================================
-- BANCO DE DADOS: GARAGEM_IOT
-- =====================================================

CREATE DATABASE IF NOT EXISTS garagem_iot
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE garagem_iot;

-- =====================================================
-- TABELA: USUARIOS
-- =====================================================

CREATE TABLE usuarios (
id_usuario INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) NOT NULL,
email VARCHAR(150) NOT NULL UNIQUE,
senha VARCHAR(255) NOT NULL,
status TINYINT(1) NOT NULL DEFAULT 1,
data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: MOTORISTAS
-- =====================================================

CREATE TABLE motoristas (
id_motorista INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) NOT NULL,
cpf VARCHAR(14) NOT NULL UNIQUE,
matricula VARCHAR(30) UNIQUE,
telefone VARCHAR(20),
status TINYINT(1) NOT NULL DEFAULT 1,
data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: CREDENCIAIS_RFID
-- =====================================================

CREATE TABLE credenciais_rfid (
id_credencial INT AUTO_INCREMENT PRIMARY KEY,
id_motorista INT NOT NULL UNIQUE,
uid VARCHAR(30) NOT NULL UNIQUE,
status TINYINT(1) NOT NULL DEFAULT 1,
data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

CONSTRAINT fk_credencial_motorista
FOREIGN KEY (id_motorista)
REFERENCES motoristas(id_motorista)
ON UPDATE CASCADE
ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: ONIBUS
-- =====================================================

CREATE TABLE onibus (
id_onibus INT AUTO_INCREMENT PRIMARY KEY,
numero_frota VARCHAR(20) NOT NULL UNIQUE,
placa VARCHAR(10) UNIQUE,
modelo VARCHAR(80),
status ENUM(
'ATIVO',
'MANUTENCAO',
'INATIVO'
) NOT NULL DEFAULT 'ATIVO',
data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: BOXES
-- =====================================================

CREATE TABLE boxes (
id_box INT AUTO_INCREMENT PRIMARY KEY,
id_onibus INT NOT NULL UNIQUE,
nome VARCHAR(50) NOT NULL UNIQUE,
status ENUM(
'LIVRE',
'OCUPADO',
'MANUTENCAO',
'INTERDITADO'
) NOT NULL DEFAULT 'LIVRE',
ativo TINYINT(1) NOT NULL DEFAULT 1,

CONSTRAINT fk_box_onibus
FOREIGN KEY (id_onibus)
REFERENCES onibus(id_onibus)
ON UPDATE CASCADE
ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: PERMISSOES
-- =====================================================

CREATE TABLE permissoes (
id_permissao INT AUTO_INCREMENT PRIMARY KEY,
id_motorista INT NOT NULL,
id_box INT NOT NULL,
data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

CONSTRAINT uk_motorista_box
UNIQUE (id_motorista, id_box),

CONSTRAINT fk_permissao_motorista
FOREIGN KEY (id_motorista)
REFERENCES motoristas(id_motorista)
ON UPDATE CASCADE
ON DELETE RESTRICT,

CONSTRAINT fk_permissao_box
FOREIGN KEY (id_box)
REFERENCES boxes(id_box)
ON UPDATE CASCADE
ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: OCUPACOES
-- =====================================================

CREATE TABLE ocupacoes (
id_ocupacao INT AUTO_INCREMENT PRIMARY KEY,
id_motorista INT NOT NULL,
id_box INT NOT NULL,
id_onibus INT NOT NULL,
entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
saida DATETIME DEFAULT NULL,
status ENUM(
'ATIVA',
'FINALIZADA'
) NOT NULL DEFAULT 'ATIVA',

INDEX idx_ocupacao_motorista_status (id_motorista, status),
INDEX idx_ocupacao_box_status (id_box, status),

CONSTRAINT fk_ocupacao_motorista
FOREIGN KEY (id_motorista)
REFERENCES motoristas(id_motorista)
ON UPDATE CASCADE
ON DELETE RESTRICT,

CONSTRAINT fk_ocupacao_box
FOREIGN KEY (id_box)
REFERENCES boxes(id_box)
ON UPDATE CASCADE
ON DELETE RESTRICT,

CONSTRAINT fk_ocupacao_onibus
FOREIGN KEY (id_onibus)
REFERENCES onibus(id_onibus)
ON UPDATE CASCADE
ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- TABELA: ACESSOS
-- =====================================================

CREATE TABLE acessos (
id_acesso INT AUTO_INCREMENT PRIMARY KEY,
id_motorista INT DEFAULT NULL,
id_box INT DEFAULT NULL,
id_ocupacao INT DEFAULT NULL,
uid_informado VARCHAR(30) NOT NULL,
request_id VARCHAR(50) NOT NULL UNIQUE,
operacao ENUM(
'ENTRADA',
'SAIDA',
'INDEFINIDA'
) NOT NULL DEFAULT 'INDEFINIDA',
resultado ENUM(
'AUTORIZADO',
'NEGADO'
) NOT NULL,
motivo VARCHAR(150) DEFAULT NULL,
data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

INDEX idx_acesso_data (data_hora),
INDEX idx_acesso_motorista (id_motorista),
INDEX idx_acesso_box (id_box),

CONSTRAINT fk_acesso_motorista
FOREIGN KEY (id_motorista)
REFERENCES motoristas(id_motorista)
ON UPDATE CASCADE
ON DELETE RESTRICT,

CONSTRAINT fk_acesso_box
FOREIGN KEY (id_box)
REFERENCES boxes(id_box)
ON UPDATE CASCADE
ON DELETE RESTRICT,

CONSTRAINT fk_acesso_ocupacao
FOREIGN KEY (id_ocupacao)
REFERENCES ocupacoes(id_ocupacao)
ON UPDATE CASCADE
ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- CADASTRO INICIAL DOS QUATRO ONIBUS
-- =====================================================

INSERT INTO onibus (numero_frota, placa, modelo, status) VALUES
('L1', 'AVG2I20', 'MARCOPOLO', 'ATIVO'),
('L2', 'BNT6C10', 'MARCOPOLO', 'ATIVO'),
('L3', 'RNM12G13','MARCOPOLO', 'ATIVO'),
('Centro', 'JKO2B30', 'VOLVO', 'ATIVO');

-- =====================================================
-- CADASTRO INICIAL DOS QUATRO BOXES
-- =====================================================

INSERT INTO boxes (id_onibus, nome, status) VALUES
(1, 'BOX 01', 'LIVRE'),
(2, 'BOX 02', 'LIVRE'),
(3, 'BOX 03', 'LIVRE'),
(4, 'BOX 04', 'LIVRE');