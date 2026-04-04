<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/jwt_loader.php';

use Firebase\JWT\JWT;

function verificarTokenEAutorizar($papelExigido = null) {
    try {
        $authHeader = null;

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

        $decoded = JWT::decode($jwt, JWT_SECRET_KEY, [JWT_ALGORITHM]);
        $dadosUsuario = $decoded->data;

        // dev é modo deus - acesso total
        if (isset($dadosUsuario->role) && $dadosUsuario->role === 'dev') {
            $dadosUsuario->papeis = ['dev', 'admin', 'protocolar', 'diligente', 'notificador', 'promotor', 'assinador', 'despachante', 'mensageiro'];
            return $dadosUsuario;
        }

        // Verifica papel único ou array de papéis permitidos
        if ($papelExigido !== null) {
            $papeisPermitidos = is_array($papelExigido) ? $papelExigido : [$papelExigido];
            
            // Busca papéis do usuário do banco
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("
                SELECT DISTINCT papel_slug FROM (
                    SELECT papel_slug FROM usuario_papeis WHERE usuario_id = ?
                    UNION
                    SELECT gp.papel_slug FROM usuario_grupos ug 
                    JOIN grupo_papeis gp ON ug.grupo_id = gp.grupo_id 
                    WHERE ug.usuario_id = ?
                ) AS todos_papeis
            ");
            $stmt->execute([$dadosUsuario->userId, $dadosUsuario->userId]);
            $papeisUsuario = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Adiciona papel da coluna role (legado)
            if (isset($dadosUsuario->role)) {
                $papeisUsuario[] = $dadosUsuario->role;
            }
            
            // Verifica se tem pelo menos um dos papéis necessários
            $temPapel = false;
            foreach ($papeisPermitidos as $papel) {
                if (in_array($papel, $papeisUsuario)) {
                    $temPapel = true;
                    break;
                }
            }
            
            if (!$temPapel) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Permissão insuficiente. Papel necessário: ' . implode(', ', $papeisPermitidos)]);
                exit();
            }
            
            $dadosUsuario->papeis = $papeisUsuario;
        } else {
            // Se não especificou papel, busca todos os papéis do usuário
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("
                SELECT DISTINCT papel_slug FROM (
                    SELECT papel_slug FROM usuario_papeis WHERE usuario_id = ?
                    UNION
                    SELECT gp.papel_slug FROM usuario_grupos ug 
                    JOIN grupo_papeis gp ON ug.grupo_id = gp.grupo_id 
                    WHERE ug.usuario_id = ?
                ) AS todos_papeis
            ");
            $stmt->execute([$dadosUsuario->userId, $dadosUsuario->userId]);
            $dadosUsuario->papeis = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (isset($dadosUsuario->role)) {
                $dadosUsuario->papeis[] = $dadosUsuario->role;
            }
        }
        
        return $dadosUsuario;

    } catch (Exception $e) {
        http_response_code(401); 
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

function temPapel($dadosUsuario, $papel) {
    return in_array($papel, $dadosUsuario->papeis ?? []);
}
?>
