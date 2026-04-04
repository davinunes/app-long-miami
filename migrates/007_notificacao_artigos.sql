-- Migration 007: Tabela de Artigos Vinculados a Notificações
-- Date: 2026-04-04
-- Objetivo: Criar tabela para rastrear artigos do regimento/lei vinculados às notificações

CREATE TABLE IF NOT EXISTS notificacao_artigos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_id INT NOT NULL,
    artigo_notacao VARCHAR(50) NOT NULL,
    artigo_texto TEXT NULL,
    tipo ENUM('regimento', 'lei', 'outro') DEFAULT 'regimento',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_notificacao_artigo (notificacao_id, artigo_notacao),
    INDEX idx_notificacao (notificacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar artigos existentes do texto de fundamentacao_legal (opcional, para futuras referências)
-- Este script não migra automaticamente pois os artigos já estão no texto
