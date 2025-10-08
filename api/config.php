<?php
// Endpoint: /api/config.php
// Método: GET
// Função: Retorna os tipos de notificação e os assuntos cadastrados no banco.

// Headers para permitir requisições de outras origens (CORS) e definir o tipo de conteúdo
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Inclui o arquivo de configuração e conexão com o banco
require_once '../config.php';

try {
    $pdo = getDbConnection();

    // Busca os tipos de notificação
    $stmt_tipos = $pdo->query("SELECT id, nome FROM notificacao_tipos ORDER BY nome ASC");
    $tipos = $stmt_tipos->fetchAll();

    // Busca os assuntos
    $stmt_assuntos = $pdo->query("SELECT id, descricao FROM assuntos ORDER BY descricao ASC");
    $assuntos = $stmt_assuntos->fetchAll();
    
    // Define o código de resposta HTTP como 200 (OK)
    http_response_code(200);

    // Retorna os dados em formato JSON
    echo json_encode([
        'tipos' => $tipos,
        'assuntos' => $assuntos
    ]);

} catch (Exception $e) {
    // Em caso de erro, retorna uma mensagem de erro com o código 500
    http_response_code(500);
    echo json_encode(['message' => 'Erro ao buscar configurações: ' . $e->getMessage()]);
}
?>