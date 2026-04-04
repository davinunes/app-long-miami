-- Migration 005: Vinculação de Evidências entre Ocorrências e Notificações
-- Data: 2026-04-04
-- Objetivo: Suportar vinculação de evidências fotográficas de ocorrências em notificações

-- ============================================
-- 1. Adicionar campo de soft delete na tabela de imagens
-- ============================================
ALTER TABLE notificacao_imagens 
ADD COLUMN inactive TINYINT(1) DEFAULT 0 AFTER caminho_arquivo;

ALTER TABLE notificacao_imagens 
ADD COLUMN deleted_at TIMESTAMP NULL AFTER inactive;

-- ============================================
-- 2. Adicionar campos para rastrear origem da imagem
-- ============================================
ALTER TABLE notificacao_imagens 
ADD COLUMN ocorrencia_id INT NULL AFTER notificacao_id,
ADD COLUMN anexo_ocorrencia_id INT NULL AFTER ocorrencia_id;

-- Adicionar índices
ALTER TABLE notificacao_imagens 
ADD INDEX idx_ocorrencia_origem (ocorrencia_id, anexo_ocorrencia_id),
ADD INDEX idx_inactive (inactive);

-- ============================================
-- 3. Adicionar campo de observação na tabela de vínculo
-- ============================================
ALTER TABLE ocorrencia_notificacoes 
ADD COLUMN observacao TEXT NULL AFTER tipo_vinculo;

-- ============================================
-- 4. Criar tabela para rastrear evidências compartilhadas
-- ============================================
CREATE TABLE IF NOT EXISTS evidencia_compartilhada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_anexo_id INT NOT NULL,
    notificacao_id INT NOT NULL,
    inactive TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ocorrencia_notificacao (ocorrencia_anexo_id, notificacao_id),
    INDEX idx_notificacao (notificacao_id),
    INDEX idx_ocorrencia_anexo (ocorrencia_anexo_id),
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. Migrar evidências existentes (se houver ocorrencia_id na notificação)
-- ============================================
-- Este script migration não migra dados existentes automaticamente
-- para não perder informações. A lógica de migração será feita na API.
