-- Migration: 014_adicionar_permissoes_fase
-- Adiciona permissões granulares para ações de fase das ocorrências

START TRANSACTION;

-- Inserir novas permissões de fase
INSERT IGNORE INTO permissoes (slug, nome, descricao, modulo) VALUES
('ocorrencia.colocar_em_analise', 'Colocar em Análise', 'Permite mover ocorrência de Nova para Em Análise', 'ocorrencia'),
('ocorrencia.marcar_pronta', 'Marcar como Pronta', 'Permite marcar ocorrência como pronta para homologação', 'ocorrencia'),
('ocorrencia.homologar', 'Homologar Ocorrência', 'Permite homologar uma ocorrência', 'ocorrencia'),
('ocorrencia.recusar', 'Recusar Ocorrência', 'Permite recusar uma ocorrência', 'ocorrencia'),
('ocorrencia.retornar_analise', 'Retornar para Análise', 'Permite retornar uma ocorrência recusada para análise', 'ocorrencia');

COMMIT;

-- info
-- Permissões de fase adicionadas:
-- - ocorrencia.colocar_em_analise
-- - ocorrencia.marcar_pronta  
-- - ocorrencia.homologar
-- - ocorrencia.recusar
-- - ocorrencia.retornar_analise
