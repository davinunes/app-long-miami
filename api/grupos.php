<?php
// Endpoint: /api/grupos.php
// Métodos: GET, POST
// Requer: autenticação JWT

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
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
            $stmt->execute([$id]);
            $grupo = $stmt->fetch();
            
            if (!$grupo) {
                http_response_code(404);
                echo json_encode(['message' => 'Grupo não encontrado.']);
                exit();
            }
            
            // Busca papéis do grupo
            $stmt = $pdo->prepare("SELECT papel_slug FROM grupo_papeis WHERE grupo_id = ?");
            $stmt->execute([$id]);
            $grupo['papeis'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Busca membros do grupo
            $stmt = $pdo->prepare("SELECT u.id, u.nome, u.email FROM usuarios u JOIN usuario_grupos ug ON u.id = ug.usuario_id WHERE ug.grupo_id = ?");
            $stmt->execute([$id]);
            $grupo['membros'] = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($grupo);
        } else if (isset($_GET['todos_papeis'])) {
            $stmt = $pdo->query("SELECT * FROM papeles ORDER BY nome ASC");
            http_response_code(200);
            echo json_encode($stmt->fetchAll());
        } else {
            $stmt = $pdo->query("SELECT g.*, GROUP_CONCAT(gp.papel_slug) as papeles FROM grupos g LEFT JOIN grupo_papeis gp ON g.id = gp.grupo_id GROUP BY g.id ORDER BY g.nome ASC");
            $grupos = $stmt->fetchAll();
            
            foreach ($grupos as &$grupo) {
                $grupo['papeis'] = $grupo['papeis'] ? explode(',', $grupo['papeis']) : [];
            }
            
            http_response_code(200);
            echo json_encode($grupos);
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        // Criar grupo
        if (isset($dados->criar_grupo)) {
            if (empty($dados->nome)) {
                http_response_code(400);
                echo json_encode(['message' => 'Nome do grupo é obrigatório.']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO grupos (nome, descricao) VALUES (?, ?)");
                $stmt->execute([$dados->nome, $dados->descricao ?? null]);
                $grupo_id = $pdo->lastInsertId();
                
                // Adiciona papéis se especificados
                if (!empty($dados->papeis) && is_array($dados->papeis)) {
                    $stmt = $pdo->prepare("INSERT INTO grupo_papeis (grupo_id, papel_slug) VALUES (?, ?)");
                    foreach ($dados->papeis as $papel) {
                        $stmt->execute([$grupo_id, $papel]);
                    }
                }
                
                http_response_code(201);
                echo json_encode(['message' => 'Grupo criado com sucesso!', 'id' => $grupo_id]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao criar grupo: ' . $e->getMessage()]);
            }
        }
        // Atualizar grupo
        elseif (isset($dados->id)) {
            $id = (int)$dados->id;
            
            try {
                $stmt = $pdo->prepare("UPDATE grupos SET nome = ?, descricao = ? WHERE id = ?");
                $stmt->execute([$dados->nome, $dados->descricao ?? null, $id]);
                
                // Atualiza papéis
                if (isset($dados->papeis)) {
                    $pdo->prepare("DELETE FROM grupo_papeis WHERE grupo_id = ?")->execute([$id]);
                    
                    if (is_array($dados->papeis)) {
                        $stmt = $pdo->prepare("INSERT INTO grupo_papeis (grupo_id, papel_slug) VALUES (?, ?)");
                        foreach ($dados->papeis as $papel) {
                            $stmt->execute([$id, $papel]);
                        }
                    }
                }
                
                http_response_code(200);
                echo json_encode(['message' => 'Grupo atualizado com sucesso!']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao atualizar grupo: ' . $e->getMessage()]);
            }
        }
        // Adicionar membro ao grupo
        elseif (isset($dados->adicionar_membro)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO usuario_grupos (usuario_id, grupo_id) VALUES (?, ?)");
                $stmt->execute([$dados->usuario_id, $dados->grupo_id]);
                http_response_code(200);
                echo json_encode(['message' => 'Membro adicionado ao grupo.']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro: ' . $e->getMessage()]);
            }
        }
        // Remover membro do grupo
        elseif (isset($dados->remover_membro)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuario_grupos WHERE usuario_id = ? AND grupo_id = ?");
                $stmt->execute([$dados->usuario_id, $dados->grupo_id]);
                http_response_code(200);
                echo json_encode(['message' => 'Membro removido do grupo.']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro: ' . $e->getMessage()]);
            }
        }
        // Deletar grupo
        elseif (isset($dados->deletar)) {
            $id = (int)$dados->id;
            try {
                $pdo->prepare("DELETE FROM grupo_papeis WHERE grupo_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM usuario_grupos WHERE grupo_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM grupos WHERE id = ?")->execute([$id]);
                http_response_code(200);
                echo json_encode(['message' => 'Grupo deletado com sucesso.']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao deletar grupo: ' . $e->getMessage()]);
            }
        }
        // Listar papéis disponíveis
        elseif (isset($_GET['listar_papeis'])) {
            $stmt = $pdo->query("SELECT * FROM papeles ORDER BY nome ASC");
            http_response_code(200);
            echo json_encode($stmt->fetchAll());
        }
        else {
            http_response_code(400);
            echo json_encode(['message' => 'Ação não especificada.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}
?>
