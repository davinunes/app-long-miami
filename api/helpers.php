<?php
/**
 * Helper para APIs - Inicializa sessão e retorna usuário
 */

session_start();

function getApiUsuario() {
    if (!isset($_SESSION['usuario'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Não autenticado.']);
        exit;
    }
    return $_SESSION['usuario'];
}

function requireApiLogin() {
    return getApiUsuario();
}

function requireApiPapel($papeis) {
    $usuario = getApiUsuario();
    $papeisPermitidos = is_array($papeis) ? $papeis : [$papeis];
    $papeisUsuario = $usuario['papeis'] ?? [];
    
    foreach ($papeisPermitidos as $papel) {
        if (in_array($papel, $papeisUsuario)) {
            return true;
        }
    }
    
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente. Papel necessário: ' . implode(', ', $papeisPermitidos)]);
    exit;
}
