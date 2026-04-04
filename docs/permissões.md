# Sistema de Permissões - App Long Miami

## Conceito

O sistema atual utiliza **papeis** (roles) para controle de acesso. A ideia é migrar para um sistema baseado em **permissões** (permissions) mais granular e flexível.

### Diferença Fundamental

| Abordagem | Descrição | Exemplo |
|-----------|-----------|---------|
| **Papeis (atual)** | Agrupamento fixo de capacidades | "promotor pode homologar" |
| **Permissões (futuro)** | Capacidade individual verificável | "pode_homologar_ocorrencia" |

### Papéis Existentes (Legacy)

| Papel | Descrição | Status |
|-------|-----------|--------|
| `protocolar` | Cadastra ocorrências | Ativo |
| `diligente` | Adiciona evidências | Ativo |
| `notificador` | Gera notificações | Ativo |
| `promotor` | Homologa ocorrências | Ativo |
| `assinador` | Lava notificações | Planejado |
| `despachante` | Envia notificações | Planejado |
| `mensageiro` | Registra ciência | Planejado |
| `admin` | Administrador total | Ativo |
| `dev` | **Modo Deus** (bypass total) | Ativo |

> **Nota:** O papel `dev` é o "modo deus" - tem todas as permissões por padrão.

---

## Sistema de Permissões Proposto

### 1. Permissões de Ocorrências

```
OCORRÊNCIAS
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ ocorrencia.criar                │ Criar nova ocorrência          │
│ ocorrencia.editar               │ Editar qualquer ocorrência     │
│ ocorrencia.editar_propria       │ Editar apenas ocorrências que   │
│                                 │ criou                          │
│ ocorrencia.excluir              │ Excluir qualquer ocorrência    │
│ ocorrencia.listar               │ Listar todas ocorrências       │
│ ocorrencia.listar_proprias      │ Listar apenas ocorrências que  │
│                                 │ criou                          │
│ ocorrencia.ver_detalhes         │ Ver detalhes de qualquer       │
│                                 │ ocorrência                     │
│ ocorrencia.alterar_fase         │ Alterar fase da ocorrência     │
│ ocorrencia.homologar            │ Homologar ocorrência (aceitar) │
│ ocorrencia.recusar              │ Recusar ocorrência             │
│ ocorrencia.gerar_notificacao    │ Gerar notificação a partir da  │
│                                 │ ocorrência                     │
└─────────────────────────────────┴────────────────────────────────┘
```

### 2. Permissões de Unidades em Ocorrências

```
UNIDADES DA OCORRÊNCIA
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ ocorrencia.unidade.vincular     │ Vincular unidade a ocorrência  │
│ ocorrencia.unidade.remover      │ Remover unidade de ocorrência  │
│ ocorrencia.unidade.editar       │ Editar unidades de qualquer    │
│                                 │ ocorrência                     │
└─────────────────────────────────┴────────────────────────────────┘
```

### 3. Permissões de Mensagens e Evidências

```
MENSAGENS E EVIDÊNCIAS
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ ocorrencia.mensagem.criar       │ Adicionar mensagem            │
│ ocorrencia.mensagem.editar       │ Editar qualquer mensagem      │
│ ocorrencia.mensagem.editar_propria │ Editar apenas mensagem que  │
│                                   │ criou                        │
│ ocorrencia.mensagem.excluir      │ Excluir qualquer mensagem    │
│ ocorrencia.mensagem.excluir_propria │ Excluir mensagem que criou │
│ ocorrencia.evidencia.marcar      │ Marcar mensagem como evidência│
│ ocorrencia.evidencia.anexar      │ Anexar evidência (arquivo)    │
│ ocorrencia.evidencia.link        │ Adicionar link como evidência │
│ ocorrencia.evidencia.excluir     │ Excluir evidência             │
└─────────────────────────────────┴────────────────────────────────┘
```

### 4. Permissões de Anexos

```
ANEXOS
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ ocorrencia.anexo.criar           │ Anexar arquivo                 │
│ ocorrencia.anexo.excluir         │ Excluir anexo                  │
│ ocorrencia.anexo.excluir_proprio │ Excluir apenas anexos que     │
│                                   │ criou                         │
│ ocorrencia.link.criar            │ Adicionar link como anexo      │
│ ocorrencia.link.excluir         │ Excluir link                   │
└─────────────────────────────────┴────────────────────────────────┘
```

### 5. Permissões de Notificações

```
NOTIFICAÇÕES
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ notificacao.criar                │ Criar notificação               │
│ notificacao.editar               │ Editar qualquer notificação     │
│ notificacao.editar_propria       │ Editar apenas notificações que  │
│                                 │ criou                          │
│ notificacao.excluir              │ Excluir notificação            │
│ notificacao.listar               │ Listar notificações            │
│ notificacao.ver                  │ Ver detalhes de notificação    │
│ notificacao.lavrar               │ Lavrar (assinar) notificação    │
│ notificacao.revogar_assinatura  │ Revogar assinatura             │
│ notificacao.registrar_ciencia    │ Registrar ciência do morador    │
│ notificacao.gerar_pdf            │ Gerar PDF da notificação       │
└─────────────────────────────────┴────────────────────────────────┘
```

### 6. Permissões de Notificação - Imagens

```
IMAGENS DA NOTIFICAÇÃO
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ notificacao.imagem.anexar        │ Anexar imagem                  │
│ notificacao.imagem.sincronizar   │ Sincronizar imagens da        │
│                                 │ ocorrência                     │
│ notificacao.imagem.remover       │ Remover imagem                │
│ notificacao.imagem.ativar        │ Reativar imagem removida       │
└─────────────────────────────────┴────────────────────────────────┘
```

### 7. Permissões de Notificação - Conteúdo

```
CONTEÚDO DA NOTIFICAÇÃO
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ notificacao.assunto.editar       │ Editar assunto                │
│ notificacao.tipo.editar          │ Editar tipo (advertência,etc) │
│ notificacao.fato.adicionar       │ Adicionar fato                │
│ notificacao.fato.editar          │ Editar fato                   │
│ notificacao.fato.remover         │ Remover fato                  │
│ notificacao.artigo.vincular      │ Vincular artigo do regimento  │
│ notificacao.artigo.desvincular   │ Desvincular artigo            │
└─────────────────────────────────┴────────────────────────────────┘
```

### 8. Permissões de Configurações

```
CONFIGURAÇÕES
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ configuracao.acessar             │ Acessar página de config.     │
│ configuracao.condominio.editar   │ Editar dados do condomínio    │
│ configuracao.sindico.gerenciar   │ Gerenciar síndicos             │
│ configuracao.regimento.editar    │ Editar regimento interno       │
└─────────────────────────────────┴────────────────────────────────┘
```

### 9. Permissões de Usuários

```
USUÁRIOS
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ usuario.listar                   │ Listar usuários               │
│ usuario.ver_detalhes            │ Ver detalhes de usuário       │
│ usuario.criar                    │ Criar usuário                 │
│ usuario.editar                   │ Editar usuário                │
│ usuario.editar_papeis            │ Alterar papéis do usuário     │
│ usuario.editar_grupo             │ Alterar grupo do usuário      │
│ usuario.excluir                  │ Excluir usuário               │
│ usuario.trocar_senha             │ Trocar senha de qualquer user │
└─────────────────────────────────┴────────────────────────────────┘
```

### 10. Permissões de Grupos

```
GRUPOS
═══════════════════════════════════════════════════════════════════
│ Permissão                        │ Descrição                      │
├─────────────────────────────────┼────────────────────────────────┤
│ grupo.listar                     │ Listar grupos                 │
│ grupo.criar                      │ Criar grupo                   │
│ grupo.editar                     │ Editar grupo                  │
│ grupo.excluir                    │ Excluir grupo                │
│ grupo.gerenciar_papeis           │ Associar/desassociar papéis   │
└─────────────────────────────────┴────────────────────────────────┘
```

---

## Mapeamento: Papéis → Permissões Padrão

Ao migrar, cada papel terá um conjunto de permissões:

```
┌──────────────────┬─────────────────────────────────────────────────┐
│ Papel            │ Permissões                                     │
├──────────────────┼─────────────────────────────────────────────────┤
│ protocolar       │ ocorrencia.criar                               │
│                  │ ocorrencia.editar_propria                      │
│                  │ ocorrencia.unidade.vincular                    │
│                  │ ocorrencia.mensagem.criar                      │
│                  │ ocorrencia.mensagem.editar_propria             │
│                  │ ocorrencia.anexo.criar                         │
│                  │ ocorrencia.anexo.excluir_proprio              │
│                  │ ocorrencia.link.criar                          │
├──────────────────┼─────────────────────────────────────────────────┤
│ diligente        │ ocorrencia.listar                              │
│                  │ ocorrencia.ver_detalhes                        │
│                  │ ocorrencia.mensagem.criar                      │
│                  │ ocorrencia.evidencia.anexar                    │
│                  │ ocorrencia.evidencia.link                      │
│                  │ ocorrencia.anexo.criar                         │
├──────────────────┼─────────────────────────────────────────────────┤
│ promotor         │ ocorrencia.listar                              │
│                  │ ocorrencia.ver_detalhes                        │
│                  │ ocorrencia.alterar_fase                        │
│                  │ ocorrencia.homologar                           │
│                  │ ocorrencia.recusar                             │
│                  │ ocorrencia.mensagem.criar                      │
│                  │ ocorrencia.evidencia.marcar                    │
│                  │ ocorrencia.evidencia.anexar                    │
│                  │ ocorrencia.unidade.vincular                    │
│                  │ ocorrencia.unidade.remover                      │
├──────────────────┼─────────────────────────────────────────────────┤
│ notificador      │ ocorrencia.listar                              │
│                  │ ocorrencia.ver_detalhes                        │
│                  │ ocorrencia.gerar_notificacao                   │
│                  │ notificacao.criar                              │
│                  │ notificacao.editar_propria                     │
│                  │ notificacao.imagem.anexar                      │
│                  │ notificacao.imagem.sincronizar                  │
│                  │ notificacao.fato.adicionar                     │
│                  │ notificacao.artigo.vincular                    │
├──────────────────┼─────────────────────────────────────────────────┤
│ assinador        │ notificacao.listar                             │
│                  │ notificacao.ver                               │
│                  │ notificacao.lavrar                             │
│                  │ notificacao.revogar_assinatura                 │
├──────────────────┼─────────────────────────────────────────────────┤
│ despachante      │ notificacao.listar                             │
│                  │ notificacao.ver                               │
│                  │ notificacao.gerar_pdf                          │
├──────────────────┼─────────────────────────────────────────────────┤
│ mensageiro       │ notificacao.listar                             │
│                  │ notificacao.ver                               │
│                  │ notificacao.registrar_ciencia                  │
├──────────────────┼─────────────────────────────────────────────────┤
│ admin            │ * (todas as permissões)                        │
├──────────────────┼─────────────────────────────────────────────────┤
│ dev              │ * (todas as permissões + bypass total)          │
└──────────────────┴─────────────────────────────────────────────────┘
```

---

## Estrutura de Dados Proposta

### Tabela: `permissoes`
```sql
CREATE TABLE permissoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,      -- ex: 'ocorrencia.criar'
    nome VARCHAR(200) NOT NULL,              -- ex: 'Criar Ocorrência'
    descricao TEXT,                          -- Descrição detalhada
    modulo VARCHAR(50) NOT NULL,             -- ex: 'ocorrencia', 'notificacao'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabela: `papel_permissoes` (associação papel → permissões)
```sql
CREATE TABLE papel_permissoes (
    papel_slug VARCHAR(50) NOT NULL,
    permissao_id INT NOT NULL,
    PRIMARY KEY (papel_slug, permissao_id),
    FOREIGN KEY (papel_slug) REFERENCES papeles(slug),
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id)
);
```

### Tabela: `usuario_permissoes` (permissões individuais)
```sql
CREATE TABLE usuario_permissoes (
    usuario_id INT NOT NULL,
    permissao_id INT NOT NULL,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, permissao_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id)
);
```

---

## Função de Verificação Proposta

```php
/**
 * Verifica se o usuário tem uma permissão específica
 * 
 * @param int $usuarioId ID do usuário
 * @param string $permissao Slug da permissão (ex: 'ocorrencia.criar')
 * @param array $context Contexto adicional (ex: ['ocorrencia_id' => 5])
 * @return bool
 */
function temPermissao($usuarioId, $permissao, $context = []) {
    $usuario = getUsuario($usuarioId);
    
    // DEV sempre tem tudo
    if (in_array('dev', $usuario['papeis'])) {
        return true;
    }
    
    // Verifica permissão individual do usuário
    if (verificarPermissaoUsuario($usuarioId, $permissao)) {
        return true;
    }
    
    // Verifica se algum papel do usuário tem a permissão
    foreach ($usuario['papeis'] as $papel) {
        if (verificarPermissaoPapel($papel, $permissao)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Verifica se o usuário é dono do recurso (para *_propria permissões)
 */
function verificarDono($usuarioId, $recurso, $recursoId) {
    $stmt = pdo()->prepare("
        SELECT created_by FROM {$recurso} WHERE id = ?
    ");
    $stmt->execute([$recursoId]);
    $registro = $stmt->fetch();
    
    return $registro && $registro['created_by'] == $usuarioId;
}
```

---

## Uso no Frontend

```php
<?php if (temPermissao($usuario['id'], 'ocorrencia.criar')): ?>
    <a href="nova_ocorrencia.php" class="btn">Nova Ocorrência</a>
<?php endif; ?>

<?php if (temPermissao($usuario['id'], 'ocorrencia.unidade.vincular', ['ocorrencia_id' => $id])): ?>
    <button onclick="adicionarUnidade()">+ Vincular Unidade</button>
<?php endif; ?>
```

---

## Migração Proposta

1. Criar tabelas `permissoes`, `papel_permissoes`, `usuario_permissoes`
2. Popular com permissões e mapeamentos
3. Criar função `temPermissao()` em `auth.php`
4. Criar helper `requirePermissao()` para APIs
5. Substituir gradualmente `requirePapel()` por `requirePermissao()`
6. Atualizar frontends para usar `temPermissao()`
7. Remover dependência de papéis para permissões (roles são apenas atalhos)

---

## Status

- [x] Levantamento de permissões necessárias
- [ ] Criar migrate de permissões
- [ ] Criar função `temPermissao()`
- [ ] Mapear papéis → permissões
- [ ] Implementar em APIs
- [ ] Implementar em frontends
- [ ] Remover dependência antiga
