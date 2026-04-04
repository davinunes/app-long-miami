-- Migration: 013_adicionar_fase_pronta
-- Adiciona fase 'pronta' ao ciclo de vida das ocorrências
-- Fase 'pronta' indica que evidências foram coletadas e está aguardando homologação

START TRANSACTION;

-- Atualizar coluna fase para incluir 'pronta'
-- Primeiro, tenta modificar; se falhar, a coluna já tem 'pronta'
ALTER TABLE ocorrencias MODIFY COLUMN fase ENUM('nova', 'em_analise', 'pronta', 'recusada', 'homologada') DEFAULT 'nova';

-- Criar índice para facilitar consultas por fase (MySQL não suporta IF NOT EXISTS)
-- Verifica se o índice já existe antes de criar
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'ocorrencias' 
               AND index_name = 'idx_ocorrencias_fase');
SET @sqlstmt := IF(@exist > 0, 'SELECT 1', 'CREATE INDEX idx_ocorrencias_fase ON ocorrencias(fase)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

-- info
-- Nova fase 'pronta' adicionada ao ciclo de vida
-- Fluxo: nova → em_analise → pronta → homologada
--         ↘_____________↗      ↘______↗
--              (recusada)       (recusada)
