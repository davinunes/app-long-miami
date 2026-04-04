-- Migration 012 - Corrigir permissões e grupos admin
-- Data: 2026-04-04
-- Sistema: App Long Miami

-- 1. Criar grupo Admin se não existir
INSERT IGNORE INTO grupos (id, nome, descricao) VALUES (1, 'Admin', 'Administradores do sistema');

-- 2. Garantir que todos os usuários com role='admin' ou 'dev' estejam no grupo Admin
INSERT IGNORE INTO usuario_grupos (usuario_id, grupo_id)
SELECT id, 1 FROM usuarios WHERE role IN ('admin', 'dev');

-- 3. Atribuir TODAS as permissões ao grupo Admin
-- Primeiro, limpar permissões atuais do Admin
DELETE FROM grupo_permissoes WHERE grupo_id = 1;

-- Adicionar todas as permissões ao Admin
INSERT INTO grupo_permissoes (grupo_id, permissao_id)
SELECT 1, id FROM permissoes;

-- 4. Verificar se usuário admin@seusistema.com existe e está ativo
-- UPDATE usuarios SET role = 'admin' WHERE email = 'admin@seusistema.com';

SELECT 'Migration 012 executada com sucesso!' as status;
SELECT 'Grupos existentes:' as info;
SELECT * FROM grupos;
SELECT 'Usuários com role admin/dev:' as info;
SELECT id, nome, email, role FROM usuarios WHERE role IN ('admin', 'dev');
SELECT 'Permissões do grupo Admin:' as info;
SELECT COUNT(*) as total_permissoes_admin FROM grupo_permissoes WHERE grupo_id = 1;
