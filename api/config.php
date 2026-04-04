<?php
// Endpoint: /api/config.php
// Método: GET
// Retorna configurações do sistema incluindo permissões

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../api/helpers.php';
requireApiLogin();

require_once '../config.php';

try {
    $pdo = getDbConnection();

    $stmt_tipos = $pdo->query("SELECT id, nome FROM notificacao_tipos ORDER BY nome ASC");
    $tipos = $stmt_tipos->fetchAll();

    $stmt_assuntos = $pdo->query("SELECT id, descricao FROM assuntos ORDER BY descricao ASC");
    $assuntos = $stmt_assuntos->fetchAll();
    
    // Papéis legacy (manter para compatibilidade)
    $stmt_papeis = $pdo->query("SELECT slug, nome, descricao FROM papeles ORDER BY nome ASC");
    $papeis = $stmt_papeis->fetchAll();
    
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
    
    http_response_code(200);

    echo json_encode([
        'tipos' => $tipos,
        'assuntos' => $assuntos,
        'papeis' => $papeis,
        'grupos' => $grupos,
        'permissoes' => $permissoes,
        'permissoesPorModulo' => $permissoesPorModulo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar configurações: ' . $e->getMessage()]);
}
?>
