<?php
/**
 * Debug page - mostra usuário logado e todas as permissões
 * Acessar: /debug_permissoes.php
 */

require_once 'auth.php';
requireLogin();

$pdo = getDbConnection();
$usuario = getUsuario();

echo "<html><head><title>Debug - Permissões</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
    h1 { color: #00ff88; }
    h2 { color: #ff6b6b; margin-top: 30px; }
    pre { background: #16213e; padding: 15px; border-radius: 8px; overflow-x: auto; }
    .info { background: #0f3460; padding: 15px; border-radius: 8px; margin: 10px 0; }
    .erro { background: #4a1c1c; padding: 15px; border-radius: 8px; color: #ff6b6b; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #333; padding: 8px; text-align: left; }
    th { background: #0f3460; }
    tr:nth-child(even) { background: #16213e; }
    a { color: #00ff88; }
</style></head><body>";

echo "<h1>🔍 Debug de Permissões</h1>";

// Dados do usuário
echo "<h2>📋 Dados do Usuário (Sessão)</h2>";
echo "<pre>";
print_r($usuario);
echo "</pre>";

// Verificar se é admin/dev pelo role
$ehAdminDev = ($usuario['role'] === 'dev' || $usuario['role'] === 'admin');
echo "<div class='info'>";
echo "<strong>eh_admin_ou_dev (pelo role):</strong> " . ($ehAdminDev ? '✅ SIM' : '❌ NÃO') . "<br>";
echo "<strong>role:</strong> " . ($usuario['role'] ?? 'null') . "<br>";
echo "<strong>permissoes na sessão:</strong> " . count($usuario['permissoes'] ?? []) . " permissões<br>";
echo "</div>";

// Grupos do usuário no banco
$stmt = $pdo->prepare("
    SELECT g.id, g.nome, g.descricao 
    FROM usuario_grupos ug 
    JOIN grupos g ON ug.grupo_id = g.id 
    WHERE ug.usuario_id = ?
");
$stmt->execute([$usuario['id']]);
$grupos = $stmt->fetchAll();

echo "<h2>👥 Grupos do Usuário (Banco)</h2>";
if (count($grupos) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th></tr>";
    foreach ($grupos as $g) {
        echo "<tr><td>{$g['id']}</td><td>{$g['nome']}</td><td>{$g['descricao']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='erro'>❌ Usuário NÃO está em nenhum grupo!</div>";
}

// Permissões por grupo
echo "<h2>🔑 Permissões por Grupo</h2>";
foreach ($grupos as $g) {
    $stmt = $pdo->prepare("
        SELECT p.slug, p.nome 
        FROM grupo_permissoes gp 
        JOIN permissoes p ON gp.permissao_id = p.id 
        WHERE gp.grupo_id = ?
    ");
    $stmt->execute([$g['id']]);
    $perms = $stmt->fetchAll();
    
    echo "<h3>Grupo: {$g['nome']} (" . count($perms) . " permissões)</h3>";
    if (count($perms) > 0) {
        echo "<pre>" . implode(", ", array_column($perms, 'slug')) . "</pre>";
    } else {
        echo "<div class='erro'>⚠️ Grupo sem permissões!</div>";
    }
}

// Permissões diretas
$stmt = $pdo->prepare("
    SELECT p.slug 
    FROM usuario_permissoes up 
    JOIN permissoes p ON up.permissao_id = p.id 
    WHERE up.usuario_id = ?
");
$stmt->execute([$usuario['id']]);
$permissoesDiretas = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>⭐ Permissões Diretas</h2>";
if (count($permissoesDiretas) > 0) {
    echo "<pre>" . implode(", ", $permissoesDiretas) . "</pre>";
} else {
    echo "<div class='info'>Nenhuma permissão direta</div>";
}

// Todas as permissões calculadas
echo "<h2>✅ Todas as Permissões (Calculadas)</h2>";
$todasPermissoes = $usuario['permissoes'] ?? [];
echo "<div class='info'>Total: " . count($todasPermissoes) . " permissões</div>";
echo "<pre>" . implode(", ", $todasPermissoes) . "</pre>";

// Teste de permissões específicas
echo "<h2>🧪 Teste de Permissões Específicas</h2>";
$testes = [
    'ocorrencia.criar',
    'ocorrencia.listar',
    'ocorrencia.ver_detalhes',
    'usuario.listar',
    'usuario.criar',
    'grupo.listar',
    'grupo.criar',
];

echo "<table>";
echo "<tr><th>Permissão</th><th>temPermissao()</th></tr>";
foreach ($testes as $p) {
    $tem = temPermissao($p) ? '✅ SIM' : '❌ NÃO';
    echo "<tr><td>$p</td><td>$tem</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='dashboard_content.php'>← Voltar ao Dashboard</a></p>";
echo "</body></html>";
