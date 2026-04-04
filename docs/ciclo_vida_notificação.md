# Ciclo de Vida das Notificações - App Long Miami

## Visão Geral

Uma **notificação** é um documento formal emitido pelo condomínio para comunicar um morador sobre uma irregularidade ou atitude necessária. É gerada a partir de ocorrências **homologadas**.

---

## Estados (Status)

```
┌──────────┐     ┌────────┐     ┌────────┐     ┌────────────┐     ┌───────────┐     ┌───────────┐
│ RASCUNHO │ ──► │ LAVRADA│ ──► │ ENVIADA │ ──► │ CIENTE     │ ──► │ EM_RECURSO│ ──► │ ENCERRADA │
└──────────┘     └────────┘     └────────┘     └────────────┘     └───────────┘     └───────────┘
                                                                        │
                                                                        ▼
                                                              ┌─────────────────┐
                                                              │ RECURSO_DEFERIDO│
                                                              └─────────────────┘
                                                                        │
                                                                        ▼
                                                              ┌─────────────────┐
                                                              │RECURSO_INDEFERIDO│
                                                              └─────────────────┘
```

| Status | Descrição | Cor |
|--------|-----------|-----|
| `rascunho` | Notificação em elaboração | Cinza |
| `lavrada` | Assinada pelo síndico | Azul |
| `enviada` | Despachada ao morador | Amarelo |
| `ciente` | Morador tomou ciência | Verde |
| `em_recurso` | Recurso interposto | Laranja |
| `encerrada` | Arquivada/definitiva | Verde escuro |
| `recurso_deferido` | Recurso aceito | Verde |
| `recurso_indeferido` | Recurso negado | Vermelho |

---

## Fluxo Completo

```
NOTIFICADOR                          SISTEMA
     │                                    │
     │  1. Cria notificação              │
     ├───────────────────────────────────►│
     │                                    │
     │                    status = 'rascunho'
     │                    número gerado    │
     │◄───────────────────────────────────┤
     │                                    │
     ▼                                    ▼
┌──────────────────┐          ┌──────────────────────┐
│ Adiciona:        │          │ Armazenado em:       │
│ • Fatos          │          │ • notificacao_fatos  │
│ • Evidências     │          │ • notificacao_imagens│
│ • Artigos        │          │ • notificacao_artigos│
│ • Fundamentação  │          └──────────────────────┘
└──────────────────┘
     │                                   
     │  2. Solicita lavratura           
     ├───────────────────────────────────►│
     │                                    │
     │                    status = 'lavrada'
     │                    data_lavratura   
     │                    lavrada_por     
     │◄───────────────────────────────────┤
     │                                    │
     ▼                                    ▼
┌──────────────────┐          ┌──────────────────────┐
│ Assinador/       │          │                      │
│ Síndico assina   │          │                      │
└──────────────────┘          └──────────────────────┘
     │                                   
     │  3. Despachante envia             
     ├───────────────────────────────────►│
     │                                    │
     │                    status = 'enviada'
     │                    data_envio       
     │◄───────────────────────────────────┤
     │                                    │
     ▼                                    ▼
┌──────────────────┐          ┌──────────────────────┐
│ Mensageiro/      │          │                      │
│ Porteiro entrega │          │                      │
└──────────────────┘          └──────────────────────┘
     │                                   
     │  4. Morador science              
     ├───────────────────────────────────►│
     │                                    │
     │                    status = 'ciente'
     │                    data_ciencia     
     │◄───────────────────────────────────┤
     │                                    │
     ├──── OU ────────────────────────────┤
     │                                    │
     │  5. Prazo expirou (sem ciência)   │
     ├───────────────────────────────────►│
     │                                    │
     │                    status = 'encerrada'
     │                    (para cobrar)   
     │◄───────────────────────────────────┤
     │                                    │
     ├──── OU ────────────────────────────┤
     │                                    │
     │  6. Morador entra com recurso     │
     ├───────────────────────────────────►│
     │                                    │
     │                    status = 'em_recurso'
     │◄───────────────────────────────────┤
```

---

## Regras por Status

### Status: RASCUNHO

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Editar conteúdo | Criador, notificador, admin, dev | Sempre |
| Adicionar fatos | Notificador, admin, dev | Sempre |
| Adicionar evidências | Notificador, admin, dev | Sempre |
| Sincronizar evidências da ocorrência | Notificador, admin, dev | Se vinculada a ocorrência |
| Vincular artigos do regimento | Notificador, admin, dev | Sempre |
| Lavrar (assinar) | Admin, dev | **Bloqueado** |
| Gerar PDF | Admin, dev | **Bloqueado** |
| Excluir | Criador, admin, dev | Sempre |

### Status: LAVRADA

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Todos com acesso | Sempre |
| Editar conteúdo | Admin, dev | **Apenas admin/dev** |
| Adicionar fatos | - | **Bloqueado** |
| Revogar assinatura | Admin, dev | Pode voltar para rascunho |
| Gerar PDF | Assinador, despachante, admin, dev | Sempre |
| Marcar como enviada | Despachante, admin, dev | Sempre |
| Registrar ciência | Mensageiro, admin, dev | Sempre |

### Status: ENVIADA

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Todos com acesso | Sempre |
| Gerar PDF | Todos com acesso | Sempre |
| Registrar ciência | Mensageiro, admin, dev | Sempre |
| Marcar como encerrada | Admin, dev | Quando prazo expira |

### Status: CIENTE

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Todos com acesso | Sempre |
| Gerar PDF | Todos com acesso | Sempre |
| Registrar recurso | Morador (via sistema externo) | Prazo válido |
| Encerrar | Admin, dev | Sempre |

### Status: EM_RECURSO

| Ação | Permitida para | Condição |
|------|----------------|----------|
| Ver detalhes | Todos com acesso | Sempre |
| Anexar parecer | Conselho (via API externa) | Sistema externo |
| Deferir recurso | Admin, dev | Muda para `recurso_deferido` |
| Indeferir recurso | Admin, dev | Muda para `recurso_indeferido` |

---

## Estrutura de Dados

### Tabela: `notificacoes`

```sql
notificacoes
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── numero                VARCHAR(10) NOT NULL            -- ex: '076'
├── ano                   YEAR NOT NULL                   -- ex: 2026
├── unidade               VARCHAR(50)                      -- ex: '101'
├── bloco                 VARCHAR(10)                      -- ex: 'A'
├── tipo_id               INT NOT NULL                    -- FK → notificacao_tipos.id
├── assunto_id            INT                             -- FK → notificacao_assuntos.id
├── status_id             INT NOT NULL                   -- FK → notificacao_status.id
├── texto_descritivo      TEXT                            -- Descrição geral
├── data_emissao          DATE                            -- Data de emissão
├── cidade_emissao        VARCHAR(100)                    -- Cidade
├── ocorrencia_id         INT                             -- FK → ocorrencias.id (origem)
├── created_by            INT NOT NULL                    -- FK → usuarios.id
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
├── updated_at            TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
│
│── Campos de Lavratura
├── data_lavratura        DATETIME                        -- Quando foi lavrada
├── lavrada_por           INT                             -- FK → usuarios.id
│
│── Campos de Ciência
├── data_ciencia          DATETIME                        -- Quando morador soube
├── ciencia_por           VARCHAR(100)                    -- Como foi comunicada
│
│── Campos de Recurso
├── tem_recurso           BOOLEAN DEFAULT FALSE
├── data_recurso          DATETIME
├── prazo_recurso_expira  DATE
├── recurso_texto         TEXT
├── recurso_status        ENUM('deferido','indeferido')
│
│── Campos de Encerramento
├── encerrada             BOOLEAN DEFAULT FALSE
├── data_encerramento     DATETIME
├── motivo_encerramento   VARCHAR(50)
│
├── deleted_at            TIMESTAMP                       -- Soft delete
└── UNIQUE(numero, ano)
```

### Tabela: `notificacao_tipos`

```sql
notificacao_tipos
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── nome                  VARCHAR(100) NOT NULL          -- ex: 'Advertência'
├── slug                  VARCHAR(50) UNIQUE NOT NULL    -- ex: 'advertencia'
├── descricao             TEXT
├──created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

**Tipos existentes:**
- Advertência
- Multa
- Notificação extrajudicial
- Comunicação interna

### Tabela: `notificacao_status`

```sql
notificacao_status
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── nome                  VARCHAR(50) NOT NULL
├── slug                  VARCHAR(30) UNIQUE NOT NULL
├── cor                   VARCHAR(7)                    -- HEX color
└── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### Tabela: `notificacao_fatos`

```sql
notificacao_fatos
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── notificacao_id        INT NOT NULL                   -- FK → notificacoes.id
├── sequencia             INT NOT NULL                   -- Ordem de exibição
├── descricao             TEXT NOT NULL
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(notificacao_id)
```

### Tabela: `notificacao_imagens`

```sql
notificacao_imagens
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── notificacao_id        INT NOT NULL                   -- FK → notificacoes.id
├── caminho               VARCHAR(500) NOT NULL           -- Caminho do arquivo
├── nome_original         VARCHAR(255)                   -- Nome original
├── tipo                  VARCHAR(50)                    -- imagem, documento
├── inactive              BOOLEAN DEFAULT FALSE           -- Soft delete
├── deleted_at            TIMESTAMP
├── ocorrencia_id         INT                            -- Origem: ocorrência
├── anexo_ocorrencia_id   INT                            -- Origem: anexo específico
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(notificacao_id, inactive)
```

### Tabela: `notificacao_artigos`

```sql
notificacao_artigos
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── notificacao_id        INT NOT NULL                   -- FK → notificacoes.id
├── artigo_id             INT NOT NULL                   -- FK → artigos.id
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── UNIQUE(notificacao_id, artigo_id)
```

### Tabela: `artigos`

```sql
artigos
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── codigo                 VARCHAR(50) NOT NULL          -- ex: 'Art. 42'
├── titulo                VARCHAR(255)                   -- Título do artigo
├── conteudo              TEXT                            -- Texto completo
├── regimento_id          INT                             -- FK → regimentos.id
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(regimento_id)
```

### Tabela: `regimentos`

```sql
regimentos
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── nome                  VARCHAR(255) NOT NULL
├── arquivo_json          TEXT                            -- JSON com artigos
├── ativo                 BOOLEAN DEFAULT TRUE
├── created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── updated_at            TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Tabela: `evidencia_compartilhada`

```sql
evidencia_compartilhada
├── id                    INT PRIMARY KEY AUTO_INCREMENT
├── ocorrencia_id         INT NOT NULL                   -- FK → ocorrencias.id
├── ocorrencia_anexo_id   INT                             -- FK → ocorrencia_anexos.id
├── notificacao_id        INT NOT NULL                   -- FK → notificacoes.id
├── notificacao_imagem_id INT                             -- FK → notificacao_imagens.id
├── sincronizado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
└── INDEX(ocorrencia_id, notificacao_id)
```

---

## Histórico de Alterações

| Data | Alteração |
|------|-----------|
| 03/04/2026 | Versão inicial com 4 status básicos |
| 04/04/2026 | Adicionado vínculo com ocorrências |
| 04/04/2026 | Adicionado suporte a artigos do regimento |
| 04/04/2026 | Adicionado soft delete de imagens |

---

## API Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/notificacoes.php` | Lista notificações |
| GET | `/api/notificacoes.php?id=X` | Busca notificação por ID |
| GET | `/api/notificacoes.php?status=X` | Filtra por status |
| POST | `/api/notificacoes.php` | Cria/Edita notificação |
| POST | `/api/notificacoes.php?upload=1` | Upload de imagem |
| POST | `/api/notificacoes.php?sincronizar=1` | Sincroniza evidências da ocorrência |
| POST | `{ "lavrar": true }` | Lava (assina) notificação |
| POST | `{ "revogar": true }` | Revoga assinatura |
| POST | `{ "registrar_ciencia": true }` | Registra ciência |
| GET | `/api/gerar_pdf.php?id=X` | Gera PDF |

---

## Funcionalidades por Tipo de Usuário

```
┌────────────────┬───────────────────────────────────────────────────────┐
│ Papel          │ Capacidades                                           │
├────────────────┼───────────────────────────────────────────────────────┤
│ notificador    │ • Criar notificações                                  │
│                │ • Editar rascunhos                                   │
│                │ • Adicionar fatos                                     │
│                │ • Sincronizar evidências da ocorrência                │
│                │ • Vincular artigos do regimento                       │
│                │ • Solicitar lavratura                                │
├────────────────┼───────────────────────────────────────────────────────┤
│ assinador      │ • Ver notificações lavradas                          │
│                │ • Assinar (lavrar) notificações                      │
│                │ • Revogar assinatura                                 │
│                │ • Gerar PDF                                          │
├────────────────┼───────────────────────────────────────────────────────┤
│ despachante    │ • Ver notificações lavradas                          │
│                │ • Marcar como enviada                                │
│                │ • Gerar PDF                                          │
├────────────────┼───────────────────────────────────────────────────────┤
│ mensageiro     │ • Ver notificações enviadas                          │
│                │ • Registrar ciência do morador                       │
│                │ • Gerar PDF                                          │
├────────────────┼───────────────────────────────────────────────────────┤
│ admin          │ • Todas as capacidades                                │
│                │ • Encerrar notificações                              │
│                │ • Gerenciar configurações                            │
├────────────────┼───────────────────────────────────────────────────────┤
│ dev            │ • **MODO DEUS** - Acesso total                        │
└────────────────┴───────────────────────────────────────────────────────┘
```

---

## Status da Implementação

- [x] CRUD completo de notificações
- [x] Status: rascunho, em_elaboracao, pendente_assinatura, lavrada
- [x] Vinculação com ocorrências
- [x] Cópia/sincronização de evidências da ocorrência
- [x] Artigos do regimento
- [x] Tipos de notificação
- [x] Soft delete de imagens
- [ ] Campo data_ciencia
- [ ] Campo data_lavratura
- [ ] Lavratura (assinatura)
- [ ] Revogação de assinatura
- [ ] Registro de ciência
- [ ] Status estendido (enviada, ciente, em_recurso, encerrada)
- [ ] Sistema de prazos
- [ ] Geração de PDF melhorada
- [ ] API para sistema do conselho (pareceres)
