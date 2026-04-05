-- ========================================================
-- ESTRUTURA E DADOS INICIAIS - app_multas
-- Gerado em: 2026-04-05 18:53:52
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------
-- Estrutura da tabela `assuntos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `assuntos`;
CREATE TABLE `assuntos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `configuracoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tipo` enum('string','text','number','boolean','json','file') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`),
  KEY `idx_chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `evidencia_compartilhada`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `evidencia_compartilhada`;
CREATE TABLE `evidencia_compartilhada` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_anexo_id` int NOT NULL,
  `notificacao_id` int NOT NULL,
  `inactive` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ocorrencia_notificacao` (`ocorrencia_anexo_id`,`notificacao_id`),
  KEY `idx_notificacao` (`notificacao_id`),
  KEY `idx_ocorrencia_anexo` (`ocorrencia_anexo_id`),
  CONSTRAINT `evidencia_compartilhada_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `grupo_permissoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `grupo_permissoes`;
CREATE TABLE `grupo_permissoes` (
  `grupo_id` int NOT NULL,
  `permissao_id` int NOT NULL,
  PRIMARY KEY (`grupo_id`,`permissao_id`),
  KEY `permissao_id` (`permissao_id`),
  CONSTRAINT `grupo_permissoes_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grupo_permissoes_ibfk_2` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `grupos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `grupos`;
CREATE TABLE `grupos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `papel_principal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_artigos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_artigos`;
CREATE TABLE `notificacao_artigos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `artigo_notacao` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `artigo_texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tipo` enum('regimento','lei','outro') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'regimento',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notificacao_artigo` (`notificacao_id`,`artigo_notacao`),
  KEY `idx_notificacao` (`notificacao_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_fase_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_fase_log`;
CREATE TABLE `notificacao_fase_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `fase_anterior` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fase_nova` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_notificacao_fase` (`notificacao_id`),
  CONSTRAINT `notificacao_fase_log_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notificacao_fase_log_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_fatos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_fatos`;
CREATE TABLE `notificacao_fatos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_notificacao_id` (`notificacao_id`),
  KEY `idx_ordem` (`ordem`),
  CONSTRAINT `notificacao_fatos_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_imagens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_imagens`;
CREATE TABLE `notificacao_imagens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `ocorrencia_id` int DEFAULT NULL,
  `anexo_ocorrencia_id` int DEFAULT NULL,
  `caminho_arquivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inactive` tinyint(1) DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_notificacao_id` (`notificacao_id`),
  KEY `idx_ocorrencia_origem` (`ocorrencia_id`,`anexo_ocorrencia_id`),
  KEY `idx_inactive` (`inactive`),
  CONSTRAINT `notificacao_imagens_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_status`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_status`;
CREATE TABLE `notificacao_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_tipos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_tipos`;
CREATE TABLE `notificacao_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `notificacoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacoes`;
CREATE TABLE `notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unidade` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bloco` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano` year NOT NULL,
  `data_emissao` date NOT NULL,
  `cidade_emissao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fundamentacao_legal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `texto_descritivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valor_multa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_recurso` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prazo_recurso` int DEFAULT '5',
  `assunto_id` int NOT NULL,
  `tipo_id` int NOT NULL,
  `status_id` int NOT NULL DEFAULT '1',
  `ocorrencia_id` int DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data_lavratura` datetime DEFAULT NULL,
  `data_envio` datetime DEFAULT NULL,
  `lavrada_por` int DEFAULT NULL,
  `data_ciencia` datetime DEFAULT NULL,
  `ciencia_por` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tem_recurso` tinyint(1) DEFAULT '0',
  `data_recurso` datetime DEFAULT NULL,
  `prazo_recurso_expira` date DEFAULT NULL,
  `recurso_texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recurso_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `encerrada` tinyint(1) DEFAULT '0',
  `data_encerramento` datetime DEFAULT NULL,
  `motivo_encerramento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assunto_id` (`assunto_id`),
  KEY `tipo_id` (`tipo_id`),
  KEY `idx_numero_ano` (`numero`,`ano`),
  KEY `idx_unidade` (`unidade`),
  KEY `idx_status` (`status_id`),
  KEY `idx_data_emissao` (`data_emissao`),
  KEY `idx_ocorrencia_id` (`ocorrencia_id`),
  KEY `fk_lavrada_por` (`lavrada_por`),
  CONSTRAINT `fk_lavrada_por` FOREIGN KEY (`lavrada_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_notificacao_ocorrencia` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`assunto_id`) REFERENCES `assuntos` (`id`),
  CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`tipo_id`) REFERENCES `notificacao_tipos` (`id`),
  CONSTRAINT `notificacoes_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `notificacao_status` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_anexos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_anexos`;
CREATE TABLE `ocorrencia_anexos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `tipo` enum('imagem','video','audio','documento','link') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` int DEFAULT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ocorrencia_anexos_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_anexos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_fase_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_fase_log`;
CREATE TABLE `ocorrencia_fase_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `fase_anterior` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fase_nova` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `usuario_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_data` (`created_at`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ocorrencia_fase_log_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_fase_log_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_mensagens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_mensagens`;
CREATE TABLE `ocorrencia_mensagens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `mensagem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `eh_evidencia` tinyint(1) DEFAULT '0',
  `tipo_anexo` enum('imagem','video','audio','link','documento') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anexo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anexo_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_evidencia` (`eh_evidencia`),
  CONSTRAINT `ocorrencia_mensagens_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_mensagens_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_notificacoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_notificacoes`;
CREATE TABLE `ocorrencia_notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `notificacao_id` int NOT NULL,
  `tipo_vinculo` enum('gerada','vinculada','derivada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'gerada',
  `observacao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vinculo` (`ocorrencia_id`,`notificacao_id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_notificacao` (`notificacao_id`),
  CONSTRAINT `ocorrencia_notificacoes_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_notificacoes_ibfk_2` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_unidades`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_unidades`;
CREATE TABLE `ocorrencia_unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `unidade_bloco` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bloco: A, B, C...',
  `unidade_numero` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número da unidade: 101, 502...',
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_unidade` (`unidade_bloco`,`unidade_numero`),
  CONSTRAINT `ocorrencia_unidades_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencias`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencias`;
CREATE TABLE `ocorrencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_fato` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_fato` date NOT NULL COMMENT 'Data em que o fato ocorreu',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `fase` enum('nova','em_analise','pronta','recusada','homologada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'nova',
  `fase_obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Observação sobre a fase (ex: motivo da recusa)',
  `notificacao_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fase` (`fase`),
  KEY `idx_data_fato` (`data_fato`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_notificacao_id` (`notificacao_id`),
  KEY `idx_ocorrencias_fase` (`fase`),
  CONSTRAINT `fk_ocorrencia_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ocorrencias_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `papeles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `papeles`;
CREATE TABLE `papeles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `permissoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissoes`;
CREATE TABLE `permissoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `modulo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `sindicos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sindicos`;
CREATE TABLE `sindicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_data_inicio` (`data_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `unidades`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `unidades`;
CREATE TABLE `unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bloco` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `numero` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('residencial','comercial','administracao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'residencial',
  `nome_proprietario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_proprietario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unidade` (`bloco`,`numero`),
  KEY `idx_bloco` (`bloco`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `usuario_grupos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `usuario_grupos`;
CREATE TABLE `usuario_grupos` (
  `usuario_id` int NOT NULL,
  `grupo_id` int NOT NULL,
  PRIMARY KEY (`usuario_id`,`grupo_id`),
  KEY `grupo_id` (`grupo_id`),
  CONSTRAINT `usuario_grupos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_grupos_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `usuario_permissoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `usuario_permissoes`;
CREATE TABLE `usuario_permissoes` (
  `usuario_id` int NOT NULL,
  `permissao_id` int NOT NULL,
  `granted_by` int DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`,`permissao_id`),
  KEY `permissao_id` (`permissao_id`),
  KEY `granted_by` (`granted_by`),
  CONSTRAINT `usuario_permissoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_permissoes_ibfk_2` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuario_permissoes_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `usuarios`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'condomino',
  `grupo_principal_id` int DEFAULT NULL,
  `refresh_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refresh_token_expira_em` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_refresh_token` (`refresh_token`),
  KEY `grupo_principal_id` (`grupo_principal_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`grupo_principal_id`) REFERENCES `grupos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- DADOS INICIAIS (SEED)
-- ========================================================

-- ========================================================
-- DADOS INICIAIS (SEED)
-- ========================================================

-- Assuntos predefinidos para notificações
-- STRUCTURE: assuntos
-- --------------------------------------------------------
CREATE TABLE `assuntos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `assuntos`
-- SEED DATA: assuntos
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('11', 'Pertubação do Sossego', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('12', 'Uso da área comum sem autorização', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('13', 'Estacionamento irregular', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('14', 'Vazamento de água', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('15', 'Deveres com Animais de Estimação', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('16', 'Lixo em local inadequado', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('17', 'Manutenção de fachada', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('18', 'Alteração não autorizada na unidade', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('19', 'Lançamento de objetos', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('20', 'Fumo em área comum', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('21', 'Prática esportiva em local proibido', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('22', 'Veículo em local proibido', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('23', 'Circulação de pessoas não autorizadas', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('24', 'Desperdício de água', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('25', 'Danos às áreas comuns', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('26', 'Desobediência às normas do regimento', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('27', 'Infração de caráter ambiental', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('28', 'Ausência de identificação', '2026-04-05 17:21:48');
INSERT INTO `assuntos` (`id`, `descricao`, `criado_em`) VALUES ('29', 'Outros', '2026-04-05 17:21:48');

-- Grupos de usuários padrão
-- STRUCTURE: grupos
-- --------------------------------------------------------
CREATE TABLE `grupos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `papel_principal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `grupos`
-- SEED DATA: grupos
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('12', 'Admin', '', NULL, '2026-04-05 04:49:56', '2026-04-05 05:08:37');
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('13', 'Fiscal', 'Cria ocorrencias', NULL, '2026-04-05 05:12:45', '2026-04-05 05:12:45');
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('14', 'Cftv', 'Busca Imagens', NULL, '2026-04-05 05:14:07', '2026-04-05 05:14:07');
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('15', 'Promotor', 'Avalia a ocorrencia e cria a notificação', NULL, '2026-04-05 05:17:44', '2026-04-05 05:17:44');
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('16', 'Sindico', '', NULL, '2026-04-05 05:20:10', '2026-04-05 05:20:10');
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('17', 'Contabilidade', '', NULL, '2026-04-05 14:28:39', '2026-04-05 14:28:39');
INSERT INTO `grupos` (`id`, `nome`, `descricao`, `papel_principal`, `created_at`, `updated_at`) VALUES ('18', 'Mensageria', '', NULL, '2026-04-05 14:30:04', '2026-04-05 14:30:04');

-- Status do ciclo de vida das notificações
-- STRUCTURE: notificacao_status
-- --------------------------------------------------------
CREATE TABLE `notificacao_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `notificacao_status`
-- SEED DATA: notificacao_status
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('10', 'Rascunho', '2026-04-05 17:21:48', 'rascunho');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('11', 'Lavrada', '2026-04-05 17:21:48', 'lavrada');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('12', 'Enviada', '2026-04-05 17:21:48', 'enviada');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('13', 'Ciente', '2026-04-05 17:21:48', 'ciente');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('14', 'Em Recurso', '2026-04-05 17:21:48', 'em_recurso');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('15', 'Recurso Deferido', '2026-04-05 17:21:48', 'recurso_deferido');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('16', 'Recurso Indeferido', '2026-04-05 17:21:48', 'recurso_indeferido');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('17', 'Em Cobrança', '2026-04-05 17:21:48', 'cobranca');
INSERT INTO `notificacao_status` (`id`, `nome`, `criado_em`, `slug`) VALUES ('18', 'Encerrada', '2026-04-05 17:21:48', 'encerrada');

-- Tipos de notificação (Advertência, Multa, etc)
-- STRUCTURE: notificacao_tipos
-- --------------------------------------------------------
CREATE TABLE `notificacao_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `notificacao_tipos`
-- SEED DATA: notificacao_tipos
INSERT INTO `notificacao_tipos` (`id`, `nome`, `criado_em`) VALUES ('5', 'Advertência', '2026-04-05 17:21:48');
INSERT INTO `notificacao_tipos` (`id`, `nome`, `criado_em`) VALUES ('6', 'Multa', '2026-04-05 17:21:48');
INSERT INTO `notificacao_tipos` (`id`, `nome`, `criado_em`) VALUES ('7', 'Orientação Educativa', '2026-04-05 17:21:48');
INSERT INTO `notificacao_tipos` (`id`, `nome`, `criado_em`) VALUES ('8', 'Notificação Extrajudicial', '2026-04-05 17:21:48');

-- Papéis legados do sistema
-- STRUCTURE: papeles
-- --------------------------------------------------------
CREATE TABLE `papeles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistema de permissões granulares
-- STRUCTURE: permissoes
-- --------------------------------------------------------
CREATE TABLE `permissoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `modulo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `permissoes`
-- SEED DATA: permissoes
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('184', 'ocorrencia.criar', 'Criar Ocorrência', 'Permite criar novas ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('185', 'ocorrencia.editar', 'Editar Ocorrência', 'Editar qualquer ocorrência', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('186', 'ocorrencia.editar_propria', 'Editar Própria Ocorrência', 'Editar apenas ocorrências criadas', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('187', 'ocorrencia.excluir', 'Excluir Ocorrência', 'Excluir ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('188', 'ocorrencia.listar', 'Listar Ocorrências', 'Listar todas as ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('189', 'ocorrencia.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('190', 'ocorrencia.alterar_fase', 'Alterar Fase', 'Alterar fase da ocorrência', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('191', 'ocorrencia.homologar', 'Homologar', 'Homologar ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('192', 'ocorrencia.recusar', 'Recusar', 'Recusar ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('193', 'ocorrencia.gerar_notificacao', 'Gerar Notificação', 'Gerar notificação a partir da ocorrência', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('194', 'ocorrencia.unidade.vincular', 'Vincular Unidade', 'Vincular unidades a ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('195', 'ocorrencia.unidade.remover', 'Remover Unidade', 'Remover unidades de ocorrências', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('196', 'ocorrencia.mensagem.criar', 'Criar Mensagem', 'Adicionar mensagens', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('197', 'ocorrencia.mensagem.editar', 'Editar Mensagem', 'Editar qualquer mensagem', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('198', 'ocorrencia.mensagem.editar_propria', 'Editar Própria Mensagem', 'Editar apenas mensagens criadas', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('199', 'ocorrencia.mensagem.excluir', 'Excluir Mensagem', 'Excluir qualquer mensagem', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('200', 'ocorrencia.mensagem.excluir_propria', 'Excluir Própria Mensagem', 'Excluir apenas mensagens criadas', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('201', 'ocorrencia.evidencia.marcar', 'Marcar Evidência', 'Marcar mensagem como evidência', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('202', 'ocorrencia.evidencia.anexar', 'Anexar Evidência', 'Anexar evidência (arquivo)', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('203', 'ocorrencia.evidencia.link', 'Adicionar Link', 'Adicionar link como evidência', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('204', 'ocorrencia.evidencia.excluir', 'Excluir Evidência', 'Excluir evidência', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('205', 'ocorrencia.anexo.criar', 'Anexar Arquivo', 'Anexar arquivos', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('206', 'ocorrencia.anexo.excluir', 'Excluir Anexo', 'Excluir qualquer anexo', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('207', 'ocorrencia.anexo.excluir_proprio', 'Excluir Próprio Anexo', 'Excluir apenas anexos criados', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('208', 'ocorrencia.link.criar', 'Adicionar Link', 'Adicionar link como anexo', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('209', 'ocorrencia.link.excluir', 'Excluir Link', 'Excluir link', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('210', 'ocorrencia.colocar_em_analise', 'Colocar em Análise', 'Mudar para fase análise', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('211', 'ocorrencia.solicitar_complemento', 'Solicitar Complemento', 'Mudar para fase complemento', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('212', 'ocorrencia.finalizar', 'Finalizar', 'Mudar para fase finalizada', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('213', 'ocorrencia.marcar_pronta', 'Marcar Pronta', 'Mudar para fase pronta', 'ocorrencia', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('214', 'notificacao.criar', 'Criar Notificação', 'Criar novas notificações', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('215', 'notificacao.editar', 'Editar Notificação', 'Editar qualquer notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('216', 'notificacao.editar_propria', 'Editar Própria Notificação', 'Editar apenas notificações criadas', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('217', 'notificacao.excluir', 'Excluir Notificação', 'Excluir notificações', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('218', 'notificacao.listar', 'Listar Notificações', 'Listar notificações', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('219', 'notificacao.ver', 'Ver Notificação', 'Ver detalhes de notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('220', 'notificacao.lavrar', 'Lavrar Notificação', 'Assinar/lavrar notificações', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('221', 'notificacao.revogar_assinatura', 'Revogar Assinatura', 'Revogar assinatura de notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('222', 'notificacao.registrar_ciencia', 'Registrar Ciência', 'Registrar ciência do morador', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('223', 'notificacao.gerar_pdf', 'Gerar PDF', 'Gerar PDF da notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('224', 'notificacao.marcar_enviada', 'Marcar Enviada', 'Marcar notificação como enviada', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('225', 'notificacao.encerrar', 'Encerrar Notificação', 'Encerrar notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('226', 'notificacao.alterar_fase', 'Alterar Fase', 'Permissão geral para transições de fase da notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('227', 'notificacao.registrar_recurso', 'Registrar Recurso', 'Registrar a interposição de recurso pelo morador', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('228', 'notificacao.julgar_recurso', 'Julgar Recurso', 'Deferir ou indeferir recursos de notificação', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('229', 'notificacao.marcar_cobranca', 'Marcar para Cobrança', 'Disponibilizar a notificação para lançamento em boleto', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('230', 'notificacao.reabrir', 'Reabrir Notificação', 'Reverter o status encerrada ou cobrança para a fase anterior', 'notificacao', '2026-04-05 05:04:19');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('231', 'notificacao.acao_rapida', 'Ações Rápidas', 'Usar ações rápidas (Enviar/Encerrar) na lista', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('232', 'notificacao.listar_lavradas', 'Listar Lavradas', 'Ver notificações no status lavrada', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('233', 'notificacao.listar_enviadas', 'Listar Enviadas', 'Ver notificações no status enviada', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('234', 'notificacao.listar_em_cobranca', 'Listar em Cobrança', 'Ver notificações em cobrança', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('235', 'notificacao.editar_campos', 'Editar Campos', 'Permite editar os campos da notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('236', 'notificacao.editar_datas', 'Editar Datas', 'Permite editar datas de envio e ciência', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('237', 'notificacao.retornar_rascunho', 'Retornar ao Rascunho', 'Retornar notificação lavrada para rascunho', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('238', 'notificacao.imagem.anexar', 'Anexar Imagem', 'Anexar imagem à notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('239', 'notificacao.imagem.sincronizar', 'Sincronizar Imagens', 'Sincronizar imagens da ocorrência', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('240', 'notificacao.imagem.remover', 'Remover Imagem', 'Remover imagem da notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('241', 'notificacao.imagem.ativar', 'Reativar Imagem', 'Reativar imagem removida', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('242', 'notificacao.assunto.editar', 'Editar Assunto', 'Editar assunto da notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('243', 'notificacao.tipo.editar', 'Editar Tipo', 'Editar tipo da notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('244', 'notificacao.fato.adicionar', 'Adicionar Fato', 'Adicionar fato à notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('245', 'notificacao.fato.editar', 'Editar Fato', 'Editar fato da notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('246', 'notificacao.fato.remover', 'Remover Fato', 'Remover fato da notificação', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('247', 'notificacao.artigo.vincular', 'Vincular Artigo', 'Vincular artigo do regimento', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('248', 'notificacao.artigo.desvincular', 'Desvincular Artigo', 'Desvincular artigo', 'notificacao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('249', 'configuracao.acessar', 'Acessar Configurações', 'Acessar página de configurações', 'configuracao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('250', 'configuracao.condominio.editar', 'Editar Condomínio', 'Editar dados do condomínio', 'configuracao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('251', 'configuracao.sindico.gerenciar', 'Gerenciar Síndicos', 'Gerenciar síndicos', 'configuracao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('252', 'configuracao.regimento.editar', 'Editar Regimento', 'Editar regimento interno', 'configuracao', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('253', 'usuario.listar', 'Listar Usuários', 'Listar usuários', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('254', 'usuario.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('255', 'usuario.criar', 'Criar Usuário', 'Criar novo usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('256', 'usuario.editar', 'Editar Usuário', 'Editar usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('257', 'usuario.editar_papeis', 'Editar Papéis', 'Alterar papéis do usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('258', 'usuario.editar_grupo', 'Editar Grupo', 'Alterar grupo do usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('259', 'usuario.excluir', 'Excluir Usuário', 'Excluir usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('260', 'usuario.trocar_senha', 'Trocar Senha', 'Trocar senha de qualquer usuário', 'usuario', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('261', 'grupo.listar', 'Listar Grupos', 'Listar grupos', 'grupo', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('262', 'grupo.criar', 'Criar Grupo', 'Criar novo grupo', 'grupo', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('263', 'grupo.editar', 'Editar Grupo', 'Editar grupo', 'grupo', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('264', 'grupo.excluir', 'Excluir Grupo', 'Excluir grupo', 'grupo', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('265', 'grupo.gerenciar_permissoes', 'Gerenciar Permissões', 'Associar/desassociar permissões', 'grupo', '2026-04-05 05:04:20');
INSERT INTO `permissoes` (`id`, `slug`, `nome`, `descricao`, `modulo`, `created_at`) VALUES ('300', 'notificacao.excluir_propria', 'Excluir Própria Notificação', 'Excluir apenas notificações criadas', 'notificacao', '2026-04-05 18:19:25');

SET FOREIGN_KEY_CHECKS = 1;
