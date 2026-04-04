-- Migration 015 - Ciclo de Vida das Notificações
-- Data: 2026-04-04 (v2)
-- Sistema: App Long Miami

-- 1. Novas permissões para a fase de recurso e controle de fluxo
INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES 
    ('notificacao.registrar_recurso', 'Registrar Recurso', 'Registrar a interposição de recurso pelo morador', 'notificacao'),
    ('notificacao.julgar_recurso', 'Julgar Recurso', 'Deferir ou indeferir recursos de notificação', 'notificacao'),
    ('notificacao.marcar_cobranca', 'Marcar para Cobrança', 'Disponibilizar a notificação para lançamento em boteleto', 'notificacao'),
    ('notificacao.encerrar', 'Encerrar Notificação', 'Marcar que a cobrança já foi lançada no boleto', 'notificacao'),
    ('notificacao.reabrir', 'Reabrir Notificação', 'Reverter o status encerrada ou cobrança para a fase anterior', 'notificacao'),
    ('notificacao.lavrar', 'Lavrar Notificação', 'Assinar a notificação para que se torne um documento oficial', 'notificacao'),
    ('notificacao.marcar_enviada', 'Marcar como Enviada', 'Indicar que a notificação foi entregue ao destinatário', 'notificacao'),
    ('notificacao.registrar_ciencia', 'Registrar Ciência', 'Registrar que o morador tomou ciência da notificação', 'notificacao'),
    ('notificacao.alterar_fase', 'Alterar Fase', 'Permissão geral para transições de fase da notificação', 'notificacao')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- 3. Novos campos na tabela notificacoes para suportar o ciclo de vida (Idempotente via Procedure)
DELIMITER //

CREATE PROCEDURE ADD_COLUMNS_NOTIFICACAO()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='data_lavratura' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN data_lavratura DATETIME DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='lavrada_por' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN lavrada_por INT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='data_ciencia' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN data_ciencia DATETIME DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='ciencia_por' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN ciencia_por VARCHAR(100) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='tem_recurso' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN tem_recurso BOOLEAN DEFAULT FALSE;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='data_recurso' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN data_recurso DATETIME DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='prazo_recurso_expira' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN prazo_recurso_expira DATE DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='recurso_texto' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN recurso_texto TEXT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='recurso_status' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN recurso_status VARCHAR(20) DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='encerrada' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN encerrada BOOLEAN DEFAULT FALSE;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='data_encerramento' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN data_encerramento DATETIME DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacoes' AND COLUMN_NAME='motivo_encerramento' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD COLUMN motivo_encerramento VARCHAR(50) DEFAULT NULL;
    END IF;

    -- Tentar adicionar constraint apenas se não existir (ignora erro se já existir no catch do script, ou use IF NOT EXISTS)
    -- Em algumas versões do MariaDB suporta CONSTRAINT IF NOT EXISTS, se não, colocamos na procedure também
    IF NOT EXISTS (SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME='fk_lavrada_por' AND TABLE_NAME='notificacoes' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacoes ADD CONSTRAINT fk_lavrada_por FOREIGN KEY (lavrada_por) REFERENCES usuarios(id);
    END IF;
    
    -- Ajustar slug da tabela notificacao_status
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME='notificacao_status' AND COLUMN_NAME='slug' AND TABLE_SCHEMA=DATABASE()) THEN
        ALTER TABLE notificacao_status ADD COLUMN slug VARCHAR(50) UNIQUE DEFAULT NULL;
    END IF;
END //

DELIMITER ;

CALL ADD_COLUMNS_NOTIFICACAO();
DROP PROCEDURE ADD_COLUMNS_NOTIFICACAO;

-- 4. Criar tabela de histórico de fases da notificação (Timeline)
CREATE TABLE IF NOT EXISTS notificacao_fase_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    fase_anterior VARCHAR(50) DEFAULT NULL,
    fase_nova VARCHAR(50) NOT NULL,
    observacao TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_notificacao_fase (notificacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Atualizar os status na tabela notificacao_status para o padrão do ciclo de vida
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE notificacao_status;
INSERT INTO notificacao_status (id, nome, slug) VALUES 
    (1, 'Rascunho', 'rascunho'),
    (2, 'Lavrada', 'lavrada'),
    (3, 'Enviada', 'enviada'),
    (4, 'Ciente', 'ciente'),
    (5, 'Em Recurso', 'em_recurso'),
    (6, 'Recurso Deferido', 'recurso_deferido'),
    (7, 'Recurso Indeferido', 'recurso_indeferido'),
    (8, 'Em Cobrança', 'cobranca'),
    (9, 'Encerrada', 'encerrada');
SET FOREIGN_KEY_CHECKS = 1;

-- 6. Documentação das Permissões (Consolidada)
-- notificacao.lavrar: Transforma rascunho em documento oficial.
-- notificacao.marcar_enviada: Próximo passo após lavratura.
-- notificacao.registrar_ciencia: Inicia o prazo de recurso (7 dias de carência opcional).
-- notificacao.registrar_recurso: Se o morador interpor em até 7 dias da ciência.
-- notificacao.julgar_recurso: Deferimento ou Indeferimento.
-- notificacao.marcar_cobranca: Disponível 7 dias após ciência (se sem recurso) ou imediatamente após indeferimento.
-- notificacao.encerrar: Lançamento efetuado no boleto.
-- notificacao.reabrir: Reverte encerrada/cobranca.
