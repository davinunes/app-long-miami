<?php
// Endpoint: /api/usuarios.php
// Métodos: GET, POST
// Suporta tanto papéis (legacy) quanto permissões

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../api/helpers.php';
$usuario = getApiUsuario();

require_once '../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$pdo = getDbConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['message' => 'Falha na conexão com o banco.']);
    exit();
}

// Verificar acesso
$isAdminDev = in_array($usuario['role'], ['admin', 'dev']) || in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? []);
$temPermissaoListar = $isAdminDev || temPermissao('usuario.listar');
$temPermissaoEditar = $isAdminDev || temPermissao('usuario.editar');
$temPermissaoCriar = $isAdminDev || temPermissao('usuario.criar');

switch ($metodo) {
    case 'GET':
        // Qualquer usuário logado pode ver a si mesmo
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            // Verificar se pode ver outros usuários
            if ($id !== $usuario['id'] && !$temPermissaoListar) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para ver outros usuários.']);
                exit();
            }
            
            $stmt = $pdo->prepare("SELECT id, nome, email, role, grupo_principal_id FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['message' => 'Usuário não encontrado.']);
                exit();
            }
            
            $stmt = $pdo->prepare("SELECT g.id, g.nome FROM grupos g JOIN usuario_grupos ug ON g.id = ug.grupo_id WHERE ug.usuario_id = ?");
            $stmt->execute([$id]);
            $user['grupos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode($user);
        }
        else {
            // Sem ID, listar - requer permissão
            if (!$temPermissaoListar) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para listar usuários.']);
                exit();
            }
            
            $stmt = $pdo->query("
                SELECT u.id, u.nome, u.email, u.role, u.grupo_principal_id,
                       GROUP_CONCAT(DISTINCT g.id) as grupo_ids,
                       GROUP_CONCAT(DISTINCT g.nome) as grupos
                FROM usuarios u
                LEFT JOIN usuario_grupos ug ON u.id = ug.usuario_id
                LEFT JOIN grupos g ON ug.grupo_id = g.id
                GROUP BY u.id
                ORDER BY u.nome ASC
            ");
            $usuarios = $stmt->fetchAll();
            
            foreach ($usuarios as &$u) {
                $u['grupos'] = $u['grupos'] ? explode(',', $u['grupos']) : [];
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
            
            // Verificar permissão: admin/dev ou tem permissão de editar ou é o próprio usuário
            $isProprio = ($id === $usuario['id']);
            if (!$isAdminDev && !$temPermissaoEditar && !$isProprio) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para editar usuário.']);
                exit();
            }
            
            // Se não for admin/dev e não tiver permissão, só pode editar nome/email (não grupos)
            $podeEditarGrupos = $isAdminDev || $temPermissaoEditar;
            $podeAlterarSenha = $isAdminDev || $temPermissaoEditar || $isProprio;
            
            // Se usuário próprio está mudando senha, verificar senha atual
            if ($isProprio && !empty($dados->senha)) {
                if (empty($dados->senha_atual)) {
                    http_response_code(400);
                    echo json_encode(['message' => 'Informe a senha atual para alterar.']);
                    exit();
                }
                
                $stmtCheck = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
                $stmtCheck->execute([$id]);
                $userData = $stmtCheck->fetch();
                
                if (!$userData || !password_verify($dados->senha_atual, $userData['senha'])) {
                    http_response_code(401);
                    echo json_encode(['message' => 'Senha atual incorreta.']);
                    exit();
                }
            }
            
            $sql = "UPDATE usuarios SET nome = ?, email = ?";
            $params = [$dados->nome, $dados->email];
            
            if (!empty($dados->senha) && $podeAlterarSenha) {
                $sql .= ", senha = ?";
                $params[] = password_hash($dados->senha, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Atualiza grupos se fornecidos e se tiver permissão
            if ($podeEditarGrupos && isset($dados->grupos)) {
                $pdo->prepare("DELETE FROM usuario_grupos WHERE usuario_id = ?")->execute([$id]);
                if (is_array($dados->grupos)) {
                    $stmt = $pdo->prepare("INSERT INTO usuario_grupos (usuario_id, grupo_id) VALUES (?, ?)");
                    foreach ($dados->grupos as $grupo_id) {
                        $stmt->execute([$id, (int)$grupo_id]);
                    }
                }
            }
            
            http_response_code(200);
            echo json_encode(['message' => 'Usuário atualizado com sucesso!', 'id' => $id]);

        } else {
            // CRIAR NOVO USUÁRIO - requer permissão
            if (!$isAdminDev && !$temPermissaoCriar) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para criar usuário.']);
                exit();
            }
            
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
                
                if (!empty($dados->grupos) && is_array($dados->grupos)) {
                    $stmt = $pdo->prepare("INSERT INTO usuario_grupos (usuario_id, grupo_id) VALUES (?, ?)");
                    foreach ($dados->grupos as $grupo_id) {
                        $stmt->execute([$novo_id, (int)$grupo_id]);
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
