-- Migration 006: Fix notifications with missing ocorrencia_id
-- Date: 2026-04-04
-- Objetivo: Atualizar ocorrencia_id em notificacoes que têm vínculo em ocorrencia_notificacoes mas o campo está NULL

UPDATE notificacoes n
INNER JOIN ocorrencia_notificacoes oc ON oc.notificacao_id = n.id
SET n.ocorrencia_id = oc.ocorrencia_id
WHERE n.ocorrencia_id IS NULL AND oc.tipo_vinculo = 'gerada'
LIMIT 100;
