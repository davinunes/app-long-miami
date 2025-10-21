<?php
header('Content-Type: application/json; charset=utf-8');

// Inclui seus arquivos de configuração e bibliotecas
require_once 'config.php';
require_once 'lib/jwt_loader.php';

use Firebase\JWT\JWT;

// Obtém a conexão com o banco de dados usando sua função
$pdo = getDbConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$senha = $input['senha'] ?? '';

if (empty($email) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email e senha são obrigatórios.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, email, senha, role FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Credenciais inválidas.']);
    exit;
}

try {
    // ---- Access Token (curto: 15 minutos) ----
    $iat = time();
    $exp_access = $iat + 900;
    $payload_access = [
		'iss' => $_SERVER['HTTP_HOST'],
		'iat' => $iat,
		'exp' => $exp_access,
		'data' => [
			'userId' => $usuario['id'], 
			'role' => $usuario['role'],
			'nome' => $usuario['nome'],   // <<-- LINHA NOVA
			'email' => $usuario['email'] // <<-- LINHA NOVA
		]
	];
    $accessToken = JWT::encode($payload_access, JWT_SECRET_KEY, JWT_ALGORITHM);

    // ---- Refresh Token (longo: 7 dias) ----
    $exp_refresh = time() + (86400 * 7);
    $refreshToken = bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("UPDATE usuarios SET refresh_token = ?, refresh_token_expira_em = ? WHERE id = ?");
    $stmt->execute([hash('sha256', $refreshToken), date('Y-m-d H:i:s', $exp_refresh), $usuario['id']]);
    
    // Envia o refresh token em um cookie HttpOnly seguro
	setcookie(
		'refreshToken',              // Nome
		$refreshToken,               // Valor
		$exp_refresh,                // Expiração (como inteiro)
		'/',                         // Path
		'',                          // Domain (vazio para o domínio atual)
		true,                        // Secure (use 'true' se estiver em HTTPS)
		true                         // HttpOnly
	);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'access_token' => $accessToken]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao processar o login.']);
}
?>