<?php
/**
 * Helper para APIs - Inicializa sessão e retorna usuário
 * Suporta tanto papéis (legacy) quanto permissões
 */

require_once __DIR__ . '/../auth.php';

/**
 * Retorna o usuário logado ou rejeita a requisição
 */
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function getApiUsuario() {
    if (!estaLogado()) {
        http_response_code(401);
        echo json_encode(['message' => 'Não autenticado.']);
        exit;
    }
    return getUsuario();
}

/**
 * Alias para getApiUsuario
 */
function requireApiLogin() {
    return getApiUsuario();
}

/**
 * Verifica se o usuário tem um dos papéis (legacy)
 */
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
    echo json_encode(['message' => 'Permissão insuficiente. Papel necessário: ' . implode(', ', $papeisPermitidos)]);
    exit;
}

/**
 * Verifica se o usuário tem uma permissão específica
 * 
 * @param string $permissao Slug da permissão
 * @param array $context Contexto adicional
 * @return bool
 */
function requireApiPermissao($permissao, $context = []) {
    $usuario = getApiUsuario();
    
    // DEV e ADMIN sempre têm todas as permissões
    $role = $usuario['role'] ?? '';
    if ($role === 'dev' || $role === 'admin') {
        return true;
    }
    
    // Verifica se tem nos papéis (sessão)
    if (in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? [])) {
        return true;
    }
    
    if (temPermissao($permissao, $context)) {
        return true;
    }
    
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente. Necessário: ' . $permissao]);
    exit;
}

/**
 * Verifica se o usuário tem pelo menos uma das permissões
 * 
 * @param array $permissoes Array de slugs
 * @return bool
 */
function requireApiAlgumaPermissao($permissoes) {
    $usuario = getApiUsuario();
    
    // DEV e ADMIN sempre têm todas as permissões
    $role = $usuario['role'] ?? '';
    if ($role === 'dev' || $role === 'admin') {
        return true;
    }
    
    if (in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? [])) {
        return true;
    }
    
    if (temAlgumaPermissao($permissoes)) {
        return true;
    }
    
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente. Necessário: ' . implode(' ou ', $permissoes)]);
    exit;
}

/**
 * Verifica se o usuário pode editar um recurso próprio ou qualquer um
 * 
 * @param string $tabela Tabela do recurso
 * @param int $registroId ID do registro
 * @param string $permissaoPropria Permissão para próprio
 * @param string $permissaoGeral Permissão geral
 * @return bool
 */
function podeEditarRecurso($tabela, $registroId, $permissaoPropria, $permissaoGeral) {
    return podeEditar($tabela, $registroId, $permissaoPropria, $permissaoGeral);
}
