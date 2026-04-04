<?php
// API de Síndicos

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/helpers.php';
requireApiLogin();

require_once __DIR__ . '/../config.php';

$pdo = getDbConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na conexão com o banco.']);
    exit();
}

$metodo = $_SERVER['REQUEST_METHOD'];

function verificarAdmin($usuario) {
    $papeis = $usuario['papeis'] ?? [];
    if (!in_array('admin', $papeis) && !in_array('dev', $papeis)) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado. Apenas administradores.']);
        exit();
    }
}

switch ($metodo) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM sindicos WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $sindico = $stmt->fetch();
            if ($sindico) {
                echo json_encode($sindico);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Síndico não encontrado.']);
            }
        } elseif (isset($_GET['ativo'])) {
            $stmt = $pdo->query("SELECT * FROM sindicos WHERE ativo = 1 ORDER BY data_inicio DESC LIMIT 1");
            $sindico = $stmt->fetch();
            echo json_encode($sindico ?: null);
        } else {
            $stmt = $pdo->query("SELECT * FROM sindicos ORDER BY ativo DESC, data_inicio DESC");
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        verificarAdmin($usuario);
        $dados = json_decode(file_get_contents("php://input"), true);
        
        if (empty($dados['nome']) || empty($dados['data_inicio'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome e data de início são obrigatórios.']);
            exit();
        }
        
        if (isset($dados['ativar'])) {
            $pdo->prepare("UPDATE sindicos SET ativo = 0")->execute();
            $stmt = $pdo->prepare("UPDATE sindicos SET ativo = 1 WHERE id = ?");
            $stmt->execute([$dados['id']]);
            echo json_encode(['success' => true, 'message' => 'Síndico ativado.']);
            break;
        }
        
        $sql = "INSERT INTO sindicos (nome, cpf, email, telefone, data_inicio, data_fim, ativo, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dados['nome'],
            $dados['cpf'] ?? null,
            $dados['email'] ?? null,
            $dados['telefone'] ?? null,
            $dados['data_inicio'],
            $dados['data_fim'] ?? null,
            $dados['ativo'] ?? 1,
            $dados['observacoes'] ?? null
        ]);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Síndico cadastrado.']);
        break;

    case 'PUT':
        verificarAdmin($usuario);
        $dados = json_decode(file_get_contents("php://input"), true);
        
        if (empty($dados['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID não fornecido.']);
            exit();
        }
        
        $sql = "UPDATE sindicos SET nome = ?, cpf = ?, email = ?, telefone = ?, data_inicio = ?, data_fim = ?, ativo = ?, observacoes = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dados['nome'],
            $dados['cpf'] ?? null,
            $dados['email'] ?? null,
            $dados['telefone'] ?? null,
            $dados['data_inicio'],
            $dados['data_fim'] ?? null,
            $dados['ativo'] ?? 1,
            $dados['observacoes'] ?? null,
            $dados['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Síndico atualizado.']);
        break;

    case 'DELETE':
        verificarAdmin($usuario);
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID não fornecido.']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM sindicos WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Síndico removido.']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido.']);
}
?>
