-- Migration Zero - Schema inicial do banco de dados
-- Sistema: App Long Miami - Gerador de Notificações Condominiais
-- Data: 2026-04-03
-- Banco: MariaDB

-- =====================================================
-- TABELAS DE REFERÊNCIA (devem ser criadas primeiro)
-- =====================================================

-- Tabela de tipos de notificação (ex: Multa, Advertência)
CREATE TABLE IF NOT EXISTS notificacao_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de status das notificações
CREATE TABLE IF NOT EXISTS notificacao_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de assuntos (motivos das notificações)
CREATE TABLE IF NOT EXISTS assuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS PRINCIPAIS
-- =====================================================

-- Tabela de usuários do sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'condomino',
    refresh_token VARCHAR(255) DEFAULT NULL,
    refresh_token_expira_em DATETIME DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_refresh_token (refresh_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela principal de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidade VARCHAR(50) NOT NULL,
    bloco VARCHAR(10) DEFAULT NULL,
    numero VARCHAR(20) NOT NULL,
    ano YEAR NOT NULL,
    data_emissao DATE NOT NULL,
    cidade_emissao VARCHAR(100) DEFAULT NULL,
    fundamentacao_legal TEXT DEFAULT NULL,
    texto_descritivo TEXT DEFAULT NULL,
    valor_multa VARCHAR(100) DEFAULT NULL,
    url_recurso VARCHAR(500) DEFAULT NULL,
    prazo_recurso INT DEFAULT 5,
    assunto_id INT NOT NULL,
    tipo_id INT NOT NULL,
    status_id INT NOT NULL DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assunto_id) REFERENCES assuntos(id),
    FOREIGN KEY (tipo_id) REFERENCES notificacao_tipos(id),
    FOREIGN KEY (status_id) REFERENCES notificacao_status(id),
    INDEX idx_numero_ano (numero, ano),
    INDEX idx_unidade (unidade),
    INDEX idx_status (status_id),
    INDEX idx_data_emissao (data_emissao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de fatos/descrições das notificações
CREATE TABLE IF NOT EXISTS notificacao_fatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_id INT NOT NULL,
    descricao TEXT NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE,
    INDEX idx_notificacao_id (notificacao_id),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens/evidências fotográficas
CREATE TABLE IF NOT EXISTS notificacao_imagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_id INT NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE,
    INDEX idx_notificacao_id (notificacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS (seed data)
-- =====================================================

-- Inserir tipos de notificação padrão
INSERT INTO notificacao_tipos (nome) VALUES 
    ('Advertência'),
    ('Multa'),
    ('Notificação Extra'),
    ('Comunicado')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Inserir status de notificação padrão
INSERT INTO notificacao_status (nome) VALUES 
    ('Pendente'),
    ('Em Análise'),
    ('Deferido'),
    ('Indeferido'),
    ('Cancelado')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Inserir assuntos comuns (exemplos)
INSERT INTO assuntos (descricao) VALUES 
    ('Perturbação do Sossego'),
    ('Uso Indevido de Áreas Comuns'),
    ('Deveres com o Pet'),
    ('Multa por Atraso'),
    ('Falta de Manutenção'),
    ('Estacionamento Irregular'),
    ('Barulho Excessivo'),
    ('Vazamento de Água'),
    ('Infração às Normas Condominiais'),
    ('Recurso Apresentado')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- Criar usuário administrador padrão
-- Email: admin@seusistema.com
-- Senha: umaSenhaMuitoForte123! (já com hash bcrypt)
INSERT INTO usuarios (nome, email, senha, role) VALUES 
    ('Administrador', 'admin@seusistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
