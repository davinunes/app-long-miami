<?php
// Endpoint: /api/ocorrencias.php
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

$usuario = verificarTokenEAutorizar(['protocolar', 'diligente', 'promotor', 'admin', 'dev']);

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
            buscarOcorrencia($pdo, (int)$_GET['id'], $usuario);
        }
        elseif (isset($_GET['homologadas'])) {
            listarHomologadas($pdo);
        }
        elseif (isset($_GET['minhas'])) {
            listarMinhas($pdo, $usuario);
        }
        else {
            listarOcorrencias($pdo);
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        if (isset($dados->mensagem)) {
            adicionarMensagem($pdo, $dados, $usuario);
        }
        elseif (isset($dados->mudar_fase)) {
            mudarFase($pdo, $dados, $usuario);
        }
        elseif (isset($_GET['upload'])) {
            fazerUpload($pdo, $usuario);
        }
        else {
            criarOuAtualizar($pdo, $dados, $usuario);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}

function buscarOcorrencia($pdo, $id, $usuario) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.nome as autor_nome 
        FROM ocorrencias o 
        LEFT JOIN usuarios u ON o.created_by = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['message' => 'Ocorrência não encontrada.']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM ocorrencia_unidades WHERE ocorrencia_id = ?");
    $stmt->execute([$id]);
    $ocorrencia['unidades'] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome as autor_nome 
        FROM ocorrencia_mensagens m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id 
        WHERE m.ocorrencia_id = ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$id]);
    $ocorrencia['mensagens'] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM ocorrencia_anexos WHERE ocorrencia_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $ocorrencia['anexos'] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM ocorrencia_fase_log WHERE ocorrencia_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $ocorrencia['fase_log'] = $stmt->fetchAll();
    
    http_response_code(200);
    echo json_encode($ocorrencia);
}

function listarOcorrencias($pdo) {
    $fase = $_GET['fase'] ?? null;
    
    $sql = "
        SELECT o.*, u.nome as autor_nome,
               GROUP_CONCAT(CONCAT(COALESCE(ou.unidade_bloco, ''), ou.unidade_numero) SEPARATOR ', ') as unidades
        FROM ocorrencias o
        LEFT JOIN usuarios u ON o.created_by = u.id
        LEFT JOIN ocorrencia_unidades ou ON o.id = ou.ocorrencia_id
    ";
    
    if ($fase) {
        $sql .= " WHERE o.fase = ?";
        $sql .= " GROUP BY o.id ORDER BY o.data_criacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fase]);
    } else {
        $sql .= " GROUP BY o.id ORDER BY o.data_criacao DESC";
        $stmt = $pdo->query($sql);
    }
    
    $ocorrencias = $stmt->fetchAll();
    
    foreach ($ocorrencias as &$o) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ocorrencia_mensagens WHERE ocorrencia_id = ? AND eh_evidencia = TRUE");
        $stmt->execute([$o['id']]);
        $o['total_evidencias'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ocorrencia_anexos WHERE ocorrencia_id = ?");
        $stmt->execute([$o['id']]);
        $o['total_anexos'] = (int)$stmt->fetchColumn();
    }
    
    http_response_code(200);
    echo json_encode($ocorrencias);
}

function listarHomologadas($pdo) {
    $stmt = $pdo->query("
        SELECT o.*, u.nome as autor_nome,
               GROUP_CONCAT(CONCAT(COALESCE(ou.unidade_bloco, ''), ou.unidade_numero) SEPARATOR ', ') as unidades
        FROM ocorrencias o
        LEFT JOIN usuarios u ON o.created_by = u.id
        LEFT JOIN ocorrencia_unidades ou ON o.id = ou.ocorrencia_id
        WHERE o.fase = 'homologada'
        GROUP BY o.id
        ORDER BY o.data_criacao DESC
    ");
    
    http_response_code(200);
    echo json_encode($stmt->fetchAll());
}

function listarMinhas($pdo, $usuario) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.nome as autor_nome,
               GROUP_CONCAT(CONCAT(COALESCE(ou.unidade_bloco, ''), ou.unidade_numero) SEPARATOR ', ') as unidades
        FROM ocorrencias o
        LEFT JOIN usuarios u ON o.created_by = u.id
        LEFT JOIN ocorrencia_unidades ou ON o.id = ou.ocorrencia_id
        WHERE o.created_by = ?
        GROUP BY o.id
        ORDER BY o.data_criacao DESC
    ");
    $stmt->execute([$usuario->id]);
    
    http_response_code(200);
    echo json_encode($stmt->fetchAll());
}

function criarOuAtualizar($pdo, $dados, $usuario) {
    if (empty($dados->titulo) || empty($dados->descricao_fato) || empty($dados->data_fato)) {
        http_response_code(400);
        echo json_encode(['message' => 'Título, descrição e data do fato são obrigatórios.']);
        exit();
    }
    
    if (isset($dados->id) && !empty($dados->id)) {
        $id = (int)$dados->id;
        
        $stmt = $pdo->prepare("SELECT created_by, fase FROM ocorrencias WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['message' => 'Ocorrência não encontrada.']);
            exit();
        }
        
        if ($existing['fase'] !== 'nova' && $existing['fase'] !== 'em_analise') {
            if (!in_array($usuario->role, ['admin', 'dev', 'promotor'])) {
                http_response_code(403);
                echo json_encode(['message' => 'Não é possível editar ocorrências nesta fase.']);
                exit();
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE ocorrencias 
            SET titulo = ?, descricao_fato = ?, data_fato = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$dados->titulo, $dados->descricao_fato, $dados->data_fato, $id]);
        
        if (!empty($dados->unidades)) {
            $pdo->prepare("DELETE FROM ocorrencia_unidades WHERE ocorrencia_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("INSERT INTO ocorrencia_unidades (ocorrencia_id, unidade_bloco, unidade_numero) VALUES (?, ?, ?)");
            foreach ($dados->unidades as $uni) {
                $stmt->execute([$id, $uni->bloco ?? null, $uni->numero]);
            }
        }
        
        http_response_code(200);
        echo json_encode(['message' => 'Ocorrência atualizada com sucesso!', 'id' => $id]);
        
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ocorrencias (titulo, descricao_fato, data_fato, created_by, fase) 
                VALUES (?, ?, ?, ?, 'nova')
            ");
            $stmt->execute([$dados->titulo, $dados->descricao_fato, $dados->data_fato, $usuario->id]);
            $id = $pdo->lastInsertId();
            
            if (!empty($dados->unidades)) {
                $stmt = $pdo->prepare("INSERT INTO ocorrencia_unidades (ocorrencia_id, unidade_bloco, unidade_numero) VALUES (?, ?, ?)");
                foreach ($dados->unidades as $uni) {
                    $stmt->execute([$id, $uni->bloco ?? null, $uni->numero]);
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO ocorrencia_fase_log (ocorrencia_id, fase_anterior, fase_nova, observacao, usuario_id) VALUES (?, NULL, 'nova', 'Criação da ocorrência', ?)");
            $stmt->execute([$id, $usuario->id]);
            
            http_response_code(201);
            echo json_encode(['message' => 'Ocorrência criada com sucesso!', 'id' => $id]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Erro ao criar ocorrência: ' . $e->getMessage()]);
        }
    }
}

function adicionarMensagem($pdo, $dados, $usuario) {
    if (empty($dados->ocorrencia_id) || empty($dados->mensagem)) {
        http_response_code(400);
        echo json_encode(['message' => 'Ocorrência e mensagem são obrigatórios.']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT fase FROM ocorrencias WHERE id = ?");
    $stmt->execute([$dados->ocorrencia_id]);
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['message' => 'Ocorrência não encontrada.']);
        exit();
    }
    
    if ($ocorrencia['fase'] === 'homologada') {
        if (!in_array($usuario->role, ['admin', 'dev'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Ocorrência homologada não aceita novas evidências.']);
            exit();
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ocorrencia_mensagens (ocorrencia_id, usuario_id, mensagem, eh_evidencia, tipo_anexo, anexo_url, anexo_nome)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $dados->ocorrencia_id,
            $usuario->id,
            $dados->mensagem,
            $dados->eh_evidencia ?? false,
            $dados->tipo_anexo ?? null,
            $dados->anexo_url ?? null,
            $dados->anexo_nome ?? null
        ]);
        
        $msgId = $pdo->lastInsertId();
        
        http_response_code(201);
        echo json_encode(['message' => 'Mensagem adicionada.', 'id' => $msgId]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao adicionar mensagem: ' . $e->getMessage()]);
    }
}

function mudarFase($pdo, $dados, $usuario) {
    $fasesPermitidas = ['nova', 'em_analise', 'recusada', 'homologada'];
    
    if (empty($dados->id) || empty($dados->nova_fase)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID e nova fase são obrigatórios.']);
        exit();
    }
    
    if (!in_array($dados->nova_fase, $fasesPermitidas)) {
        http_response_code(400);
        echo json_encode(['message' => 'Fase inválida.']);
        exit();
    }
    
    $papelPodeMudar = [
        'nova' => ['protocolar', 'admin', 'dev'],
        'em_analise' => ['promotor', 'admin', 'dev'],
        'recusada' => ['promotor', 'admin', 'dev'],
        'homologada' => ['promotor', 'admin', 'dev']
    ];
    
    if (!in_array($usuario->role, $papelPodeMudar[$dados->nova_fase])) {
        http_response_code(403);
        echo json_encode(['message' => 'Você não tem permissão para definir esta fase.']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT fase FROM ocorrencias WHERE id = ?");
    $stmt->execute([$dados->id]);
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['message' => 'Ocorrência não encontrada.']);
        exit();
    }
    
    $faseAnterior = $ocorrencia['fase'];
    
    $stmt = $pdo->prepare("UPDATE ocorrencias SET fase = ? WHERE id = ?");
    $stmt->execute([$dados->nova_fase, $dados->id]);
    
    $stmt = $pdo->prepare("INSERT INTO ocorrencia_fase_log (ocorrencia_id, fase_anterior, fase_nova, observacao, usuario_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$dados->id, $faseAnterior, $dados->nova_fase, $dados->observacao ?? null, $usuario->id]);
    
    http_response_code(200);
    echo json_encode(['message' => "Fase alterada de '$faseAnterior' para '$dados->nova_fase'."]);
}

function fazerUpload($pdo, $usuario) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido para upload.']);
        exit();
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input['ocorrencia_id']) || empty($input['tipo']) || empty($input['nome_original'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Dados incompletos para upload.']);
        exit();
    }
    
    $tiposPermitidos = ['imagem', 'video', 'audio', 'documento', 'link'];
    if (!in_array($input['tipo'], $tiposPermitidos)) {
        http_response_code(400);
        echo json_encode(['message' => 'Tipo de anexo inválido.']);
        exit();
    }
    
    if ($input['tipo'] !== 'link' && empty($input['dados'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Dados do arquivo não fornecidos.']);
        exit();
    }
    
    $ocorrenciaId = (int)$input['ocorrencia_id'];
    $stmt = $pdo->prepare("SELECT fase FROM ocorrencias WHERE id = ?");
    $stmt->execute([$ocorrenciaId]);
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['message' => 'Ocorrência não encontrada.']);
        exit();
    }
    
    if ($ocorrencia['fase'] === 'homologada') {
        if (!in_array($usuario->role, ['admin', 'dev'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Ocorrência homologada não aceita novos anexos.']);
            exit();
        }
    }
    
    try {
        $url = $input['url'] ?? '';
        $tamanho = $input['tamanho_bytes'] ?? null;
        $mimeType = $input['mime_type'] ?? null;
        
        if ($input['tipo'] !== 'link' && !empty($input['dados'])) {
            $uploadDir = '../uploads/ocorrencias/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($input['nome_original'], PATHINFO_EXTENSION);
            $novoNome = uniqid() . '_' . time() . '.' . $ext;
            $caminho = $uploadDir . $novoNome;
            
            $dadosBin = base64_decode($input['dados']);
            file_put_contents($caminho, $dadosBin);
            
            $url = '/uploads/ocorrencias/' . $novoNome;
            $tamanho = strlen($dadosBin);
            $mimeType = $input['mime_type'] ?? 'application/octet-stream';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO ocorrencia_anexos (ocorrencia_id, usuario_id, tipo, url, nome_original, tamanho_bytes, mime_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ocorrenciaId, $usuario->id, $input['tipo'], $url, $input['nome_original'], $tamanho, $mimeType]);
        
        http_response_code(201);
        echo json_encode(['message' => 'Anexo salvo.', 'id' => $pdo->lastInsertId(), 'url' => $url]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao salvar anexo: ' . $e->getMessage()]);
    }
}
?>
