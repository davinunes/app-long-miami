<?php
// Endpoint: /api/grupos.php
// Métodos: GET, POST
// Sistema de Permissões: Grupos agora têm permissões, não papéis

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../api/helpers.php';
requireApiPermissao('grupo.listar');

require_once '../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$pdo = getDbConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['message' => 'Falha na conexão com o banco.']);
    exit();
}

switch ($metodo) {
    case 'GET':
        // Listar permissões disponíveis
        if (isset($_GET['listar_permissoes'])) {
            $modulo = $_GET['modulo'] ?? null;
            if ($modulo) {
                $stmt = $pdo->prepare("SELECT * FROM permissoes WHERE modulo = ? ORDER BY modulo, nome ASC");
                $stmt->execute([$modulo]);
            } else {
                $stmt = $pdo->query("SELECT * FROM permissoes ORDER BY modulo, nome ASC");
            }
            http_response_code(200);
            echo json_encode($stmt->fetchAll());
            break;
        }
        
        // Listar módulos de permissões
        if (isset($_GET['modulos'])) {
            $stmt = $pdo->query("SELECT DISTINCT modulo FROM permissoes ORDER BY modulo ASC");
            http_response_code(200);
            echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
            break;
        }
        
        // Buscar grupo específico
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
            
            // Busca permissões do grupo
            $stmt = $pdo->prepare("
                SELECT p.id, p.slug FROM grupo_permissoes gp 
                JOIN permissoes p ON gp.permissao_id = p.id 
                WHERE gp.grupo_id = ?
            ");
            $stmt->execute([$id]);
            $grupo['permissoes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Busca membros do grupo
            $stmt = $pdo->prepare("SELECT u.id, u.nome, u.email FROM usuarios u JOIN usuario_grupos ug ON u.id = ug.usuario_id WHERE ug.grupo_id = ?");
            $stmt->execute([$id]);
            $grupo['membros'] = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($grupo);
        } else {
            // Listar todos os grupos com suas permissões
            $stmt = $pdo->query("
                SELECT g.id, g.nome, g.descricao, g.created_at
                FROM grupos g 
                ORDER BY g.nome ASC
            ");
            $grupos = $stmt->fetchAll();
            
            foreach ($grupos as &$grupo) {
                $stmt = $pdo->prepare("
                    SELECT p.slug FROM grupo_permissoes gp 
                    JOIN permissoes p ON gp.permissao_id = p.id 
                    WHERE gp.grupo_id = ?
                ");
                $stmt->execute([$grupo['id']]);
                $grupo['permissoes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            http_response_code(200);
            echo json_encode($grupos);
        }
        break;

    case 'POST':
        requireApiPermissao('grupo.criar');
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
                
                // Adiciona permissões se especificadas
                if (!empty($dados->permissoes) && is_array($dados->permissoes)) {
                    $stmt = $pdo->prepare("INSERT INTO grupo_permissoes (grupo_id, permissao_id) VALUES (?, ?)");
                    foreach ($dados->permissoes as $permissaoId) {
                        $stmt->execute([$grupo_id, (int)$permissaoId]);
                    }
                }
                
                http_response_code(201);
                echo json_encode(['message' => 'Grupo criado com sucesso!', 'id' => $grupo_id]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao criar grupo: ' . $e->getMessage()]);
            }
        }
        // Deletar grupo
        elseif (isset($dados->deletar) && isset($dados->id)) {
            requireApiPermissao('grupo.excluir');
            $id = (int)$dados->id;
            try {
                $pdo->prepare("DELETE FROM grupo_permissoes WHERE grupo_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM usuario_grupos WHERE grupo_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM grupos WHERE id = ?")->execute([$id]);
                http_response_code(200);
                echo json_encode(['message' => 'Grupo deletado com sucesso.']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao deletar grupo: ' . $e->getMessage()]);
            }
        }
        // Atualizar grupo
        elseif (isset($dados->id)) {
            requireApiPermissao('grupo.editar');
            $id = (int)$dados->id;
            
            // Verificar se nome foi enviado
            if (!isset($dados->nome) || empty(trim($dados->nome))) {
                http_response_code(400);
                echo json_encode(['message' => 'Nome do grupo é obrigatório.']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE grupos SET nome = ?, descricao = ? WHERE id = ?");
                $stmt->execute([trim($dados->nome), $dados->descricao ?? null, $id]);
                
                // Atualiza permissões se especificadas
                if (isset($dados->permissoes)) {
                    $pdo->prepare("DELETE FROM grupo_permissoes WHERE grupo_id = ?")->execute([$id]);
                    
                    if (is_array($dados->permissoes)) {
                        $stmt = $pdo->prepare("INSERT INTO grupo_permissoes (grupo_id, permissao_id) VALUES (?, ?)");
                        foreach ($dados->permissoes as $permissaoId) {
                            $stmt->execute([$id, (int)$permissaoId]);
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
        // Deletar grupo
        elseif (isset($dados->deletar)) {
            requireApiPermissao('grupo.excluir');
            $id = (int)$dados->id;
            try {
                $pdo->prepare("DELETE FROM grupo_permissoes WHERE grupo_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM usuario_grupos WHERE grupo_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM grupos WHERE id = ?")->execute([$id]);
                http_response_code(200);
                echo json_encode(['message' => 'Grupo deletado com sucesso.']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao deletar grupo: ' . $e->getMessage()]);
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
