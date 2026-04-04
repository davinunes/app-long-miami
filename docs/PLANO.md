# Plano de Desenvolvimento - Sistema App Long Miami

## Visão Geral
Sistema de gestão de notificações condominiais com controle de ocorrências, evidências e workflow de aprovação.

---

## FASE 1: Sistema de Usuários, Papéis e Grupos

### 1.1 - Papéis (Roles) do Sistema

| Papel (slug) | Descrição |
|--------------|-----------|
| `protocolar` | Cadastra ocorrências no sistema |
| `diligente` | Adiciona arquivos e comentários às ocorrências |
| `notificador` | Gera notificações e vincula ocorrências homologadas |
| `promotor` | Homologa ocorrências e adiciona evidências |
| `assinador` | Lavra notificações (aprova para envio) |
| `despachante` | Acessa notificações lavradas para envio |
| `mensageiro` | Registra ciência de notificações |
| `admin` | Administrador total |
| `dev` | Modo deus (hardcoded) |

### 1.2 - Grupos de Usuários

- Grupos permitem agrupar papéis
- Usuários podem pertencer a múltiplos grupos
- Facilitam a administração de permissões

**Grupos Padrão:**
- `Conselho` - promotors + assinadores
- `Fiscal` - fiscal + protocolar
- `Operacional` - diligente + mensageiro
- `Gerencial` - notificador + promotor + assinador

### 1.3 - Estrutura de Tabelas

```
grupos (id, nome, descricao, created_at)
grupo_papeis (grupo_id, papel)
usuarios (já existe) - adicionar grupo_id principal
usuario_grupos (usuario_id, grupo_id)
```

### 1.4 - Tarefas

- [x] Criar migrate para grupos e papéis (002_grupos_papeis.sql)
- [x] Criar CRUD de grupos (api/grupos.php)
- [x] Criar CRUD de usuários com gestão de grupos (api/usuarios.php)
- [x] Implementar middleware de verificação de papel (verificar_token.php)
- [x] Frontend de usuários com modais de grupos/papéis (usuarios.php + js/main.js)

---

## FASE 2: Sistema de Ocorrências

### 2.1 - Conceito
Uma ocorrência é um fato relatado por um porteiro, fiscal, subsíndico ou síndico. Inclui evidências coletadas via CFTV e mensagens em formato de chat.

### 2.2 - Estrutura de Dados

```
ocorrencias
├── id
├── titulo
├── descricao_fato
├── data_fato (data em que o fato ocorreu)
├── data_criacao (data do registro)
├── fase (nova, em_analise, recusada, homologada)
├── created_by (usuario que criou)
├── created_at
└── updated_at

ocorrencia_unidades (vínculo N:N)
├── ocorrencia_id
├── unidade_bloco (A-F)
└── unidade_numero (101-1912)

ocorrencia_mensagens (estrutura de chat)
├── id
├── ocorrencia_id
├── usuario_id
├── mensagem
├── eh_evidencia (boolean)
├── tipo_anexo (imagem, video, audio, link)
├── anexo_url
├── created_at

ocorrencia_anexos (arquivos avulsos)
├── id
├── ocorrencia_id
├── usuario_id
├── tipo (imagem, video, audio, documento, link)
├── url
├── nome_original
├── created_at
```

### 2.3 - Regras de Negócio

- **Fase `nova`**: Apenas edição por quem criou
- **Fase `em_analise`**: Diligentes podem adicionar evidências
- **Fase `homologada`**: Bloqueia novas evidências
- **Fase `recusada`**: Ocorrência rejeitada, histórico mantido

### 2.4 - Tarefas

- [x] Criar migrate de ocorrências (003_ocorrencias.sql)
- [x] Criar API CRUD de ocorrências (api/ocorrencias.php)
- [x] Implementar endpoints de unidades (api/unidades.php)
- [x] Criar sistema de mensagens (chat) - via api/ocorrencias.php
- [x] Implementar upload de anexos - via api/ocorrencias.php
- [x] Criar frontend de ocorrências (ocorrencias.php)

---

## FASE 3: Vinculação Ocorrência → Notificação

### 3.1 - Conceito
A partir de ocorrências homologadas, gerar notificações vinculando os fatos.

### 3.2 - Estrutura de Dados

```
notificacao_ocorrencias
├── notificacao_id
└── ocorrencia_id
```

### 3.3 - Fluxo

1. Ocorrência homologada aparece em fila
2. Notificador seleciona ocorrências
3. Sistema gera notificação com fatos das ocorrências
4. Fatos são copiados para `notificacao_fatos`

### 3.4 - Tarefas

- [ ] Criar tabela de vínculo
- [ ] Criar endpoint para listar ocorrências homologadas
- [ ] Criar endpoint para vincular ocorrências à notificação
- [ ] Implementar lógica de copiar fatos

---

## FASE 4: Sistema de Notificações (Melhorias)

### 4.1 - Estrutura Atual (já existe)

```
notificacoes
├── numero (sequencial/ano, ex: 76/2026)
├── unidade, bloco
├── tipo_id (advertência, multa)
├── status_id
├── ...
```

### 4.2 - Adicionar Campos

| Campo | Descrição |
|-------|-----------|
| `data_ciencia` | Data em que o morador tomou ciência |
| `data_lavratura` | Data em que foi lavrada (assinada) |
| `lavrada_por` | Usuário que lavrou |
| `tem_recurso` | Boolean - recurso interposto |
| `prazo_recurso_expira` | Data de expiração do prazo |

### 4.3 - Status Expandidos

- `rascunho` - Notificação em criação
- `lavrada` - Assinada pelo síndico
- `ciente` - Morador tomou ciência
- `em_recurso` - Recurso interposto
- `cobrar` - Prazo vencido sem recurso
- `encerrada` - Arquivada

### 4.4 - Tarefas

- [ ] Migrar estrutura de notificações
- [ ] Criar endpoint de ciência
- [ ] Criar lógica de prazo e sinalizações
- [ ] Atualizar frontend de notificações

---

## FASE 5: Sistema de Mensageria e Despacho

### 5.1 - Conceito
Notificações lavradas ficam disponíveis para despachante enviar (impressão, envelopamento, postagem).

### 5.2 - Fluxo

1. Assinador lava notificação (muda status)
2. Despachante acessa lista de lavradas
3. Despachante marca como enviada
4. Mensageiro registra ciência

### 5.3 - Tarefas

- [ ] Criar endpoint de notificações lavradas
- [ ] Criar endpoint de registro de ciência
- [ ] Criar lógica de prazos (cobrar/em prazo)
- [ ] Atualizar frontend de despacho

---

## FASE 6: API para Sistema do Conselho

### 6.1 - Conceito
API REST para integração com sistema do conselho.

### 6.2 - Endpoints

```
GET /api/v1/notificacoes/{numero_ano}
GET /api/v1/notificacoes/{numero_ano}/fatos
GET /api/v1/notificacoes/{numero_ano}/evidencias
POST /api/v1/notificacoes/{numero_ano}/parecer (futuro)
```

### 6.3 - Campos para Sincronização

```
pareceres (tabela para receber dados externos)
├── id
├── notificacao_id
├── numero_parecer
├── data_parecer
├── texto_parecer
├── responsavel
├── criado_em
└── sincronizado_em
```

### 6.4 - Tarefas

- [ ] Criar migrate de pareceres
- [ ] Criar endpoints da API v1
- [ ] Implementar autenticação por API Key
- [ ] Documentar API (OpenAPI/Swagger)

---

## Arquivos do Projeto

```
app-long-miami/
├── migrates/
│   ├── 001_initial_schema.sql      ✓
│   ├── 002_grupos_papeis.sql       ✓
│   └── 003_ocorrencias.sql        ✓
├── api/
│   ├── usuarios.php                 ✓
│   ├── grupos.php                  ✓
│   ├── notificacoes.php            ✓
│   ├── ocorrencias.php            ✓
│   ├── unidades.php               ✓
│   ├── config.php                  ✓
│   └── verificar_token.php        ✓
├── js/
│   ├── main.js                     ✓
│   └── funcs.js                    ✓
├── usuarios.php                    ✓
├── ocorrencias.php                ✓
├── verificar_token.php             ✓
└── docs/
    └── PLANO.md                   ✓
```

---

## Priorização Sugerida

1. **FASE 1** - Sistema de usuários completo (segurança base)
2. **FASE 2** - Ocorrências (coração do sistema)
3. **FASE 3** - Vinculação (conecta os módulos)
4. **FASE 4** - Notificações melhoradas
5. **FASE 5** - Mensageria
6. **FASE 6** - API (pode ser feito параллельно)

---

## Notas

- Autenticação via **Sessões PHP** (não JWT) - mais simples e fácil de manter
- Arquivo `auth.php` contém funções: `estaLogado()`, `temPapel()`, `requireLogin()`, `requirePapel()`
- APIs usam `api/helpers.php` para verificar sessão
- Frontend PHP com proteção via `requireLogin()` ou `requirePapel()`

---

## Status: REFATORAÇÃO CONCLUÍDA (03/04/2026)

### Nova Arquitetura de Autenticação

**Arquivos:**
- `auth.php` - Funções de autenticação via sessão PHP
- `api/helpers.php` - Helper para APIs com sessão
- `index.php` - Login via POST (sem JS JWT)
- `logout.php` - Logout simples
- `dashboard_content.php` - Dashboard protegido
- `lista.php`, `usuarios.php`, `ocorrencias.php` - Protegidos com requireLogin/requirePapel

**Vantagens:**
- Sem JWT para gerenciar no frontend
- Sessão PHP disponível em todas as páginas
- Depuração direta com var_dump/echo
- Menos código JavaScript

---

## Status: FASE 1 CONCLUÍDA ✓

**Backend:**
- `api/grupos.php` - CRUD completo de grupos
- `api/usuarios.php` - Com suporte a grupos/papéis
- `api/config.php` - Retorna papéis e grupos

**Frontend:**
- `usuarios.php` - Interface com modais de usuário e grupos
- `js/main.js` - Funções de CRUD

---

## Status: FASE 2 CONCLUÍDA ✓

**Database (003_ocorrencias.sql):**
- `ocorrencias` - Fases: nova, em_analise, recusada, homologada
- `ocorrencia_unidades` - Unidades envolvidas
- `ocorrencia_mensagens` - Chat com evidências
- `ocorrencia_anexos` - Arquivos anexados
- `ocorrencia_fase_log` - Log de fases
- `unidades` - Catálogo

**APIs:**
- `api/ocorrencias.php` - CRUD com fases, mensagens, anexos
- `api/unidades.php` - CRUD de unidades

**Frontend:**
- `ocorrencias.php` - Lista com filtros
- `ocorrencia_detalhe.php` - Página completa com seções lineares

---

## Status: FASE 3 CONCLUÍDA ✓ (04/04/2026)

### Migração JWT → Sessões PHP

**Problema:** JWT adicionou complexidade desnecessária ao projeto.

**Solução:** Sessões PHP são mais simples de manter e depurar.

**Arquivos Modificados:**
- `auth.php` - Sistema de autenticação via sessão
- `api/helpers.php` - Verificação de sessão para APIs
- `api/gerar_pdf.php` - Atualizado para usar sessão PHP
- `index.php` - Login via POST (sem JS JWT)
- `logout.php` - Logout com destruição de sessão
- Todas as páginas protegidas - Atualizadas
- `js/main.js` - Removido código JWT, agora usa APIs PHP
- `js/funcs.js` - Funções compartilhadas atualizadas

### Funcionalidades Adicionadas

**1. Excluir Ocorrência (Admin only)**
- `DELETE` / `POST` endpoint em `api/ocorrencias.php`
- Remove arquivos do disco
- Limpa todas as referências no banco

**2. Excluir Anexos e Mensagens**
- Admin ou criador podem excluir (enquanto fase ≠ homologada)
- Remove arquivos do disco para anexos
- Endpoint: `deletar_anexo`, `deletar_mensagem`

**3. Histórico de Criação Automático**
- Ao criar ocorrência, registra automaticamente no `ocorrencia_fase_log`
- Registra: usuário, data/hora e observação

**4. Edição Condicional**
- Admin/dev sempre pode editar
- Criador pode editar enquanto fase ≠ homologada
- anexos/mensagens: admin ou criador podem excluir

### Migration 004: Vinculação Ocorrência → Notificação

**Adicionado:**
- `ocorrencia_id` na tabela `notificacoes`
- `notificacao_id` na tabela `ocorrencias`
- Tabela `ocorrencia_notificacoes` (histórico de vínculos)

**APIs atualizadas:**
- `api/notificacoes.php` - Aceita e armazena `ocorrencia_id`
- `api/ocorrencias.php` - Novo endpoint `gerar_notificacao`

**Frontend atualizado:**
- `ocorrencia_detalhe.php` - Mostra notificação vinculada e botão gerar

---

## Status: FEATURE - Vinculação de Evidências (04/04/2026)

### Migration 005: Vinculação de Evidências

**Estrutura de Dados:**
- `notificacao_imagens.inactive` - Flag para soft delete
- `notificacao_imagens.deleted_at` - Data da remoção
- `notificacao_imagens.ocorrencia_id` - Ocorrência de origem
- `notificacao_imagens.anexo_ocorrencia_id` - Anexo específico da ocorrência
- `evidencia_compartilhada` - Tabela para rastrear evidências compartilhadas

**APIs:**
- `buscarOcorrenciasParaVincular` - Lista ocorrências homologadas sem notificação
- `deletarImagem` - Soft delete para imagens de ocorrência, hard delete para imagens diretas
- `vincularEvidencias` - Vincula/desvincula evidências de ocorrências
- `buscarNotificacao` - Retorna evidências vinculadas e todas da ocorrência

**Frontend:**
- `ocorrencia_detalhe.php` - Botão "Criar Notificação" (papeis: notificador, admin, dev)
- `nova_not.php` - Pré-preenche com dados da ocorrência
- `_form.php` - Mostra evidências da ocorrência vinculada

**Regras de Negócio:**
1. Botão "Criar Notificação" só aparece para ocorrências homologadas e usuários com papel `notificador`, `admin` ou `dev`
2. Ao criar notificação a partir de ocorrência:
   - Pré-preenche: unidade, descrição
   - **Copia automaticamente** imagens da ocorrência para a notificação
   - Registra vínculo em `evidencia_compartilhada`
3. Imagens vinculadas a ocorrência → soft delete (flag `inactive=1`) quando removidas da notificação
4. Imagens uploadadas diretamente na notificação → hard delete (remove do disco)
5. Evidências compartilhadas são rastreadas na tabela `evidencia_compartilhada`

---

## Tarefas Pendentes

- [ ] Função de **editar mensagem** (não implementada)
- [ ] Migrar para sistema de permissões (ver `docs/permissões.md`)
- [ ] FASE 4: Notificações melhoradas (ver `docs/ciclo_vida_notificação.md`)
- [ ] FASE 5: Sistema de Despacho
- [ ] FASE 6: API para Conselho

### Concluído (04/04/2026)
- [x] Migration 005 - Vinculação de evidências
- [x] Botão "Criar Notificação" na ocorrência (papeis permitidos)
- [x] Soft delete para imagens de ocorrência
- [x] Hard delete para imagens diretas
- [x] Pré-preenchimento de notificação com dados da ocorrência
- [x] Exibição de evidências da ocorrência na notificação
- [x] Cópia de imagens da ocorrência para notificação (criarNotificacao atualizada)
- [x] Migration 009 - Sistema de Permissões

---

## Sistema de Permissões (MIGRAÇÃO 009)

### Estrutura de Dados

```
permissoes
├── id
├── slug                    -- ex: 'ocorrencia.criar'
├── nome                    -- ex: 'Criar Ocorrência'
├── descricao
├── modulo                  -- ex: 'ocorrencia', 'notificacao'
└── created_at

grupo_permissoes
├── grupo_id
└── permissao_id

usuario_permissoes
├── usuario_id
├── permissao_id
├── granted_by
└── granted_at
```

### Funções Disponíveis em auth.php

```php
// Verificar permissão
temPermissao('ocorrencia.criar');              // bool
temAlgumaPermissao(['a', 'b']);                 // bool
verificarDono('ocorrencias', $id);              // bool
podeEditar('ocorrencias', $id, 'editar_propria', 'editar'); // bool

// Requerer permissão
requirePermissao('ocorrencia.criar');
requireAlgumaPermissao(['a', 'b']);

// Legacy (mantido para compatibilidade)
temPapel('admin');                              // bool
requirePapel(['admin', 'dev']);
```

### API Endpoints

```
GET  /api/grupos.php?listar_permissoes=1       -- Lista todas permissões
GET  /api/grupos.php?modulos=1                 -- Lista módulos
GET  /api/config.php                           -- Inclui permissões
POST /api/grupos.php                            -- CRUD com permissões
```

### Próximos Passos

1. [x] Migration 009 criada
2. [x] Funções em auth.php implementadas
3. [x] APIs atualizadas
4. [ ] Atualizar telas uma a uma com permissões granulares
5. [ ] Remover dependência de papéis legacy

---

## Documentação Detalhada

Consulte os arquivos em `docs/` para informações detalhadas:

| Arquivo | Descrição |
|---------|-----------|
| `permissões.md` | Sistema de permissões granular proposto |
| `ciclo_vida_ocorrencia.md` | Ciclo de vida completo das ocorrências |
| `ciclo_vida_notificação.md` | Ciclo de vida completo das notificações |

### Resumo do Sistema de Permissões

O novo sistema substitui o controle por **papeis** por **permissões** mais granulares:

- Cada ação do sistema é uma permissão específica (ex: `ocorrencia.criar`, `notificacao.lavrar`)
- Grupos são associados a permissões (não mais a papéis)
- Usuários podem ter permissões individuais extras
- `dev` é o modo deus (todas as permissões automaticamente)
