<?php
// Endpoint: /api/usuarios.php

// --- PASSO 1: Headers CORS e Resposta OPTIONS ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- PASSO 2: Verificação de Token ---
require_once '../verificar_token.php';
// Apenas 'admin' ou 'dev' podem gerenciar usuários (Exemplo de regra)
$dadosUsuario = verificarTokenEAutorizar(['admin', 'dev']); 

// --- PASSO 3: Lógica da API ---
require_once '../config.php';
$metodo = $_SERVER['REQUEST_METHOD'];
$pdo = getDbConnection();

switch ($metodo) {
    case 'GET':
        // --- Listar um ou todos os usuários ---
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT id, nome, email, role FROM usuarios WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch());
        } else {
            // Nunca envie a senha no GET!
            $stmt = $pdo->query("SELECT id, nome, email, role FROM usuarios ORDER BY nome ASC");
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        // --- Criar ou Atualizar um usuário ---
        $dados = json_decode(file_get_contents("php://input"));
        
        if (empty($dados->nome) || empty($dados->email) || empty($dados->role)) {
            http_response_code(400);
            echo json_encode(['message' => 'Nome, email e nível são obrigatórios.']);
            exit;
        }

        if (isset($dados->id) && !empty($dados->id)) {
            // --- ATUALIZAR (UPDATE) ---
            $id = (int)$dados->id;
            
            if (!empty($dados->senha)) {
                // Se uma nova senha foi fornecida, atualiza
                $senhaHash = password_hash($dados->senha, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = ?, email = ?, role = ?, senha = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$dados->nome, $dados->email, $dados->role, $senhaHash, $id]);
            } else {
                // Se a senha está em branco, NÃO atualiza a senha
                $sql = "UPDATE usuarios SET nome = ?, email = ?, role = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$dados->nome, $dados->email, $dados->role, $id]);
            }
            echo json_encode(['message' => 'Usuário atualizado com sucesso!', 'id' => $id]);

        } else {
            // --- CRIAR (INSERT) ---
            if (empty($dados->senha)) {
                http_response_code(400);
                echo json_encode(['message' => 'A senha é obrigatória para criar um novo usuário.']);
                exit;
            }
            
            $senhaHash = password_hash($dados->senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([$dados->nome, $dados->email, $senhaHash, $dados->role]);
                echo json_encode(['message' => 'Usuário criado com sucesso!', 'id' => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                // Erro 23000 é violação de constraint (provavelmente email duplicado)
                if ($e->getCode() == 23000) {
                    http_response_code(409); // Conflito
                    echo json_encode(['message' => 'Este email já está cadastrado.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Erro ao criar usuário: ' . $e->getMessage()]);
                }
            }
        }
        break;
    
    // (Opcional) Você pode adicionar um 'case DELETE:' aqui se quiser.
}
?>