-- Migration 009 - Sistema de Permissões
-- Data: 2026-04-04
-- Sistema: App Long Miami

START TRANSACTION;

-- =====================================================
-- TABELA DE PERMISSÕES
-- =====================================================

CREATE TABLE IF NOT EXISTS permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT DEFAULT NULL,
    modulo VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_modulo (modulo),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PERMISSÕES DE OCORRÊNCIAS
-- =====================================================

INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES 
    -- Ocorrências
    ('ocorrencia.criar', 'Criar Ocorrência', 'Permite criar novas ocorrências', 'ocorrencia'),
    ('ocorrencia.editar', 'Editar Ocorrência', 'Editar qualquer ocorrência', 'ocorrencia'),
    ('ocorrencia.editar_propria', 'Editar Própria Ocorrência', 'Editar apenas ocorrências criadas', 'ocorrencia'),
    ('ocorrencia.excluir', 'Excluir Ocorrência', 'Excluir ocorrências', 'ocorrencia'),
    ('ocorrencia.listar', 'Listar Ocorrências', 'Listar todas as ocorrências', 'ocorrencia'),
    ('ocorrencia.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de ocorrências', 'ocorrencia'),
    ('ocorrencia.alterar_fase', 'Alterar Fase', 'Alterar fase da ocorrência', 'ocorrencia'),
    ('ocorrencia.homologar', 'Homologar', 'Homologar ocorrências', 'ocorrencia'),
    ('ocorrencia.recusar', 'Recusar', 'Recusar ocorrências', 'ocorrencia'),
    ('ocorrencia.gerar_notificacao', 'Gerar Notificação', 'Gerar notificação a partir da ocorrência', 'ocorrencia'),
    
    -- Unidades da Ocorrência
    ('ocorrencia.unidade.vincular', 'Vincular Unidade', 'Vincular unidades a ocorrências', 'ocorrencia'),
    ('ocorrencia.unidade.remover', 'Remover Unidade', 'Remover unidades de ocorrências', 'ocorrencia'),
    
    -- Mensagens
    ('ocorrencia.mensagem.criar', 'Criar Mensagem', 'Adicionar mensagens', 'ocorrencia'),
    ('ocorrencia.mensagem.editar', 'Editar Mensagem', 'Editar qualquer mensagem', 'ocorrencia'),
    ('ocorrencia.mensagem.editar_propria', 'Editar Própria Mensagem', 'Editar apenas mensagens criadas', 'ocorrencia'),
    ('ocorrencia.mensagem.excluir', 'Excluir Mensagem', 'Excluir qualquer mensagem', 'ocorrencia'),
    ('ocorrencia.mensagem.excluir_propria', 'Excluir Própria Mensagem', 'Excluir apenas mensagens criadas', 'ocorrencia'),
    
    -- Evidências
    ('ocorrencia.evidencia.marcar', 'Marcar Evidência', 'Marcar mensagem como evidência', 'ocorrencia'),
    ('ocorrencia.evidencia.anexar', 'Anexar Evidência', 'Anexar evidência (arquivo)', 'ocorrencia'),
    ('ocorrencia.evidencia.link', 'Adicionar Link', 'Adicionar link como evidência', 'ocorrencia'),
    ('ocorrencia.evidencia.excluir', 'Excluir Evidência', 'Excluir evidência', 'ocorrencia'),
    
    -- Anexos
    ('ocorrencia.anexo.criar', 'Anexar Arquivo', 'Anexar arquivos', 'ocorrencia'),
    ('ocorrencia.anexo.excluir', 'Excluir Anexo', 'Excluir qualquer anexo', 'ocorrencia'),
    ('ocorrencia.anexo.excluir_proprio', 'Excluir Próprio Anexo', 'Excluir apenas anexos criados', 'ocorrencia'),
    ('ocorrencia.link.criar', 'Adicionar Link', 'Adicionar link como anexo', 'ocorrencia'),
    ('ocorrencia.link.excluir', 'Excluir Link', 'Excluir link', 'ocorrencia')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- PERMISSÕES DE NOTIFICAÇÕES
-- =====================================================

INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES 
    ('notificacao.criar', 'Criar Notificação', 'Criar novas notificações', 'notificacao'),
    ('notificacao.editar', 'Editar Notificação', 'Editar qualquer notificação', 'notificacao'),
    ('notificacao.editar_propria', 'Editar Própria Notificação', 'Editar apenas notificações criadas', 'notificacao'),
    ('notificacao.excluir', 'Excluir Notificação', 'Excluir notificações', 'notificacao'),
    ('notificacao.listar', 'Listar Notificações', 'Listar notificações', 'notificacao'),
    ('notificacao.ver', 'Ver Notificação', 'Ver detalhes de notificação', 'notificacao'),
    ('notificacao.lavrar', 'Lavrar Notificação', 'Assinar/lavrar notificações', 'notificacao'),
    ('notificacao.revogar_assinatura', 'Revogar Assinatura', 'Revogar assinatura de notificação', 'notificacao'),
    ('notificacao.registrar_ciencia', 'Registrar Ciência', 'Registrar ciência do morador', 'notificacao'),
    ('notificacao.gerar_pdf', 'Gerar PDF', 'Gerar PDF da notificação', 'notificacao'),
    ('notificacao.marcar_enviada', 'Marcar Enviada', 'Marcar notificação como enviada', 'notificacao'),
    ('notificacao.encerrar', 'Encerrar Notificação', 'Encerrar notificação', 'notificacao'),
    
    -- Imagens
    ('notificacao.imagem.anexar', 'Anexar Imagem', 'Anexar imagem à notificação', 'notificacao'),
    ('notificacao.imagem.sincronizar', 'Sincronizar Imagens', 'Sincronizar imagens da ocorrência', 'notificacao'),
    ('notificacao.imagem.remover', 'Remover Imagem', 'Remover imagem da notificação', 'notificacao'),
    ('notificacao.imagem.ativar', 'Reativar Imagem', 'Reativar imagem removida', 'notificacao'),
    
    -- Conteúdo
    ('notificacao.assunto.editar', 'Editar Assunto', 'Editar assunto da notificação', 'notificacao'),
    ('notificacao.tipo.editar', 'Editar Tipo', 'Editar tipo da notificação', 'notificacao'),
    ('notificacao.fato.adicionar', 'Adicionar Fato', 'Adicionar fato à notificação', 'notificacao'),
    ('notificacao.fato.editar', 'Editar Fato', 'Editar fato da notificação', 'notificacao'),
    ('notificacao.fato.remover', 'Remover Fato', 'Remover fato da notificação', 'notificacao'),
    ('notificacao.artigo.vincular', 'Vincular Artigo', 'Vincular artigo do regimento', 'notificacao'),
    ('notificacao.artigo.desvincular', 'Desvincular Artigo', 'Desvincular artigo', 'notificacao')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- PERMISSÕES DE CONFIGURAÇÕES
-- =====================================================

INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES 
    ('configuracao.acessar', 'Acessar Configurações', 'Acessar página de configurações', 'configuracao'),
    ('configuracao.condominio.editar', 'Editar Condomínio', 'Editar dados do condomínio', 'configuracao'),
    ('configuracao.sindico.gerenciar', 'Gerenciar Síndicos', 'Gerenciar síndicos', 'configuracao'),
    ('configuracao.regimento.editar', 'Editar Regimento', 'Editar regimento interno', 'configuracao')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- PERMISSÕES DE USUÁRIOS
-- =====================================================

INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES 
    ('usuario.listar', 'Listar Usuários', 'Listar usuários', 'usuario'),
    ('usuario.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de usuário', 'usuario'),
    ('usuario.criar', 'Criar Usuário', 'Criar novo usuário', 'usuario'),
    ('usuario.editar', 'Editar Usuário', 'Editar usuário', 'usuario'),
    ('usuario.editar_papeis', 'Editar Papéis', 'Alterar papéis do usuário', 'usuario'),
    ('usuario.editar_grupo', 'Editar Grupo', 'Alterar grupo do usuário', 'usuario'),
    ('usuario.excluir', 'Excluir Usuário', 'Excluir usuário', 'usuario'),
    ('usuario.trocar_senha', 'Trocar Senha', 'Trocar senha de qualquer usuário', 'usuario')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- PERMISSÕES DE GRUPOS
-- =====================================================

INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES 
    ('grupo.listar', 'Listar Grupos', 'Listar grupos', 'grupo'),
    ('grupo.criar', 'Criar Grupo', 'Criar novo grupo', 'grupo'),
    ('grupo.editar', 'Editar Grupo', 'Editar grupo', 'grupo'),
    ('grupo.excluir', 'Excluir Grupo', 'Excluir grupo', 'grupo'),
    ('grupo.gerenciar_permissoes', 'Gerenciar Permissões', 'Associar/desassociar permissões', 'grupo')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- VÍNCULO GRUPOS → PERMISSÕES
-- =====================================================

CREATE TABLE IF NOT EXISTS grupo_permissoes (
    grupo_id INT NOT NULL,
    permissao_id INT NOT NULL,
    PRIMARY KEY (grupo_id, permissao_id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VÍNCULO USUÁRIOS → PERMISSÕES DIRETAS
-- =====================================================

CREATE TABLE IF NOT EXISTS usuario_permissoes (
    usuario_id INT NOT NULL,
    permissao_id INT NOT NULL,
    granted_by INT DEFAULT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, permissao_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONFIGURAR PERMISSÕES DOS GRUPOS PADRÃO
-- =====================================================

-- Grupo: Conselho (assinador + promotor + notificador)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Conselho' AND p.slug IN (
    'ocorrencia.listar', 'ocorrencia.ver_detalhes', 'ocorrencia.alterar_fase',
    'ocorrencia.homologar', 'ocorrencia.recusar',
    'ocorrencia.unidade.vincular', 'ocorrencia.unidade.remover',
    'ocorrencia.mensagem.criar', 'ocorrencia.evidencia.marcar', 'ocorrencia.evidencia.anexar',
    'notificacao.listar', 'notificacao.ver', 'notificacao.lavrar', 'notificacao.gerar_pdf'
);

-- Grupo: Fiscal (protocolar + diligente)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Fiscal' AND p.slug IN (
    'ocorrencia.criar', 'ocorrencia.listar', 'ocorrencia.ver_detalhes',
    'ocorrencia.editar_propria', 'ocorrencia.mensagem.criar', 'ocorrencia.mensagem.editar_propria',
    'ocorrencia.evidencia.anexar', 'ocorrencia.evidencia.link',
    'ocorrencia.anexo.criar', 'ocorrencia.anexo.excluir_proprio',
    'ocorrencia.link.criar',
    'ocorrencia.unidade.vincular'
);

-- Grupo: Operacional (diligente + mensageiro)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Operacional' AND p.slug IN (
    'ocorrencia.listar', 'ocorrencia.ver_detalhes',
    'ocorrencia.mensagem.criar', 'ocorrencia.evidencia.anexar', 'ocorrencia.evidencia.link',
    'ocorrencia.anexo.criar', 'ocorrencia.link.criar',
    'notificacao.listar', 'notificacao.ver', 'notificacao.registrar_ciencia', 'notificacao.gerar_pdf'
);

-- Grupo: Gerencial (todos os papéis operacionais)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Gerencial' AND p.slug IN (
    'ocorrencia.criar', 'ocorrencia.listar', 'ocorrencia.ver_detalhes',
    'ocorrencia.editar_propria', 'ocorrencia.mensagem.criar', 'ocorrencia.mensagem.editar_propria',
    'ocorrencia.evidencia.anexar', 'ocorrencia.evidencia.link', 'ocorrencia.evidencia.marcar',
    'ocorrencia.anexo.criar', 'ocorrencia.anexo.excluir_proprio',
    'ocorrencia.link.criar',
    'ocorrencia.unidade.vincular', 'ocorrencia.unidade.remover',
    'ocorrencia.alterar_fase', 'ocorrencia.homologar', 'ocorrencia.recusar',
    'ocorrencia.gerar_notificacao',
    'notificacao.criar', 'notificacao.listar', 'notificacao.ver', 'notificacao.gerar_pdf',
    'notificacao.lavrar', 'notificacao.marcar_enviada', 'notificacao.registrar_ciencia'
);

-- Grupo: Admin (todas as permissões)
INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin';

COMMIT;
