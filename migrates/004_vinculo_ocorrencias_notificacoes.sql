-- Migration 004: Vincular Ocorrências a Notificações
-- Data: 2026-04-03
-- Objetivo: Criar relação entre ocorrências e notificações

-- ============================================
-- Adicionar vínculo de ocorrência na notificação
-- ============================================
ALTER TABLE notificacoes 
ADD COLUMN ocorrencia_id INT NULL 
AFTER status_id;

ALTER TABLE notificacoes 
ADD CONSTRAINT fk_notificacao_ocorrencia 
FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE SET NULL;

ALTER TABLE notificacoes 
ADD INDEX idx_ocorrencia_id (ocorrencia_id);

-- ============================================
-- Adicionar vínculo de notificação na ocorrência
-- (relação bidirecional para consultas rápidas)
-- ============================================
ALTER TABLE ocorrencias 
ADD COLUMN notificacao_id INT NULL 
AFTER fase_obs;

ALTER TABLE ocorrencias 
ADD CONSTRAINT fk_ocorrencia_notificacao 
FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE SET NULL;

ALTER TABLE ocorrencias 
ADD INDEX idx_notificacao_id (notificacao_id);

-- ============================================
-- Tabela de vínculo extra: múltiplas notificações
-- por ocorrência (caso precise de versões)
-- ============================================
CREATE TABLE IF NOT EXISTS ocorrencia_notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    notificacao_id INT NOT NULL,
    tipo_vinculo ENUM('gerada', 'vinculada', 'derivada') DEFAULT 'gerada',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_vinculo (ocorrencia_id, notificacao_id),
    INDEX idx_ocorrencia (ocorrencia_id),
    INDEX idx_notificacao (notificacao_id),
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE,
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
