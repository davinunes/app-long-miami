<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/jwt_loader.php';

use Firebase\JWT\JWT;
// REMOVEMOS a linha 'use Firebase\JWT\Key;' daqui

function verificarTokenEAutorizar($roleExigida = null) {
    try {
        $authHeader = null;

        // Lógica correta para encontrar o header no seu container
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strtolower($key) == 'authorization') {
                    $authHeader = $value;
                    break;
                }
            }
        }

        if (!$authHeader) { 
            throw new Exception('Cabeçalho de autorização não encontrado.'); 
        }

        list($jwt) = sscanf($authHeader, 'Bearer %s');
        if (!$jwt) { 
            throw new Exception('Formato de token inválido. Esperado "Bearer [token]".'); 
        }

        // --- AQUI ESTÁ A CORREÇÃO ---
        // Voltamos para a sintaxe da V5, que o seu servidor entende.
        // Em vez de 'new Key(...)', passamos a chave e o algoritmo diretamente.
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
        // PASSO 1: Retorne o código de "Não Autorizado"
        http_response_code(401); 
        
        // PASSO 2: Envie a mensagem de erro real
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage() // Envia a mensagem real, ex: "Expired token"
        ]);
        
        exit();
    }
}
?>