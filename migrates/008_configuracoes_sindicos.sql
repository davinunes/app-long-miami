-- Migration 008: Tabelas de Configurações e Síndicos
-- Date: 2026-04-04
-- Objetivo: Criar tabelas para configurações gerais e gestão de síndicos

-- ============================================
-- Tabela de configurações gerais
-- ============================================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NULL,
    tipo ENUM('string', 'text', 'number', 'boolean', 'json', 'file') DEFAULT 'string',
    descricao VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações padrão
INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
    ('condominio_nome', 'Condomínio Long Miami Beach', 'string', 'Nome do condomínio'),
    ('condominio_cnpj', '', 'string', 'CNPJ do condomínio'),
    ('condominio_logo', '', 'file', 'Logo do condomínio'),
    ('url_recurso_default', 'recurso.conselhomiamibeach.com.br', 'string', 'URL padrão para recursos'),
    ('regimento_json', '', 'file', 'Arquivo JSON do regimento interno')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- ============================================
-- Tabela de síndicos
-- ============================================
CREATE TABLE IF NOT EXISTS sindicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NULL,
    email VARCHAR(255) NULL,
    telefone VARCHAR(20) NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    ativo TINYINT(1) DEFAULT 1,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_data_inicio (data_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
