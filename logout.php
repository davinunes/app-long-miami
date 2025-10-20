<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$pdo = getDbConnection();
$refreshToken = $_COOKIE['refreshToken'] ?? null;

if ($refreshToken && $pdo) {
    $refreshTokenHash = hash('sha256', $refreshToken);
    $stmt = $pdo->prepare("UPDATE usuarios SET refresh_token = NULL, refresh_token_expira_em = NULL WHERE refresh_token = ?");
    $stmt->execute([$refreshTokenHash]);
}

// Limpa o cookie do lado do cliente
setcookie(
    'refreshToken',
    '',
    time() - 3600, // Expiração no passado
    '/'
);

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Logout realizado com sucesso.']);
?>