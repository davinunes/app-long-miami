-- Migration 010 - Corrigir permissões do Admin
-- Data: 2026-04-04
-- Sistema: App Long Miami

-- Garantir que o grupo Admin tenha todas as permissões
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin';

-- Garantir que usuários com role='dev' ou role='admin' no campo usuarios.role
-- tenham todas as permissões via grupo_permissoes
-- (Isso já é feito no login via auth.php, mas aqui garantimos no banco)
