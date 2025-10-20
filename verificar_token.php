<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/jwt_loader.php';

use Firebase\JWT\JWT; // Apenas o JWT é necessário aqui

function verificarTokenEAutorizar($roleExigida = null) {
    try {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$authHeader) { throw new Exception('Token de autorização não fornecido.'); }

        list($jwt) = sscanf($authHeader, 'Bearer %s');
        if (!$jwt) { throw new Exception('Formato de token inválido.'); }

        // MUDANÇA APLICADA AQUI
        $decoded = JWT::decode($jwt, JWT_SECRET_KEY, [JWT_ALGORITHM]);
        
        $dadosUsuario = $decoded->data;

        if ($roleExigida !== null) {
            $rolesPermitidas = is_array($roleExigida) ? $roleExigida : [$roleExigida];
            if (!isset($dadosUsuario->role) || !in_array($dadosUsuario->role, $rolesPermitidas)) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Permissão insuficiente.']);
                exit();
            }
        }
        
        return $dadosUsuario;

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado: ' . $e->getMessage()]);
        exit();
    }
}
?>