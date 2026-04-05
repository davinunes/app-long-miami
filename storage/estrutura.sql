-- Estrutura do banco de dados: app_multas
-- Gerado em: 2026-04-05 03:45:20

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- --------------------------------------------------------
-- Estrutura da tabela `assuntos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `assuntos`;
CREATE TABLE `assuntos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `configuracoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('string','text','number','boolean','json','file') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`),
  KEY `idx_chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `papel_principal` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_artigos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_artigos`;
CREATE TABLE `notificacao_artigos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `artigo_notacao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `artigo_texto` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('regimento','lei','outro') COLLATE utf8mb4_unicode_ci DEFAULT 'regimento',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notificacao_artigo` (`notificacao_id`,`artigo_notacao`),
  KEY `idx_notificacao` (`notificacao_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_fase_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_fase_log`;
CREATE TABLE `notificacao_fase_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `fase_anterior` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fase_nova` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_notificacao_fase` (`notificacao_id`),
  CONSTRAINT `notificacao_fase_log_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notificacao_fase_log_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_fatos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_fatos`;
CREATE TABLE `notificacao_fatos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_notificacao_id` (`notificacao_id`),
  KEY `idx_ordem` (`ordem`),
  CONSTRAINT `notificacao_fatos_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_imagens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_imagens`;
CREATE TABLE `notificacao_imagens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacao_id` int NOT NULL,
  `ocorrencia_id` int DEFAULT NULL,
  `anexo_ocorrencia_id` int DEFAULT NULL,
  `caminho_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inactive` tinyint(1) DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_notificacao_id` (`notificacao_id`),
  KEY `idx_ocorrencia_origem` (`ocorrencia_id`,`anexo_ocorrencia_id`),
  KEY `idx_inactive` (`inactive`),
  CONSTRAINT `notificacao_imagens_ibfk_1` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_status`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_status`;
CREATE TABLE `notificacao_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacao_tipos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacao_tipos`;
CREATE TABLE `notificacao_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `notificacoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notificacoes`;
CREATE TABLE `notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unidade` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bloco` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano` year NOT NULL,
  `data_emissao` date NOT NULL,
  `cidade_emissao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fundamentacao_legal` text COLLATE utf8mb4_unicode_ci,
  `texto_descritivo` text COLLATE utf8mb4_unicode_ci,
  `valor_multa` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_recurso` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `ciencia_por` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tem_recurso` tinyint(1) DEFAULT '0',
  `data_recurso` datetime DEFAULT NULL,
  `prazo_recurso_expira` date DEFAULT NULL,
  `recurso_texto` text COLLATE utf8mb4_unicode_ci,
  `recurso_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `encerrada` tinyint(1) DEFAULT '0',
  `data_encerramento` datetime DEFAULT NULL,
  `motivo_encerramento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_anexos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_anexos`;
CREATE TABLE `ocorrencia_anexos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `tipo` enum('imagem','video','audio','documento','link') COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` int DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inactive` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ocorrencia_anexos_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_anexos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_fase_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_fase_log`;
CREATE TABLE `ocorrencia_fase_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `fase_anterior` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fase_nova` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_data` (`created_at`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ocorrencia_fase_log_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_fase_log_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_mensagens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_mensagens`;
CREATE TABLE `ocorrencia_mensagens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `eh_evidencia` tinyint(1) DEFAULT '0',
  `tipo_anexo` enum('imagem','video','audio','link','documento') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anexo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anexo_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_evidencia` (`eh_evidencia`),
  CONSTRAINT `ocorrencia_mensagens_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_mensagens_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_notificacoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_notificacoes`;
CREATE TABLE `ocorrencia_notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `notificacao_id` int NOT NULL,
  `tipo_vinculo` enum('gerada','vinculada','derivada') COLLATE utf8mb4_unicode_ci DEFAULT 'gerada',
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vinculo` (`ocorrencia_id`,`notificacao_id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_notificacao` (`notificacao_id`),
  CONSTRAINT `ocorrencia_notificacoes_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ocorrencia_notificacoes_ibfk_2` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencia_unidades`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencia_unidades`;
CREATE TABLE `ocorrencia_unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int NOT NULL,
  `unidade_bloco` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bloco: A, B, C...',
  `unidade_numero` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número da unidade: 101, 502...',
  PRIMARY KEY (`id`),
  KEY `idx_ocorrencia` (`ocorrencia_id`),
  KEY `idx_unidade` (`unidade_bloco`,`unidade_numero`),
  CONSTRAINT `ocorrencia_unidades_ibfk_1` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `ocorrencias`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ocorrencias`;
CREATE TABLE `ocorrencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_fato` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_fato` date NOT NULL COMMENT 'Data em que o fato ocorreu',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `fase` enum('nova','em_analise','pronta','recusada','homologada') COLLATE utf8mb4_unicode_ci DEFAULT 'nova',
  `fase_obs` text COLLATE utf8mb4_unicode_ci COMMENT 'Observação sobre a fase (ex: motivo da recusa)',
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `papeles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `papeles`;
CREATE TABLE `papeles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `permissoes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissoes`;
CREATE TABLE `permissoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Estrutura da tabela `sindicos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sindicos`;
CREATE TABLE `sindicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
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
  `bloco` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `numero` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('residencial','comercial','administracao') COLLATE utf8mb4_unicode_ci DEFAULT 'residencial',
  `nome_proprietario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_proprietario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'condomino',
  `grupo_principal_id` int DEFAULT NULL,
  `refresh_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refresh_token_expira_em` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_refresh_token` (`refresh_token`),
  KEY `grupo_principal_id` (`grupo_principal_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`grupo_principal_id`) REFERENCES `grupos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;