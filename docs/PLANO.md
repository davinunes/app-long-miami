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

- [ ] Criar migrate de ocorrências
- [ ] Criar API CRUD de ocorrências
- [ ] Implementar endpoints de unidades
- [ ] Criar sistema de mensagens (chat)
- [ ] Implementar upload de anexos
- [ ] Criar frontend de ocorrências

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
│   └── 002_grupos_papeis.sql      ✓
├── api/
│   ├── usuarios.php                 ✓
│   ├── grupos.php                  ✓
│   ├── notificacoes.php            ✓
│   ├── config.php                  ✓
│   └── verificar_token.php         ✓
├── js/
│   ├── main.js                     ✓ (atualizado com funções de usuários/grupos)
│   └── funcs.js                    ✓
├── usuarios.php                    ✓ (frontend de usuários)
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

- O grupo `dev` é hardcoded com acesso total em `verificar_token.php`
- Todas as APIs devem usar autenticação JWT
- Frontend usa padrão SPA com AJAX
- Migrate 002 executado no servidor (10.0.0.208)

---

## Status: FASE 1 CONCLUÍDA ✓

### Implementado em 03/04/2026:

**Backend:**
- `api/grupos.php` - CRUD completo de grupos
- `api/usuarios.php` - Atualizado com suporte a grupos/papéis
- `api/config.php` - Retorna papéis e grupos disponíveis
- `verificar_token.php` - Verifica papéis do banco

**Frontend:**
- `usuarios.php` - Interface com modais de usuário e grupos
- `js/main.js` - Funções: carregarListaUsuarios, abrirModalUsuario, salvarUsuarioModal, carregarListaGrupos, criarGrupo, editarGrupo, deletarGrupo
