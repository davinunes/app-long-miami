<?php
// Inclui suas configurações e o carregador da biblioteca JWT
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/jwt_loader.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Verifica o token JWT da requisição.
 * Se o token for inválido, ausente ou o usuário não tiver a permissão,
 * a função envia uma resposta de erro e interrompe o script.
 * Se tudo estiver correto, retorna os dados do usuário.
 *
 * @param string|array|null $roleExigida A role (ou lista de roles) necessária para o acesso.
 * @return object Os dados do usuário decodificados do payload do token.
 */
function verificarTokenEAutorizar($roleExigida = null) {
    try {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$authHeader) {
            throw new Exception('Token de autorização não fornecido.');
        }

        list($jwt) = sscanf($authHeader, 'Bearer %s');
        if (!$jwt) {
            throw new Exception('Formato de token inválido.');
        }

        $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        $dadosUsuario = $decoded->data;

        // Se uma ou mais roles foram exigidas, verifica a permissão
        if ($roleExigida !== null) {
            $rolesPermitidas = is_array($roleExigida) ? $roleExigida : [$roleExigida];
            if (!isset($dadosUsuario->role) || !in_array($dadosUsuario->role, $rolesPermitidas)) {
                // O usuário está autenticado, mas não tem a permissão correta
                http_response_code(403); // 403 Forbidden
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Permissão insuficiente para este recurso.']);
                exit();
            }
        }
        
        return $dadosUsuario;

    } catch (Exception $e) {
        // Se a validação do token falhar (expirado, assinatura inválida, etc.)
        http_response_code(401); // 401 Unauthorized
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado: ' . $e->getMessage()]);
        exit(); // Interrompe a execução
    }
}
?>