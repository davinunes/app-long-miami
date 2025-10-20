<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
require_once 'lib/jwt_loader.php';
use Firebase\JWT\JWT;

$pdo = getDbConnection();
if (!$pdo) { /* ... erro de conexão ... */ exit; }

$refreshToken = $_COOKIE['refreshToken'] ?? null;
if (!$refreshToken) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Refresh token não encontrado.']);
    exit;
}

try {
    $refreshTokenHash = hash('sha256', $refreshToken);
    $stmt = $pdo->prepare("SELECT id, role, refresh_token_expira_em FROM usuarios WHERE refresh_token = ?");
    $stmt->execute([$refreshTokenHash]);
    $usuario = $stmt->fetch();

    if (!$usuario || strtotime($usuario['refresh_token_expira_em']) < time()) {
        throw new Exception('Refresh token inválido ou expirado.');
    }

    $iat = time();
    $exp_access = $iat + 900;
    $payload_access = [
        'iss' => $_SERVER['HTTP_HOST'], 'iat' => $iat, 'exp' => $exp_access,
        'data' => ['userId' => $usuario['id'], 'role' => $usuario['role']]
    ];
    $accessToken = JWT::encode($payload_access, JWT_SECRET_KEY, JWT_ALGORITHM);
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'access_token' => $accessToken]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível renovar o token: ' . $e->getMessage()]);
}
?>