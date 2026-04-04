-- Migration 002 - Sistema de Grupos e Papéis (Roles)
-- Data: 2026-04-03
-- Sistema: App Long Miami

-- =====================================================
-- PAPÉIS DO SISTEMA
-- =====================================================

CREATE TABLE IF NOT EXISTS papeles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir papéis do sistema
INSERT INTO papeles (slug, nome, descricao) VALUES 
    ('protocolar', 'Protocolar', 'Cadastra ocorrências no sistema'),
    ('diligente', 'Diligente', 'Adiciona arquivos e comentários às ocorrências'),
    ('notificador', 'Notificador', 'Gera notificações e vincula ocorrências homologadas'),
    ('promotor', 'Promotor', 'Homologa ocorrências e adiciona evidências'),
    ('assinador', 'Assinador', 'Lavra notificações (aprova para envio)'),
    ('despachante', 'Despachante', 'Acessa notificações lavradas para envio'),
    ('mensageiro', 'Mensageiro', 'Registra ciência de notificações'),
    ('admin', 'Administrador', 'Administrador total do sistema'),
    ('dev', 'Desenvolvedor', 'Modo deus - acesso total sem restrições')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- GRUPOS DE USUÁRIOS
-- =====================================================

CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT DEFAULT NULL,
    papel_principal VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir grupos padrão
INSERT INTO grupos (nome, descricao) VALUES 
    ('Conselho', 'Membros do conselho fiscal e administrativo'),
    ('Fiscal', 'Equipe de fiscalização'),
    ('Operacional', 'Equipe operacional - CFTV, portaria'),
    ('Gerencial', 'Gestores - síndico, subsíndico'),
    ('Admin', 'Administradores do sistema')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- =====================================================
-- VÍNCULO PAPÉIS POR GRUPO
-- =====================================================

CREATE TABLE IF NOT EXISTS grupo_papeis (
    grupo_id INT NOT NULL,
    papel_slug VARCHAR(50) NOT NULL,
    PRIMARY KEY (grupo_id, papel_slug),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (papel_slug) REFERENCES papeles(slug) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Definir papéis por grupo padrão
INSERT INTO grupo_papeis (grupo_id, papel_slug) VALUES 
    -- Conselho
    ((SELECT id FROM grupos WHERE nome = 'Conselho'), 'assinador'),
    ((SELECT id FROM grupos WHERE nome = 'Conselho'), 'promotor'),
    ((SELECT id FROM grupos WHERE nome = 'Conselho'), 'notificador'),
    -- Fiscal
    ((SELECT id FROM grupos WHERE nome = 'Fiscal'), 'protocolar'),
    ((SELECT id FROM grupos WHERE nome = 'Fiscal'), 'diligente'),
    -- Operacional
    ((SELECT id FROM grupos WHERE nome = 'Operacional'), 'diligente'),
    ((SELECT id FROM grupos WHERE nome = 'Operacional'), 'mensageiro'),
    -- Gerencial
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'assinador'),
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'promotor'),
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'notificador'),
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'despachante'),
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'protocolar'),
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'diligente'),
    ((SELECT id FROM grupos WHERE nome = 'Gerencial'), 'mensageiro'),
    -- Admin
    ((SELECT id FROM grupos WHERE nome = 'Admin'), 'admin')
ON DUPLICATE KEY UPDATE papel_slug = VALUES(papel_slug);

-- =====================================================
-- VÍNCULO USUÁRIOS-GRUPOS
-- =====================================================

CREATE TABLE IF NOT EXISTS usuario_grupos (
    usuario_id INT NOT NULL,
    grupo_id INT NOT NULL,
    PRIMARY KEY (usuario_id, grupo_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAPÉIS DIRETOS DO USUÁRIO (além dos grupos)
-- =====================================================

CREATE TABLE IF NOT EXISTS usuario_papeis (
    usuario_id INT NOT NULL,
    papel_slug VARCHAR(50) NOT NULL,
    PRIMARY KEY (usuario_id, papel_slug),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (papel_slug) REFERENCES papeles(slug) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ATUALIZAR TABELA USUÁRIOS
-- =====================================================

-- Adicionar coluna grupo_principal se não existir
SET @column_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios' 
    AND COLUMN_NAME = 'grupo_principal_id'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE usuarios ADD COLUMN grupo_principal_id INT DEFAULT NULL AFTER role, ADD FOREIGN KEY (grupo_principal_id) REFERENCES grupos(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ATUALIZAR USUÁRIO ADMIN PADRÃO
-- =====================================================

-- Vincular admin ao grupo Admin
INSERT INTO usuario_grupos (usuario_id, grupo_id)
SELECT u.id, g.id FROM usuarios u, grupos g 
WHERE u.email = 'admin@seusistema.com' AND g.nome = 'Admin'
ON DUPLICATE KEY UPDATE grupo_id = VALUES(grupo_id);

-- Adicionar papel admin direto ao admin
INSERT INTO usuario_papeis (usuario_id, papel_slug)
SELECT u.id, 'admin' FROM usuarios u WHERE u.email = 'admin@seusistema.com'
ON DUPLICATE KEY UPDATE papel_slug = VALUES(papel_slug);
