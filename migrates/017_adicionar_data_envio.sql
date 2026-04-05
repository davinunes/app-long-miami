-- Migration 017 - Adicionar campo data_envio
-- Data: 2026-04-05
-- Sistema: App Long Miami

START TRANSACTION;

-- Adicionar campo data_envio se não existir
SET @column_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'notificacoes' 
    AND COLUMN_NAME = 'data_envio'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE notificacoes ADD COLUMN data_envio DATETIME NULL AFTER data_lavratura',
    'SELECT "Column already exists" as result');
    
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Inserir permissão editar_datas se não existir
INSERT IGNORE INTO permissoes (slug, nome, descricao, modulo) 
VALUES ('notificacao.editar_datas', 'Editar Datas', 'Permite editar datas de envio e ciência', 'notificacao');

-- Linkar permissão ao Admin
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id) 
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin' AND p.slug = 'notificacao.editar_datas';

COMMIT;
