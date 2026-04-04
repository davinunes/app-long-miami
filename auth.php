<?php
/**
 * Sistema de Autenticação via Sessão PHP
 * Substitui o JWT para simplificar a manutenção
 */

session_start();

require_once __DIR__ . '/config.php';

function estaLogado() {
    return isset($_SESSION['usuario']['id']);
}

function getUsuario() {
    return $_SESSION['usuario'] ?? null;
}

function getUsuarioId() {
    return $_SESSION['usuario']['id'] ?? null;
}

function getUsuarioNome() {
    return $_SESSION['usuario']['nome'] ?? null;
}

function getUsuarioEmail() {
    return $_SESSION['usuario']['email'] ?? null;
}

function getUsuarioRole() {
    return $_SESSION['usuario']['role'] ?? null;
}

function temPapel($papel) {
    $papeis = getPapeisUsuario();
    return in_array($papel, $papeis);
}

function temAlgumPapel($papeis) {
    foreach ($papeis as $papel) {
        if (temPapel($papel)) return true;
    }
    return false;
}

function getPapeisUsuario() {
    if (!estaLogado()) return [];
    return $_SESSION['usuario']['papeis'] ?? [];
}

function requireLogin() {
    if (!estaLogado()) {
        header('Location: index.php');
        exit;
    }
}

function requirePapel($papeis) {
    requireLogin();
    if (!temAlgumPapel(is_array($papeis) ? $papeis : [$papeis])) {
        http_response_code(403);
        die('Acesso negado. Papel necessário: ' . (is_array($papeis) ? implode(', ', $papeis) : $papeis));
    }
}

function login($email, $senha) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        return ['success' => false, 'message' => 'Email ou senha incorretos.'];
    }
    
    // Buscar papéis do usuário
    $stmtPapeis = $pdo->prepare("
        SELECT DISTINCT papel_slug FROM (
            SELECT papel_slug FROM usuario_papeis WHERE usuario_id = ?
            UNION
            SELECT gp.papel_slug FROM usuario_grupos ug 
            JOIN grupo_papeis gp ON ug.grupo_id = gp.grupo_id 
            WHERE ug.usuario_id = ?
        ) AS todos_papeis
    ");
    $stmtPapeis->execute([$usuario['id'], $usuario['id']]);
    $papeis = $stmtPapeis->fetchAll(PDO::FETCH_COLUMN);
    $papeis[] = $usuario['role'];
    
    // Armazenar na sessão
    $_SESSION['usuario'] = [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'role' => $usuario['role'],
        'papeis' => $papeis,
        'login_at' => date('Y-m-d H:i:s')
    ];
    
    return ['success' => true, 'message' => 'Login realizado com sucesso.'];
}

function logout() {
    session_destroy();
    session_unset();
}
