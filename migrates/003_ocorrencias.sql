-- Migrate 003: Sistema de Ocorrências
-- Data: 2026-04-03

-- ============================================
-- Tabela principal de ocorrências
-- ============================================
CREATE TABLE IF NOT EXISTS ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao_fato TEXT NOT NULL,
    data_fato DATE NOT NULL COMMENT 'Data em que o fato ocorreu',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    fase ENUM('nova', 'em_analise', 'recusada', 'homologada') DEFAULT 'nova',
    fase_obs TEXT NULL COMMENT 'Observação sobre a fase (ex: motivo da recusa)',
    created_by INT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fase (fase),
    INDEX idx_data_fato (data_fato),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de vínculo ocorrências <-> unidades
-- ============================================
CREATE TABLE IF NOT EXISTS ocorrencia_unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    unidade_bloco CHAR(1) NULL COMMENT 'Bloco: A, B, C...',
    unidade_numero VARCHAR(10) NOT NULL COMMENT 'Número da unidade: 101, 502...',
    INDEX idx_ocorrencia (ocorrencia_id),
    INDEX idx_unidade (unidade_bloco, unidade_numero),
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de mensagens (estrutura de chat)
-- ============================================
CREATE TABLE IF NOT EXISTS ocorrencia_mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    eh_evidencia BOOLEAN DEFAULT FALSE,
    tipo_anexo ENUM('imagem', 'video', 'audio', 'link', 'documento') NULL,
    anexo_url VARCHAR(500) NULL,
    anexo_nome VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia (ocorrencia_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_evidencia (eh_evidencia),
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de anexos avulsos
-- ============================================
CREATE TABLE IF NOT EXISTS ocorrencia_anexos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('imagem', 'video', 'audio', 'documento', 'link') NOT NULL,
    url VARCHAR(500) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    tamanho_bytes INT NULL,
    mime_type VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia (ocorrencia_id),
    INDEX idx_tipo (tipo),
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela de unidades do condomínio (catálogo)
-- ============================================
CREATE TABLE IF NOT EXISTS unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bloco CHAR(1) NOT NULL DEFAULT 'A',
    numero VARCHAR(10) NOT NULL,
    tipo ENUM('residencial', 'comercial', 'administracao') DEFAULT 'residencial',
    nome_proprietario VARCHAR(255) NULL,
    email_proprietario VARCHAR(255) NULL,
    ativo BOOLEAN DEFAULT TRUE,
    UNIQUE KEY uk_unidade (bloco, numero),
    INDEX idx_bloco (bloco),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Log de mudanças de fase
-- ============================================
CREATE TABLE IF NOT EXISTS ocorrencia_fase_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    fase_anterior VARCHAR(20) NULL,
    fase_nova VARCHAR(20) NOT NULL,
    observacao TEXT NULL,
    usuario_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia (ocorrencia_id),
    INDEX idx_data (created_at),
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
