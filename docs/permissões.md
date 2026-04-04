# Sistema de Permissões - App Long Miami
*Status: Implementado e Ativo em Produção (Abril/2026)*

## Conceito

O sistema utiliza um modelo **RBAC (Role-Based Access Control)** flexível, onde as permissões granulares são atribuídas a **Grupos**, e usuários são vinculados a um ou mais grupos.

### Governança de Acessos

O sistema abandonou a lógica de "papéis fixos" em código. Atualmente, a estrutura funciona assim:

1.  **Permissões**: Capacidades individuais (ex: `notificacao.lavrar`). Estão cadastradas na tabela `permissoes`.
2.  **Grupos**: Entidades criadas dinamicamente pelo Administrador (ex: "Conselho Fiscal", "Portaria B").
3.  **Vínculo Permissão-Grupo**: Define o que cada grupo pode fazer.
4.  **Vínculo Usuário-Grupo**: Define quais capacidades o usuário herda.

> [!IMPORTANT]
> **Admin e Dev (Super-Usuários)**: Os papéis `admin` e `dev` são tratados como exceções sistêmicas ("Modo Deus"). Usuários com esses papéis possuem bypass total em qualquer verificação de permissão.

---

## Catálogo de Permissões Disponíveis

### 1. Notificações e Ciclo de Vida
Essas permissões controlam o fluxo oficial de documentos.

| Permissão | Ação Relacionada |
|-----------|------------------|
| `notificacao.criar` | Criar um novo rascunho. |
| `notificacao.editar` | Editar conteúdo de rascunhos. |
| `notificacao.lavrar` | **Assinar** a notificação (bloqueia o form). |
| `notificacao.marcar_enviada` | Registrar despacho do documento. |
| `notificacao.registrar_ciencia` | Definir data de recebimento pelo morador. |
| `notificacao.registrar_recurso` | Anexar a contestação do morador. |
| `notificacao.julgar_recurso` | Deferir ou indeferir o recurso. |
| `notificacao.marcar_cobranca` | Disponibilizar para o financeiro. |
| `notificacao.encerrar` | Confirmar que a multa foi lançada. |
| `notificacao.reabrir` | Reverter status de documentos encerrados. |

### 2. Ocorrências
Controle da coleta de evidências e triagem.

| Permissão | Descrição |
|-----------|-----------|
| `ocorrencia.criar` | Cadastrar novo fato. |
| `ocorrencia.homologar` | Validar que a ocorrência é verídica. |
| `ocorrencia.alterar_fase` | Mudar entre em_analise, diligencia, etc. |
| `ocorrencia.gerar_notificacao` | Criar rascunho de notificação a partir dela. |

### 3. Gestão Administrativa
Configurações sensíveis do sistema.

| Permissão | Descrição |
|-----------|-----------|
| `usuario.criar` | Adicionar novos colaboradores ao sistema. |
| `usuario.editar` | Alterar dados e grupos de outros usuários. |
| `grupo.gerenciar` | Criar grupos e definir suas permissões. |
| `configuracao.regimento` | Editar os artigos do regimento interno. |

---

## Como verificar permissões no código

O sistema injeta as permissões do usuário logado em uma constante global `PERMISSOES_USUARIO` no JavaScript (via `_partials/menu.php`) e fornece a função `temPermissao()` no PHP (via `auth.php`).

### No Backend (PHP)
```php
if (temPermissao('notificacao.lavrar')) {
    // executa lógica de assinatura
}
```

### No Frontend (JavaScript)
```javascript
if (temPermissao('notificacao.marcar_cobranca')) {
    // mostra o botão de cobrança
}
```

---

## Histórico de Implementação

- **Março/2026**: Migração total de Roles dinâmicas para Permissões RBAC.
- **Abril/2026**: Implementação do ciclo de vida completo de notificações (Lavratura -> Ciência -> Recurso -> Cobrança -> Encerramento).
- **Abril/2026**: Criação da permissão `notificacao.reabrir` para reversão de fluxos.
