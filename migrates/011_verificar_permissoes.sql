-- Migration 011 - Verificar e corrigir permissões
-- Data: 2026-04-04
-- Sistema: App Long Miami

-- Verificar se existe grupo Admin
SELECT 'Grupos:' as info;
SELECT * FROM grupos;

-- Verificar se existem permissões
SELECT 'Permissões:' as info;
SELECT COUNT(*) as total_permissoes FROM permissoes;

-- Verificar se grupo Admin tem permissões
SELECT 'Permissões do Admin:' as info;
SELECT COUNT(*) as total FROM grupo_permissoes gp 
JOIN grupos g ON gp.grupo_id = g.id 
WHERE g.nome = 'Admin';

-- Se não tiver, inserir
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin';
