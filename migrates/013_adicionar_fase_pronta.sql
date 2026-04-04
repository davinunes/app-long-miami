-- Migration: 013_adicionar_fase_pronta
-- Adiciona fase 'pronta' ao ciclo de vida das ocorrências
-- Fase 'pronta' indica que evidências foram coletadas e está aguardando homologação

START TRANSACTION;

-- Adicionar 'pronta' como opção na fase (se não existir)
-- Isso pode precisar de ajuste dependendo do tipo da coluna

-- Atualizar descrição da fase se for ENUM
ALTER TABLE ocorrencias MODIFY COLUMN fase ENUM('nova', 'em_analise', 'pronta', 'recusada', 'homologada') DEFAULT 'nova';

-- Criar índice para facilitar consultas por fase
CREATE INDEX IF NOT EXISTS idx_ocorrencias_fase ON ocorrencias(fase);

COMMIT;

-- info
-- Nova fase 'pronta' adicionada ao ciclo de vida
-- Fluxo: nova → em_analise → pronta → homologada
--         ↘_____________↗      ↘______↗
--              (recusada)       (recusada)
