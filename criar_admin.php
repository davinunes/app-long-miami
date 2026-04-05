<?php
/**
 * Script para criar o usuário Administrador padrão
 * 
 * Uso:
 *   - Navegador: acessar diretamente
 *   - CLI: php criar_admin.php
 */

require_once __DIR__ . '/config.php';

$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #eee; }
        .card { background: #16213e; padding: 25px; border-radius: 10px; margin: 20px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; color: #aaa; }
        input { width: 100%; padding: 10px; border: 1px solid #333; border-radius: 5px; background: #0f3460; color: #fff; box-sizing: border-box; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; margin-top: 15px; }
        .btn:hover { background: #5568d3; }
        .success { background: #28a745; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #dc3545; padding: 15px; border-radius: 8px; margin: 15px 0; }
        pre { background: #0f3460; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>👤 Criar Administrador</h1>
    <div class="card">
';
    
    $resultado = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nome = trim($_POST['nome'] ?? 'Administrador');
        
        if (empty($email) || empty($senha)) {
            $resultado = ['erro' => 'Email e senha são obrigatórios.'];
        } else {
            $resultado = criarAdmin($email, $senha, $nome);
        }
        
        if (isset($resultado['sucesso'])) {
            echo '<div class="success"><h3>✅ ' . htmlspecialchars($resultado['sucesso']) . '</h3></div>';
            if (isset($resultado['detalhes'])) {
                echo '<pre>' . htmlspecialchars($resultado['detalhes']) . '</pre>';
            }
        } else {
            echo '<div class="error"><h3>❌ Erro</h3><p>' . htmlspecialchars($resultado['erro']) . '</p></div>';
        }
    }
    
    echo '
        <form method="POST">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="nome" value="Administrador">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="admin@admin.com" required>
            </div>
            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="senha" placeholder="Mínimo 6 caracteres" required>
            </div>
            <button type="submit" class="btn">Criar Admin</button>
        </form>
    </div>
    <a href="dashboard_content.php" style="color: #667eea;">← Voltar ao Dashboard</a>
</body>
</html>
';
    exit;
}

function criarAdmin($email, $senha, $nome = 'Administrador') {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['erro' => 'Não foi possível conectar ao banco de dados.'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verificar se já existe usuário com esse email
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            return ['erro' => 'Já existe um usuário com este email.'];
        }
        
        // Criar ou verificar grupo Admin
        $stmt = $pdo->prepare("SELECT id FROM grupos WHERE nome = 'Admin'");
        $stmt->execute();
        $grupoAdmin = $stmt->fetch();
        
        if (!$grupoAdmin) {
            $stmt = $pdo->prepare("INSERT INTO grupos (nome, descricao) VALUES ('Admin', 'Grupo de administradores')");
            $stmt->execute();
            $grupoAdminId = $pdo->lastInsertId();
        } else {
            $grupoAdminId = $grupoAdmin['id'];
        }
        
        // Criar usuário
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Verificar se a coluna é 'password' ou 'senha'
        $stmtCheck = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'password'");
        $colunaSenha = $stmtCheck->fetch() ? 'password' : 'senha';
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, {$colunaSenha}, role, created_at) 
            VALUES (?, ?, ?, 'admin', NOW())
        ");
        $stmt->execute([$nome, $email, $senhaHash]);
        $usuarioId = $pdo->lastInsertId();
        
        // Vincular usuário ao grupo Admin
        $stmt = $pdo->prepare("INSERT INTO grupo_usuarios (grupo_id, usuario_id) VALUES (?, ?)");
        $stmt->execute([$grupoAdminId, $usuarioId]);
        
        // Atribuir todas as permissões ao grupo Admin
        $stmt = $pdo->prepare("SELECT id FROM permissoes");
        $stmt->execute();
        $permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtLink = $pdo->prepare("INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id) VALUES (?, ?)");
        foreach ($permissoes as $permissaoId) {
            $stmtLink->execute([$grupoAdminId, $permissaoId]);
        }
        
        $pdo->commit();
        
        return [
            'sucesso' => 'Administrador criado com sucesso!',
            'detalhes' => "Usuário: {$email}\nGrupos: Admin\nPermissões atribuídas: " . count($permissoes)
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['erro' => 'Erro ao criar admin: ' . $e->getMessage()];
    }
}

// CLI mode
if ($argc < 3) {
    echo "Uso: php criar_admin.php <email> <senha> [nome]\n";
    echo "Exemplo: php criar_admin.php admin@admin.com minhaSenha123\n";
    exit(1);
}

$email = $argv[1];
$senha = $argv[2];
$nome = $argv[3] ?? 'Administrador';

echo "Criando admin...\n";
$resultado = criarAdmin($email, $senha, $nome);

if (isset($resultado['sucesso'])) {
    echo "\n✅ " . $resultado['sucesso'] . "\n";
    echo $resultado['detalhes'] . "\n";
} else {
    echo "\n❌ Erro: " . $resultado['erro'] . "\n";
    exit(1);
}
