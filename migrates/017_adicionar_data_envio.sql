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
    'SELECT "Column already exists"');
    
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
