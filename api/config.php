<?php
// Endpoint: /api/config.php
// Método: GET
// Parâmetros: ?debug=1 para ver permissões do usuário logado
// Retorna configurações do sistema incluindo permissões

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../api/helpers.php';
requireApiLogin();

require_once '../config.php';

// Debug mode - mostra usuário logado e permissões
if (isset($_GET['debug'])) {
    $usuario = getUsuario();
    $permissoes = getPermissoesUsuario();
    
    // Buscar grupos do usuário
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT g.id, g.nome 
        FROM usuario_grupos ug 
        JOIN grupos g ON ug.grupo_id = g.id 
        WHERE ug.usuario_id = ?
    ");
    $stmt->execute([$usuario['id']]);
    $grupos = $stmt->fetchAll();
    
    // Permissões por grupo
    $permissoesPorGrupo = [];
    foreach ($grupos as $g) {
        $stmt = $pdo->prepare("
            SELECT p.slug 
            FROM grupo_permissoes gp 
            JOIN permissoes p ON gp.permissao_id = p.id 
            WHERE gp.grupo_id = ?
        ");
        $stmt->execute([$g['id']]);
        $permissoesPorGrupo[$g['nome']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
    
    echo json_encode([
        'debug' => true,
        'usuario' => [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'role' => $usuario['role'],
            'papeis' => $usuario['papeis'],
            'grupos' => $grupos,
            'permissoes_diretas' => $permissoesDiretas,
            'permissoes_por_grupo' => $permissoesPorGrupo,
            'total_permissoes' => count($permissoes),
            'todas_permissoes' => $permissoes,
            'eh_admin_ou_dev' => ($usuario['role'] === 'dev' || $usuario['role'] === 'admin'),
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt_tipos = $pdo->query("SELECT id, nome FROM notificacao_tipos ORDER BY nome ASC");
    $tipos = $stmt_tipos->fetchAll();

    $stmt_assuntos = $pdo->query("SELECT id, descricao FROM assuntos ORDER BY descricao ASC");
    $assuntos = $stmt_assuntos->fetchAll();
    
    // Papéis legacy (removido - substituído por permissões)
    $papeis = [];
    
    // Grupos com permissões
    $stmt_grupos = $pdo->query("
        SELECT g.id, g.nome, g.descricao 
        FROM grupos g 
        ORDER BY g.nome ASC
    ");
    $grupos = $stmt_grupos->fetchAll();
    foreach ($grupos as &$g) {
        $stmt = $pdo->prepare("
            SELECT p.slug FROM grupo_permissoes gp 
            JOIN permissoes p ON gp.permissao_id = p.id 
            WHERE gp.grupo_id = ?
        ");
        $stmt->execute([$g['id']]);
        $g['permissoes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Permissões do sistema
    $stmt_permissoes = $pdo->query("SELECT id, slug, nome, descricao, modulo FROM permissoes ORDER BY modulo, nome ASC");
    $permissoes = $stmt_permissoes->fetchAll();
    
    // Árvore de permissões por módulo
    $permissoesPorModulo = [];
    foreach ($permissoes as $p) {
        $modulo = $p['modulo'];
        unset($p['modulo']);
        if (!isset($permissoesPorModulo[$modulo])) {
            $permissoesPorModulo[$modulo] = [];
        }
        $permissoesPorModulo[$modulo][] = $p;
    }
    
    // Buscar configuração de URL para recurso
    $stmtUrlRecurso = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'url_recurso_default'");
    $stmtUrlRecurso->execute();
    $urlRecursoDefault = $stmtUrlRecurso->fetchColumn() ?: '';
    
    http_response_code(200);

    echo json_encode([
        'tipos' => $tipos,
        'assuntos' => $assuntos,
        'papeis' => $papeis,
        'grupos' => $grupos,
        'permissoes' => $permissoes,
        'permissoesPorModulo' => $permissoesPorModulo,
        'urlRecursoDefault' => $urlRecursoDefault
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar configurações: ' . $e->getMessage()]);
}
?>
