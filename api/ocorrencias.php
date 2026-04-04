<?php
// Endpoint: /api/ocorrencias.php
// Métodos: GET, POST

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../api/helpers.php';
requireApiPapel(['protocolar', 'diligente', 'promotor', 'admin', 'dev']);

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
        elseif (isset($dados->gerar_notificacao)) {
            gerarNotificacao($pdo, $dados, $usuario);
        }
        elseif (isset($dados->deletar_anexo)) {
            deletarAnexo($pdo, $dados, $usuario);
        }
        elseif (isset($dados->deletar_mensagem)) {
            deletarMensagem($pdo, $dados, $usuario);
        }
        else {
            criarOuAtualizar($pdo, $dados, $usuario);
        }
        break;

    case 'DELETE':
        $dados = json_decode(file_get_contents("php://input"));
        if (isset($dados->id)) {
            deletarOcorrencia($pdo, $dados, $usuario);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'ID da ocorrência é obrigatório.']);
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
    
    if ($ocorrencia['notificacao_id']) {
        $stmt = $pdo->prepare("SELECT id, numero, ano, status_id, ns.nome as status FROM notificacoes n JOIN notificacao_status ns ON n.status_id = ns.id WHERE n.id = ?");
        $stmt->execute([$ocorrencia['notificacao_id']]);
        $ocorrencia['notificacao'] = $stmt->fetch();
    }
    
    $stmt = $pdo->prepare("SELECT notificacao_id, tipo_vinculo, criado_em FROM ocorrencia_notificacoes WHERE ocorrencia_id = ?");
    $stmt->execute([$id]);
    $ocorrencia['historico_notificacoes'] = $stmt->fetchAll();
    
    http_response_code(200);
    echo json_encode($ocorrencia);
}

function listarOcorrencias($pdo) {
    $fase = $_GET['fase'] ?? null;
    
    $sql = "
        SELECT o.id, o.titulo, o.descricao_fato, o.data_fato, o.data_criacao, o.fase, o.fase_obs, o.created_by, o.updated_at, o.created_at, o.notificacao_id, u.nome as autor_nome,
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
    $stmt->execute([$usuario['id']]);
    
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
            if (!in_array($usuario['role'], ['admin', 'dev', 'promotor'])) {
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
            $stmt->execute([$dados->titulo, $dados->descricao_fato, $dados->data_fato, $usuario['id']]);
            $id = $pdo->lastInsertId();
            
            if (!empty($dados->unidades)) {
                $stmt = $pdo->prepare("INSERT INTO ocorrencia_unidades (ocorrencia_id, unidade_bloco, unidade_numero) VALUES (?, ?, ?)");
                foreach ($dados->unidades as $uni) {
                    $stmt->execute([$id, $uni->bloco ?? null, $uni->numero]);
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO ocorrencia_fase_log (ocorrencia_id, fase_anterior, fase_nova, observacao, usuario_id) VALUES (?, NULL, 'nova', ?, ?)");
            $stmt->execute([$id, 'Criação da ocorrência por ' . $usuario['nome'], $usuario['id']]);
            
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
        if (!in_array($usuario['role'], ['admin', 'dev'])) {
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
            $usuario['id'],
            $dados->mensagem,
            !empty($dados->eh_evidencia) ? 1 : 0,
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
    
    if (!in_array($usuario['role'], $papelPodeMudar[$dados->nova_fase])) {
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
    $stmt->execute([$dados->id, $faseAnterior, $dados->nova_fase, $dados->observacao ?? null, $usuario['id']]);
    
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
        if (!in_array($usuario['role'], ['admin', 'dev'])) {
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
        $stmt->execute([$ocorrenciaId, $usuario['id'], $input['tipo'], $url, $input['nome_original'], $tamanho, $mimeType]);
        
        http_response_code(201);
        echo json_encode(['message' => 'Anexo salvo.', 'id' => $pdo->lastInsertId(), 'url' => $url]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao salvar anexo: ' . $e->getMessage()]);
    }
}

function gerarNotificacao($pdo, $dados, $usuario) {
    if (empty($dados->ocorrencia_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID da ocorrência é obrigatório.']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM ocorrencias WHERE id = ?");
    $stmt->execute([$dados->ocorrencia_id]);
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['message' => 'Ocorrência não encontrada.']);
        exit();
    }
    
    if ($ocorrencia['fase'] !== 'homologada') {
        http_response_code(400);
        echo json_encode(['message' => 'Só é possível gerar notificação para ocorrências homologadas.']);
        exit();
    }
    
    if ($ocorrencia['notificacao_id']) {
        http_response_code(400);
        echo json_encode(['message' => 'Esta ocorrência já possui uma notificação vinculada.', 'notificacao_id' => $ocorrencia['notificacao_id']]);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) as max_num FROM notificacoes WHERE ano = ?");
        $stmt->execute([date('Y')]);
        $resultado = $stmt->fetch();
        $proximo_numero = ($resultado && $resultado['max_num']) ? $resultado['max_num'] + 1 : 1;
        
        $stmt = $pdo->prepare("SELECT unidade_bloco, unidade_numero FROM ocorrencia_unidades WHERE ocorrencia_id = ? LIMIT 1");
        $stmt->execute([$dados->ocorrencia_id]);
        $unidade = $stmt->fetch();
        
        $unidadeStr = $unidade ? trim(($unidade['unidade_bloco'] ?? '') . ' ' . $unidade['unidade_numero']) : '';
        $bloco = $unidade['unidade_bloco'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (unidade, bloco, numero, ano, data_emissao, cidade_emissao, texto_descritivo, assunto_id, tipo_id, status_id, ocorrencia_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $unidadeStr,
            $bloco,
            sprintf('%03d', $proximo_numero),
            date('Y'),
            date('Y-m-d'),
            $dados->cidade_emissao ?? null,
            $ocorrencia['descricao_fato'],
            $dados->assunto_id ?? 11,
            $dados->tipo_id ?? 2,
            $ocorrencia['id']
        ]);
        $notificacao_id = $pdo->lastInsertId();
        
        $pdo->prepare("UPDATE ocorrencias SET notificacao_id = ? WHERE id = ?")->execute([$notificacao_id, $ocorrencia['id']]);
        
        $pdo->prepare("INSERT INTO ocorrencia_notificacoes (ocorrencia_id, notificacao_id, tipo_vinculo) VALUES (?, ?, 'gerada')")->execute([$ocorrencia['id'], $notificacao_id]);
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Notificação gerada com sucesso!',
            'notificacao_id' => $notificacao_id,
            'numero' => sprintf('%03d/%s', $proximo_numero, date('Y'))
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao gerar notificação: ' . $e->getMessage()]);
    }
}

function deletarOcorrencia($pdo, $dados, $usuario) {
    if (!in_array('admin', $usuario['papeis']) && !in_array('dev', $usuario['papeis'])) {
        http_response_code(403);
        echo json_encode(['message' => 'Apenas administradores podem excluir ocorrências.']);
        exit();
    }
    
    $id = (int)$dados->id;
    
    $stmt = $pdo->prepare("SELECT * FROM ocorrencias WHERE id = ?");
    $stmt->execute([$id]);
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['message' => 'Ocorrência não encontrada.']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT url FROM ocorrencia_anexos WHERE ocorrencia_id = ? AND url IS NOT NULL AND url != ''");
        $stmt->execute([$id]);
        $anexos = $stmt->fetchAll();
        
        foreach ($anexos as $anexo) {
            $caminhoArquivo = dirname(__DIR__) . '/uploads/ocorrencias/' . basename($anexo['url']);
            if (file_exists($caminhoArquivo)) {
                unlink($caminhoArquivo);
            }
        }
        
        $pdo->prepare("DELETE FROM ocorrencia_fase_log WHERE ocorrencia_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM ocorrencia_mensagens WHERE ocorrencia_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM ocorrencia_anexos WHERE ocorrencia_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM ocorrencia_unidades WHERE ocorrencia_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM ocorrencia_notificacoes WHERE ocorrencia_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM ocorrencias WHERE id = ?")->execute([$id]);
        
        http_response_code(200);
        echo json_encode(['message' => 'Ocorrência excluída com sucesso.']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao excluir ocorrência: ' . $e->getMessage()]);
    }
}

function deletarAnexo($pdo, $dados, $usuario) {
    $id = (int)$dados->id;
    
    $stmt = $pdo->prepare("SELECT oa.*, o.created_by, o.fase FROM ocorrencia_anexos oa JOIN ocorrencias o ON oa.ocorrencia_id = o.id WHERE oa.id = ?");
    $stmt->execute([$id]);
    $anexo = $stmt->fetch();
    
    if (!$anexo) {
        http_response_code(404);
        echo json_encode(['message' => 'Anexo não encontrado.']);
        exit();
    }
    
    $isAdmin = in_array('admin', $usuario['papeis']) || in_array('dev', $usuario['papeis']);
    $isCriador = $anexo['created_by'] == $usuario['id'];
    
    if (!$isAdmin && !$isCriador) {
        http_response_code(403);
        echo json_encode(['message' => 'Você não tem permissão para excluir este anexo.']);
        exit();
    }
    
    if ($anexo['fase'] === 'homologada' && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['message' => 'Ocorrência homologada. Apenas administradores podem excluir anexos.']);
        exit();
    }
    
    try {
        if (!empty($anexo['url'])) {
            $caminhoArquivo = dirname(__DIR__) . '/uploads/ocorrencias/' . basename($anexo['url']);
            if (file_exists($caminhoArquivo)) {
                unlink($caminhoArquivo);
            }
        }
        
        $pdo->prepare("DELETE FROM ocorrencia_anexos WHERE id = ?")->execute([$id]);
        
        http_response_code(200);
        echo json_encode(['message' => 'Anexo excluído com sucesso.']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao excluir anexo: ' . $e->getMessage()]);
    }
}

function deletarMensagem($pdo, $dados, $usuario) {
    $id = (int)$dados->id;
    
    $stmt = $pdo->prepare("SELECT om.*, o.created_by, o.fase FROM ocorrencia_mensagens om JOIN ocorrencias o ON om.ocorrencia_id = o.id WHERE om.id = ?");
    $stmt->execute([$id]);
    $mensagem = $stmt->fetch();
    
    if (!$mensagem) {
        http_response_code(404);
        echo json_encode(['message' => 'Mensagem não encontrada.']);
        exit();
    }
    
    $isAdmin = in_array('admin', $usuario['papeis']) || in_array('dev', $usuario['papeis']);
    $isCriador = $mensagem['usuario_id'] == $usuario['id'];
    
    if (!$isAdmin && !$isCriador) {
        http_response_code(403);
        echo json_encode(['message' => 'Você não tem permissão para excluir esta mensagem.']);
        exit();
    }
    
    if ($mensagem['fase'] === 'homologada' && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['message' => 'Ocorrência homologada. Apenas administradores podem excluir mensagens.']);
        exit();
    }
    
    try {
        $pdo->prepare("DELETE FROM ocorrencia_mensagens WHERE id = ?")->execute([$id]);
        
        http_response_code(200);
        echo json_encode(['message' => 'Mensagem excluída com sucesso.']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao excluir mensagem: ' . $e->getMessage()]);
    }
}
?>
