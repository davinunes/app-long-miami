<?php
/**
 * Helper para APIs - Inicializa sessão e retorna usuário
 */

require_once __DIR__ . '/../auth.php';

function getApiUsuario() {
    if (!estaLogado()) {
        http_response_code(401);
        echo json_encode(['message' => 'Não autenticado.']);
        exit;
    }
    $usuario = getUsuario();
    error_log("SESSION DEBUG: " . print_r($_SESSION, true));
    return $usuario;
}

function requireApiLogin() {
    return getApiUsuario();
}

function requireApiPapel($papeis) {
    $usuario = getApiUsuario();
    $papeisPermitidos = is_array($papeis) ? $papeis : [$papeis];
    $papeisUsuario = getPapeisUsuario();
    
    foreach ($papeisPermitidos as $papel) {
        if (in_array($papel, $papeisUsuario)) {
            return true;
        }
    }
    
    http_response_code(403);
    echo json_encode([
        'message' => 'Permissão insuficiente. Papel necessário: ' . implode(', ', $papeisPermitidos),
        'debug' => ['session' => $_SESSION, 'papeisUsuario' => $papeisUsuario]
    ]);
    exit;
}
