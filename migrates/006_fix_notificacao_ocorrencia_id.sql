-- Migration 006: Fix notifications with missing ocorrencia_id
-- Date: 2026-04-04
-- Objetivo: Atualizar ocorrencia_id em notificacoes que têm vínculo em ocorrencia_notificacoes mas o campo está NULL

-- Adicionar coluna inactive em ocorrencia_anexos se não existir
ALTER TABLE ocorrencia_anexos ADD COLUMN inactive TINYINT(1) DEFAULT 0 AFTER mime_type;

UPDATE notificacoes n
INNER JOIN ocorrencia_notificacoes oc ON oc.notificacao_id = n.id
SET n.ocorrencia_id = oc.ocorrencia_id
WHERE n.ocorrencia_id IS NULL AND oc.tipo_vinculo = 'gerada';
