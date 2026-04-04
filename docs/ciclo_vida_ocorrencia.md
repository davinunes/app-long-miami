# Ciclo de Vida das Ocorrências - App Long Miami

## Visão Geral

Uma **ocorrência** representa um fato relatado no condomínio que precisa ser investigado, documentado e eventualmente convertido em notificação formal.

---

## Estados (Fases)

```
┌─────────┐     ┌──────────────┐     ┌─────────┐     ┌────────────┐     ┌─────────────┐
│  NOVA   │ ──► │ EM_ANALISE   │ ──► │ PRONTA  │ ──► │ HOMOLOGADA │     │  RECUSADA   │
└─────────┘     └──────────────┘     └─────────┘     └────────────┘     └─────────────┘
     │                  │                 │                                      │
     │                  │                 └──────────────────────────────────────┘
     │                  │                          (pode voltar para análise)         
     └──────────────────┴────────────────────────────────────────────────────────────┘
```

| Fase | Descrição | Cor |
|------|-----------|-----|
| `nova` | Ocorrência recém-criada, aguardando análise | Azul |
| `em_analise` | Em investigação, evidências sendo coletadas (CFTV, fotos, etc.) | Laranja |
| `pronta` | Evidências coletadas, aguardando homologação | Roxo |
| `recusada` | Ocorrência rejeitada, não procede | Vermelho |
| `homologada` | Fato confirmado, pronta para notificação | Verde |

---

## Ações de Fase (Botões)

Cada transição de fase é uma ação separada com permissão específica:

| Ação | De | Para | Permissão Necessária |
|------|-----|------|---------------------|
| Colocar em Análise | nova | em_analise | `ocorrencia.colocar_em_analise` |
| Marcar como Pronta | em_analise | pronta | `ocorrencia.marcar_pronta` |
| Homologar | pronta | homologada | `ocorrencia.homologar` |
| Recusar | em_analise/pronta | recusada | `ocorrencia.recusar` |
| Voltar para Análise | recusada | em_analise | `ocorrencia.retornar_analise` |

---

## Fluxo Completo

```
CRIADOR (protocolar)                              SISTEMA
      │                                              │
      │  1. Cria ocorrência                          │
      ├──────────────────────────────────────────────►│
      │                                              │
      │                          fase = 'nova'        │
      │                          log criado           │
      │◄──────────────────────────────────────────────┤
      │                                              │
      ▼                                              ▼
┌──────────────────┐                    ┌──────────────────────┐
│ Botão: Colocar   │                    │ Mensagens/evidências │
│ em Análise       │                    │ podem ser adicionados │
└──────────────────┘                    └──────────────────────┘
      │                                              │
      │  2. Colocar em Análise                       │
      ├──────────────────────────────────────────────►│
      │                                              │
      │                          fase = 'em_analise' │
      │◄──────────────────────────────────────────────┤
      │                                              │
      ▼                                              ▼
┌──────────────────┐                    ┌──────────────────────┐
│ Botão: Marcar    │                    │ Evidências sendo     │
│ como Pronta      │                    │ coletadas (CFTV,     │
└──────────────────┘                    │ fotos, docs)        │
      │                                 └──────────────────────┘
      │                                              │
      │  3. Marcar como Pronta                       │
      ├──────────────────────────────────────────────►│
      │                                              │
      │                          fase = 'pronta'     │
      │◄──────────────────────────────────────────────┤
      │                                              │
      ▼                                              ▼
┌──────────────────┐                    ┌──────────────────────┐
│ Botão: Homologar │                    │ Ocorrência aguardando│
│ Botão: Recusar   │                    │ homologação         │
└──────────────────┘                    └──────────────────────┘
      │                                              │
      ├─► 3a. Homologar                              │
      │    ├──────────────────────────────────────►│
      │    │                    fase = 'homologada'
      │    │                    Notificação pode  │
      │    │                    ser gerada       │
      │    │◄──────────────────────────────────────┤
      │                                              │
      └─► 3b. Recusar                                │
           ├──────────────────────────────────────►│
           │                    fase = 'recusada'  │
           │◄──────────────────────────────────────┤
```

---

## Regras por Fase

### Fase: NOVA

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Qualquer usuário com acesso | Sempre |
| Editar ocorrência | Criador, admin, dev | Sempre |
| Listar ocorrências | Qualquer usuário com acesso | Sempre |
| Colocar em Análise | Usuários com permissão `colocar_em_analise` | Sempre |
| Adicionar mensagem | Usuários com `mensagem.criar` | Sempre |
| Adicionar evidência | Usuários com `evidencia.anexar` | Sempre |
| Vincular unidade | Usuários com `unidade.vincular` | Sempre |
| Criar notificação | - | **Não disponível** |

### Fase: EM_ANALISE

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Qualquer usuário com acesso | Sempre |
| Editar ocorrência | Admin, dev | Sempre |
| Marcar como Pronta | Usuários com permissão `marcar_pronta` | Sempre |
| Recusar | Usuários com permissão `recusar` | Sempre |
| Adicionar mensagem | Usuários com `mensagem.criar` | Sempre |
| Adicionar evidência | Usuários com `evidencia.anexar` | Sempre |
| Vincular/remover unidades | Usuários com `unidade.vincular` | Sempre |
| Criar notificação | - | **Não disponível** |

### Fase: PRONTA

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Qualquer usuário com acesso | Sempre |
| Editar ocorrência | Admin, dev | Sempre |
| Homologar | Usuários com permissão `homologar` | Sempre |
| Recusar | Usuários com permissão `recusar` | Sempre |
| Adicionar mensagem | Admin, dev | **Apenas admin/dev** |
| Adicionar evidência | Admin, dev | **Apenas admin/dev** |
| Criar notificação | - | **Não disponível** |

### Fase: HOMOLOGADA

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Qualquer usuário com acesso | Sempre |
| Editar ocorrência | Admin, dev | Sempre |
| Adicionar mensagem | Admin, dev | **Apenas admin/dev** |
| Adicionar evidência | Admin, dev | **Apenas admin/dev** |
| Vincular/remover unidades | Admin, dev | Sempre |
| Criar notificação | Usuários com `notificacao.criar` | Sempre |
| Excluir ocorrência | Admin, dev | Sempre |

### Fase: RECUSADA

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Qualquer usuário com acesso | Sempre |
| Editar ocorrência | Admin, dev | Sempre |
| Voltar para Análise | Usuários com `retornar_analise` | Sempre |
| Adicionar mensagem | Admin, dev | **Apenas admin/dev** |
| Criar notificação | - | **Não disponível** |

---

## Estrutura de Dados

### Tabela: `ocorrencias`

```sql
ocorrencias
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── titulo                VARCHAR(255) NOT NULL
├── descricao_fato        TEXT NOT NULL           -- Descrição detalhada do fato
├── data_fato             DATE NOT NULL            -- Data em que o fato ocorreu
├── data_criacao          TIMESTAMP DEFAULT NOW() -- Data do registro no sistema
├── fase                  ENUM('nova','em_analise','pronta','recusada','homologada') DEFAULT 'nova'
├── fase_obs              TEXT                    -- Observação da última mudança de fase
├── created_by            INT NOT NULL             -- FK → usuarios.id
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
├── updated_at            TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
├── notificacao_id        INT                      -- FK → notificacoes.id (após gerar)
└── deleted_at            TIMESTAMP               -- Soft delete
```

### Tabela: `ocorrencia_unidades`

```sql
ocorrencia_unidades
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── ocorrencia_id         INT NOT NULL            -- FK → ocorrencias.id
├── unidade_bloco         VARCHAR(10)              -- A, B, C, etc.
├── unidade_numero        VARCHAR(20)             -- 101, 202, 1001, etc.
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── UNIQUE(ocorrencia_id, unidade_bloco, unidade_numero)
```

### Tabela: `ocorrencia_mensagens`

```sql
ocorrencia_mensagens
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── ocorrencia_id         INT NOT NULL            -- FK → ocorrencias.id
├── usuario_id            INT NOT NULL            -- FK → usuarios.id
├── mensagem              TEXT NOT NULL
├── eh_evidencia          BOOLEAN DEFAULT FALSE   -- Marcação de evidência
├── tipo_anexo            VARCHAR(50)             -- imagem, video, audio, link
├── anexo_url             VARCHAR(500)            -- URL do anexo (se houver)
├── anexo_nome            VARCHAR(255)            -- Nome original do anexo
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(ocorrencia_id)
```

### Tabela: `ocorrencia_anexos`

```sql
ocorrencia_anexos
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── ocorrencia_id         INT NOT NULL            -- FK → ocorrencias.id
├── usuario_id            INT NOT NULL            -- FK → usuarios.id
├── tipo                  VARCHAR(50)             -- imagem, video, audio, documento, link
├── url                   VARCHAR(500)            -- Caminho ou URL do arquivo
├── nome_original         VARCHAR(255)            -- Nome original do arquivo
├── tamanho_bytes         BIGINT                  -- Tamanho em bytes
├── mime_type             VARCHAR(100)            -- Tipo MIME
├── inactive              BOOLEAN DEFAULT FALSE   -- Soft delete
├── deleted_at            TIMESTAMP               -- Data da remoção
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(ocorrencia_id, inactive)
```

### Tabela: `ocorrencia_fase_log`

```sql
ocorrencia_fase_log
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── ocorrencia_id         INT NOT NULL            -- FK → ocorrencias.id
├── fase_anterior         VARCHAR(50)              -- Fase anterior (NULL se criação)
├── fase_nova             VARCHAR(50) NOT NULL     -- Nova fase
├── observacao            TEXT                     -- Motivo da mudança
├── usuario_id            INT                      -- FK → usuarios.id (quem alterou)
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(ocorrencia_id)
```

---

## Histórico de Alterações

| Data | Alteração |
|------|-----------|
| 03/04/2026 | Versão inicial com 4 fases |
| 04/04/2026 | Adicionado suporte a links como evidência |
| 04/04/2026 | Adicionado soft delete em anexos |

---

## API Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/ocorrencias.php` | Lista ocorrências |
| GET | `/api/ocorrencias.php?id=X` | Busca ocorrência por ID |
| GET | `/api/ocorrencias.php?homologadas=1` | Lista homologadas |
| GET | `/api/ocorrencias.php?minhas=1` | Lista criadas pelo usuário |
| POST | `/api/ocorrencias.php` | Cria/Edita ocorrência |
| POST | `/api/ocorrencias.php?upload=1` | Upload de anexo |
| POST | `{ "gerar_notificacao": true }` | Gera notificação |

---

## Funcionalidades por Tipo de Usuário

```
┌────────────────┬───────────────────────────────────────────────────────┐
│ Papel          │ Capacidades                                           │
├────────────────┼───────────────────────────────────────────────────────┤
│ protocolar     │ • Criar ocorrências                                   │
│                │ • Editar próprias ocorrências (fase nova)             │
│                │ • Alterar fase para 'em_analise'                     │
│                │ • Adicionar mensagens e evidências                   │
│                │ • Vincular unidades                                   │
├────────────────┼───────────────────────────────────────────────────────┤
│ diligente      │ • Listar ocorrências                                 │
│                │ • Ver detalhes                                       │
│                │ • Adicionar mensagens e evidências                   │
│                │ • Upload de anexos                                   │
├────────────────┼───────────────────────────────────────────────────────┤
│ promotor       │ • Todas as capacidades de diligente                   │
│                │ • Homologar ocorrências                              │
│                │ • Recusar ocorrências                                 │
│                │ • Alterar fase de ocorrências                         │
│                │ • Gerenciar unidades                                 │
├────────────────┼───────────────────────────────────────────────────────┤
│ notificador    │ • Listar ocorrências                                 │
│                │ • Ver detalhes de homologadas                        │
│                │ • **Gerar notificações**                             │
├────────────────┼───────────────────────────────────────────────────────┤
│ admin          │ • Todas as capacidades                                │
│                │ • Excluir ocorrências                               │
│                │ • Acessar configurações                              │
├────────────────┼───────────────────────────────────────────────────────┤
│ dev            │ • **MODO DEUS** - Acesso total                        │
└────────────────┴───────────────────────────────────────────────────────┘
```

---

## Status da Implementação

- [x] CRUD completo de ocorrências
- [x] Sistema de fases (nova, em_analise, recusada, homologada)
- [x] Log de alterações de fase
- [x] Unidades vinculadas
- [x] Mensagens/chat
- [x] Evidências (imagens, videos, audios)
- [x] Anexos arquivos
- [x] Links como evidência (Google Drive, OneDrive, etc.)
- [x] Soft delete de anexos
- [x] Geração de notificação
- [ ] Edição de mensagens (pendente)
- [ ] Recurso de imagens (colado via Ctrl+V)
