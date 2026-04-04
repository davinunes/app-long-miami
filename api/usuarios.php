<?php
// Endpoint: /api/usuarios.php
// Métodos: GET, POST
// Requer: autenticação JWT com papel admin ou dev

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../verificar_token.php';
require_once '../config.php';

$usuario = verificarTokenEAutorizar(['admin', 'dev']);

$metodo = $_SERVER['REQUEST_METHOD'];
$pdo = getDbConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['message' => 'Falha na conexão com o banco.']);
    exit();
}

switch ($metodo) {
    case 'GET':
        // Listar um usuário específico
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT id, nome, email, role, grupo_principal_id FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['message' => 'Usuário não encontrado.']);
                exit();
            }
            
            // Busca grupos do usuário
            $stmt = $pdo->prepare("SELECT g.id, g.nome FROM grupos g JOIN usuario_grupos ug ON g.id = ug.grupo_id WHERE ug.usuario_id = ?");
            $stmt->execute([$id]);
            $user['grupos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Busca papéis diretos do usuário
            $stmt = $pdo->prepare("SELECT papel_slug FROM usuario_papeis WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $user['papeis'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            http_response_code(200);
            echo json_encode($user);
        }
        // Listar todos os usuários
        else {
            $stmt = $pdo->query("
                SELECT u.id, u.nome, u.email, u.role, u.grupo_principal_id,
                       GROUP_CONCAT(DISTINCT g.nome) as grupos,
                       GROUP_CONCAT(DISTINCT up.papel_slug) as papeis
                FROM usuarios u
                LEFT JOIN usuario_grupos ug ON u.id = ug.usuario_id
                LEFT JOIN grupos g ON ug.grupo_id = g.id
                LEFT JOIN usuario_papeis up ON u.id = up.usuario_id
                GROUP BY u.id
                ORDER BY u.nome ASC
            ");
            $usuarios = $stmt->fetchAll();
            
            foreach ($usuarios as &$u) {
                $u['grupos'] = $u['grupos'] ? explode(',', $u['grupos']) : [];
                $u['papeis'] = $u['papeis'] ? explode(',', $u['papeis']) : [];
            }
            
            http_response_code(200);
            echo json_encode($usuarios);
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        if (isset($dados->trocar_senha)) {
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$dados->id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($dados->senha_atual, $user['senha'])) {
                http_response_code(401);
                echo json_encode(['message' => 'Senha atual incorreta.']);
                exit();
            }
            
            if ($dados->nova_senha !== $dados->confirmar_senha) {
                http_response_code(400);
                echo json_encode(['message' => 'As senhas não coincidem.']);
                exit();
            }
            
            $hash = password_hash($dados->nova_senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$hash, $dados->id]);
            
            http_response_code(200);
            echo json_encode(['message' => 'Senha alterada com sucesso!']);
            exit();
        }
        
        if (empty($dados->nome) || empty($dados->email)) {
            http_response_code(400);
            echo json_encode(['message' => 'Nome e email são obrigatórios.']);
            exit();
        }

        if (isset($dados->id) && !empty($dados->id)) {
            // ATUALIZAR
            $id = (int)$dados->id;
            
            $sql = "UPDATE usuarios SET nome = ?, email = ?";
            $params = [$dados->nome, $dados->email];
            
            if (!empty($dados->senha)) {
                $sql .= ", senha = ?";
                $params[] = password_hash($dados->senha, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Atualiza grupos se fornecidos
            if (isset($dados->grupos)) {
                $pdo->prepare("DELETE FROM usuario_grupos WHERE usuario_id = ?")->execute([$id]);
                if (is_array($dados->grupos)) {
                    $stmt = $pdo->prepare("INSERT INTO usuario_grupos (usuario_id, grupo_id) VALUES (?, ?)");
                    foreach ($dados->grupos as $grupo_id) {
                        $stmt->execute([$id, (int)$grupo_id]);
                    }
                }
            }
            
            // Atualiza papéis diretos se fornecidos
            if (isset($dados->papeis)) {
                $pdo->prepare("DELETE FROM usuario_papeis WHERE usuario_id = ?")->execute([$id]);
                if (is_array($dados->papeis)) {
                    $stmt = $pdo->prepare("INSERT INTO usuario_papeis (usuario_id, papel_slug) VALUES (?, ?)");
                    foreach ($dados->papeis as $papel) {
                        $stmt->execute([$id, $papel]);
                    }
                }
            }
            
            http_response_code(200);
            echo json_encode(['message' => 'Usuário atualizado com sucesso!', 'id' => $id]);

        } else {
            // CRIAR
            if (empty($dados->senha)) {
                http_response_code(400);
                echo json_encode(['message' => 'A senha é obrigatória para criar um novo usuário.']);
                exit;
            }
            
            $senhaHash = password_hash($dados->senha, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$dados->nome, $dados->email, $senhaHash, $dados->role ?? 'condomino']);
                $novo_id = $pdo->lastInsertId();
                
                // Adiciona grupos se fornecidos
                if (!empty($dados->grupos) && is_array($dados->grupos)) {
                    $stmt = $pdo->prepare("INSERT INTO usuario_grupos (usuario_id, grupo_id) VALUES (?, ?)");
                    foreach ($dados->grupos as $grupo_id) {
                        $stmt->execute([$novo_id, (int)$grupo_id]);
                    }
                }
                
                // Adiciona papéis se fornecidos
                if (!empty($dados->papeis) && is_array($dados->papeis)) {
                    $stmt = $pdo->prepare("INSERT INTO usuario_papeis (usuario_id, papel_slug) VALUES (?, ?)");
                    foreach ($dados->papeis as $papel) {
                        $stmt->execute([$novo_id, $papel]);
                    }
                }
                
                http_response_code(201);
                echo json_encode(['message' => 'Usuário criado com sucesso!', 'id' => $novo_id]);
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    http_response_code(409);
                    echo json_encode(['message' => 'Este email já está cadastrado.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Erro ao criar usuário: ' . $e->getMessage()]);
                }
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}
?>
