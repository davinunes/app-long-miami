# Migrates - Banco de Dados

## Arquivos

- `001_initial_schema.sql` - Migration zero com todas as tabelas e dados iniciais

## Execução

Para executar a migração, use o MySQL/MariaDB CLI:

```bash
# Conectar ao banco
mysql -h 172.24.100.30 -u root -p app_db

# Executar a migração
source migrates/001_initial_schema.sql;
```

Ou via docker:

```bash
docker exec -i <container_mysql> mysql -u root -p app_db < migrates/001_initial_schema.sql
```

## Estrutura do Banco

### Tabelas

| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Usuários do sistema com autenticação JWT |
| `notificacoes` | Notificações principais |
| `notificacao_fatos` | Descrição dos fatos das notificações |
| `notificacao_imagens` | Evidências fotográficas |
| `assuntos` | Motivos/tipos de infração |
| `notificacao_tipos` | Tipos (Advertência, Multa, etc) |
| `notificacao_status` | Status das notificações |

### Roles de Usuário

- `admin` - Administrador total
- `dev` - Desenvolvedor
- `sindico` - Síndico
- `fiscal` - Fiscal
- `conselheiro` - Membro do conselho
- `condomino` - Condômino
- `pode_assinar` - Pode assinar documentos

### Usuário Padrão

- **Email:** admin@seusistema.com
- **Senha:** umaSenhaMuitoForte123!
- **Role:** admin
