-- Migration: 015_vincular_permissoes_fase_grupos
-- Vincula as permissões de fase aos grupos corretos

START TRANSACTION;

-- Associar permissões de fase ao Grupo Conselho (para promotors e assinadores)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id 
FROM grupos g, permissoes p 
WHERE g.nome = 'Conselho' 
AND p.slug IN ('ocorrencia.colocar_em_analise', 'ocorrencia.marcar_pronta', 'ocorrencia.retornar_analise');

-- Associar permissões de fase ao Grupo Gerencial
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id 
FROM grupos g, permissoes p 
WHERE g.nome = 'Gerencial' 
AND p.slug IN ('ocorrencia.colocar_em_analise', 'ocorrencia.marcar_pronta', 'ocorrencia.retornar_analise');

-- Associar permissões de fase ao Grupo Admin
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id 
FROM grupos g, permissoes p 
WHERE g.nome = 'Admin' 
AND p.slug IN ('ocorrencia.colocar_em_analise', 'ocorrencia.marcar_pronta');

-- Garantir que Admin tenha todas as permissões de fase (já deve ter por causa da config anterior)
-- Apenas para usuários admin individuais (sem grupo)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id 
FROM grupos g, permissoes p 
WHERE g.nome = 'Admin' 
AND p.slug = 'ocorrencia.retornar_analise';

COMMIT;

-- info
-- Permissões de fase vinculadas aos grupos:
-- - Conselho: colocar_em_analise, marcar_pronta, retornar_analise
-- - Gerencial: colocar_em_analise, marcar_pronta, retornar_analise
-- - Admin: todas as permissões de fase
