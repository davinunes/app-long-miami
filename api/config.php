<?php
// Endpoint: /api/config.php
// Método: GET

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
    
    $stmt_papeis = $pdo->query("SELECT slug, nome, descricao FROM papeles ORDER BY nome ASC");
    $papeis = $stmt_papeis->fetchAll();
    
    $stmt_grupos = $pdo->query("SELECT g.id, g.nome, g.descricao, GROUP_CONCAT(gp.papel_slug) as papeis FROM grupos g LEFT JOIN grupo_papeis gp ON g.id = gp.grupo_id GROUP BY g.id ORDER BY g.nome ASC");
    $grupos = $stmt_grupos->fetchAll();
    foreach ($grupos as &$g) {
        $g['papeis'] = $g['papeis'] ? explode(',', $g['papeis']) : [];
    }
    
    http_response_code(200);

    echo json_encode([
        'tipos' => $tipos,
        'assuntos' => $assuntos,
        'papeis' => $papeis,
        'grupos' => $grupos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar configurações: ' . $e->getMessage()]);
}
?>