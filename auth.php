<?php
/**
 * Sistema de Autenticação via Sessão PHP
 * Substitui o JWT para simplificar a manutenção
 * 
 * Sistema de Permissões: Usa permissões granulares em vez de papéis
 * O papel 'dev' é o "modo deus" com todas as permissões
 */

session_start();

require_once __DIR__ . '/config.php';

/**
 * Verifica se o usuário está logado
 */
function estaLogado() {
    return isset($_SESSION['usuario']['id']);
}

/**
 * Retorna os dados do usuário logado
 */
function getUsuario() {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Retorna o ID do usuário logado
 */
function getUsuarioId() {
    return $_SESSION['usuario']['id'] ?? null;
}

/**
 * Retorna o nome do usuário logado
 */
function getUsuarioNome() {
    return $_SESSION['usuario']['nome'] ?? null;
}

/**
 * Retorna o email do usuário logado
 */
function getUsuarioEmail() {
    return $_SESSION['usuario']['email'] ?? null;
}

/**
 * Retorna o role (papel) principal do usuário
 */
function getUsuarioRole() {
    return $_SESSION['usuario']['role'] ?? null;
}

/**
 * Verifica se o usuário tem um papel específico (legacy, usar temPermissao)
 */
function temPapel($papel) {
    $papeis = getPapeisUsuario();
    return in_array($papel, $papeis);
}

/**
 * Verifica se o usuário tem algum dos papéis especificados (legacy)
 */
function temAlgumPapel($papeis) {
    foreach ($papeis as $papel) {
        if (temPapel($papel)) return true;
    }
    return false;
}

/**
 * Retorna todos os papéis do usuário (legacy)
 */
function getPapeisUsuario() {
    if (!estaLogado()) return [];
    return $_SESSION['usuario']['papeis'] ?? [];
}

/**
 * Alias para getPapeisUsuario (legacy)
 */
function getUsuarioPapeis() {
    return getPapeisUsuario();
}

// =====================================================
// SISTEMA DE PERMISSÕES
// =====================================================

/**
 * Retorna todas as permissões do usuário logado
 * Inclui: permissões do grupo + permissões diretas + dev tem todas
 */
function getPermissoesUsuario() {
    if (!estaLogado()) return [];
    return $_SESSION['usuario']['permissoes'] ?? [];
}

/**
 * Verifica se o usuário tem uma permissão específica
 * 
 * @param string $permissao Slug da permissão (ex: 'ocorrencia.criar')
 * @param array $context Contexto adicional (ex: ['ocorrencia_id' => 5])
 * @return bool
 */
function temPermissao($permissao, $context = []) {
    if (!estaLogado()) return false;
    
    $usuario = getUsuario();
    
    // DEV e ADMIN sempre tem tudo (modo deus)
    $role = $usuario['role'] ?? '';
    if ($role === 'dev' || $role === 'admin') {
        return true;
    }
    
    // Verifica se tem nos papéis (sessão)
    if (in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? [])) {
        return true;
    }
    
    // Verifica na sessão (cache)
    $permissoes = getPermissoesUsuario();
    return in_array($permissao, $permissoes);
}

/**
 * Verifica se o usuário tem pelo menos uma das permissões especificadas
 * 
 * @param array $permissoes Array de slugs de permissões
 * @return bool
 */
function temAlgumaPermissao($permissoes) {
    if (!estaLogado()) return false;
    
    $usuario = getUsuario();
    
    // DEV e ADMIN sempre têm todas as permissões
    $role = $usuario['role'] ?? '';
    if ($role === 'dev' || $role === 'admin') {
        return true;
    }
    
    if (in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? [])) {
        return true;
    }
    
    $permissoesUsuario = getPermissoesUsuario();
    foreach ($permissoes as $permissao) {
        if (in_array($permissao, $permissoesUsuario)) return true;
    }
    return false;
}

/**
 * Verifica se o usuário é dono do recurso
 * Usado para permissões *_propria
 * 
 * @param string $tabela Nome da tabela
 * @param int $registroId ID do registro
 * @return bool
 */
function verificarDono($tabela, $registroId) {
    $usuarioId = getUsuarioId();
    if (!$usuarioId) return false;
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT created_by FROM {$tabela} WHERE id = ?");
    $stmt->execute([$registroId]);
    $registro = $stmt->fetch();
    
    return $registro && $registro['created_by'] == $usuarioId;
}

/**
 * Verifica se o usuário tem permissão para editar próprio ou qualquer
 * 
 * @param string $tabela Tabela do recurso
 * @param int $registroId ID do registro
 * @param string $permissaoPropria Permissão para próprio (ex: 'ocorrencia.editar_propria')
 * @param string $permissaoGeral Permissão geral (ex: 'ocorrencia.editar')
 * @return bool
 */
function podeEditar($tabela, $registroId, $permissaoPropria, $permissaoGeral) {
    if (temPermissao($permissaoGeral)) return true;
    if (temPermissao($permissaoPropria) && verificarDono($tabela, $registroId)) return true;
    return false;
}

// =====================================================
// HELPERS DE AUTENTICAÇÃO
// =====================================================

/**
 * Redireciona para login se não estiver logado
 */
function requireLogin() {
    if (!estaLogado()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Verifica se o usuário tem o papel necessário (legacy)
 */
function requirePapel($papeis) {
    requireLogin();
    if (!temAlgumPapel(is_array($papeis) ? $papeis : [$papeis])) {
        http_response_code(403);
        die('Acesso negado. Papel necessário: ' . (is_array($papeis) ? implode(', ', $papeis) : $papeis));
    }
}

/**
 * Verifica se o usuário tem a permissão necessária
 */
function requirePermissao($permissao, $context = []) {
    requireLogin();
    if (!temPermissao($permissao, $context)) {
        http_response_code(403);
        die('Acesso negado. Permissão necessária: ' . $permissao);
    }
}

/**
 * Verifica se o usuário tem pelo menos uma das permissões
 */
function requireAlgumaPermissao($permissoes) {
    requireLogin();
    if (!temAlgumaPermissao($permissoes)) {
        http_response_code(403);
        die('Acesso negado. Permissões necessárias: ' . implode(' ou ', $permissoes));
    }
}

// =====================================================
// LOGIN / LOGOUT
// =====================================================

/**
 * Realiza o login do usuário
 * Carrega permissões do grupo na sessão
 */
function login($email, $senha) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        return ['success' => false, 'message' => 'Email ou senha incorretos.'];
    }
    
    // Papel principal do usuário (do campo 'role')
    $papeis = [];
    if ($usuario['role']) {
        $papeis[] = $usuario['role'];
    }
    
    // Buscar permissões do usuário (via grupos + diretas)
    $stmtPerm = $pdo->prepare("
        SELECT DISTINCT p.slug FROM (
            SELECT gp.permissao_id FROM usuario_grupos ug 
            JOIN grupo_permissoes gp ON ug.grupo_id = gp.grupo_id 
            WHERE ug.usuario_id = ?
            UNION
            SELECT up.permissao_id FROM usuario_permissoes up 
            WHERE up.usuario_id = ?
        ) AS todas_permissoes
        JOIN permissoes p ON p.id = todas_permissoes.permissao_id
    ");
    $stmtPerm->execute([$usuario['id'], $usuario['id']]);
    $permissoes = $stmtPerm->fetchAll(PDO::FETCH_COLUMN);
    
    // DEV sempre tem todas as permissões
    if (in_array('dev', $papeis)) {
        $stmtTodasPerm = $pdo->query("SELECT slug FROM permissoes");
        $todasPermissoes = $stmtTodasPerm->fetchAll(PDO::FETCH_COLUMN);
        $permissoes = array_unique(array_merge($permissoes, $todasPermissoes));
    }
    
    // ADMIN também tem todas as permissões
    if (in_array('admin', $papeis)) {
        $stmtTodasPerm = $pdo->query("SELECT slug FROM permissoes");
        $todasPermissoes = $stmtTodasPerm->fetchAll(PDO::FETCH_COLUMN);
        $permissoes = array_unique(array_merge($permissoes, $todasPermissoes));
    }
    
    // Armazenar na sessão
    $_SESSION['usuario'] = [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'role' => $usuario['role'],
        'papeis' => $papeis,
        'permissoes' => $permissoes,
        'login_at' => date('Y-m-d H:i:s')
    ];
    
    return ['success' => true, 'message' => 'Login realizado com sucesso.'];
}

/**
 * Realiza o logout do usuário
 */
function logout() {
    session_destroy();
    session_unset();
}

// =====================================================
// HELPERS PARA TEMPLATES
// =====================================================

/**
 * Retorna 'active' se o usuário tem a permissão (para menus)
 */
function ativoSe($permissao) {
    return temPermissao($permissao) ? 'active' : '';
}

/**
 * Retorna true se o usuário é admin ou dev
 */
function isAdmin() {
    return temPapel('admin') || temPapel('dev');
}

/**
 * Retorna true se o usuário é dev
 */
function isDev() {
    return temPapel('dev');
}
