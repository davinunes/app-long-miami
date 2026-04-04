<?php
// Endpoint: /api/unidades.php
// Métodos: GET, POST

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../api/helpers.php';
requireApiPapel(['admin', 'dev']);

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
        if (isset($_GET['bloco'])) {
            listarPorBloco($pdo, $_GET['bloco']);
        }
        elseif (isset($_GET['id'])) {
            buscarUnidade($pdo, (int)$_GET['id']);
        }
        elseif (isset($_GET['buscar'])) {
            buscarUnidades($pdo, $_GET['buscar']);
        }
        else {
            listarTodas($pdo);
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        if (isset($dados->importar)) {
            importarLote($pdo, $dados);
        }
        elseif (isset($dados->id)) {
            atualizarUnidade($pdo, $dados);
        }
        else {
            criarUnidade($pdo, $dados);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}

function listarTodas($pdo) {
    $stmt = $pdo->query("SELECT * FROM unidades WHERE ativo = TRUE ORDER BY bloco, numero ASC");
    http_response_code(200);
    echo json_encode($stmt->fetchAll());
}

function listarPorBloco($pdo, $bloco) {
    $stmt = $pdo->prepare("SELECT * FROM unidades WHERE bloco = ? AND ativo = TRUE ORDER BY numero ASC");
    $stmt->execute([strtoupper($bloco)]);
    http_response_code(200);
    echo json_encode($stmt->fetchAll());
}

function buscarUnidade($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM unidades WHERE id = ?");
    $stmt->execute([$id]);
    $unidade = $stmt->fetch();
    
    if (!$unidade) {
        http_response_code(404);
        echo json_encode(['message' => 'Unidade não encontrada.']);
        exit();
    }
    
    http_response_code(200);
    echo json_encode($unidade);
}

function buscarUnidades($pdo, $termo) {
    $stmt = $pdo->prepare("
        SELECT * FROM unidades 
        WHERE ativo = TRUE 
        AND (numero LIKE ? OR nome_proprietario LIKE ? OR email_proprietario LIKE ?)
        ORDER BY bloco, numero ASC
        LIMIT 50
    ");
    $like = '%' . $termo . '%';
    $stmt->execute([$like, $like, $like]);
    http_response_code(200);
    echo json_encode($stmt->fetchAll());
}

function criarUnidade($pdo, $dados) {
    if (empty($dados->bloco) || empty($dados->numero)) {
        http_response_code(400);
        echo json_encode(['message' => 'Bloco e número são obrigatórios.']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO unidades (bloco, numero, tipo, nome_proprietario, email_proprietario) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            strtoupper($dados->bloco),
            $dados->numero,
            $dados->tipo ?? 'residencial',
            $dados->nome_proprietario ?? null,
            $dados->email_proprietario ?? null
        ]);
        
        http_response_code(201);
        echo json_encode(['message' => 'Unidade criada.', 'id' => $pdo->lastInsertId()]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['message' => 'Esta unidade já existe.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Erro: ' . $e->getMessage()]);
        }
    }
}

function atualizarUnidade($pdo, $dados) {
    $id = (int)$dados->id;
    
    $stmt = $pdo->prepare("SELECT id FROM unidades WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['message' => 'Unidade não encontrada.']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE unidades 
            SET bloco = ?, numero = ?, tipo = ?, nome_proprietario = ?, email_proprietario = ?, ativo = ?
            WHERE id = ?
        ");
        $stmt->execute([
            strtoupper($dados->bloco),
            $dados->numero,
            $dados->tipo ?? 'residencial',
            $dados->nome_proprietario ?? null,
            $dados->email_proprietario ?? null,
            $dados->ativo ?? true,
            $id
        ]);
        
        http_response_code(200);
        echo json_encode(['message' => 'Unidade atualizada.']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro: ' . $e->getMessage()]);
    }
}

function importarLote($pdo, $dados) {
    if (empty($dados->unidades) || !is_array($dados->unidades)) {
        http_response_code(400);
        echo json_encode(['message' => 'Nenhuma unidade para importar.']);
        exit();
    }
    
    $importados = 0;
    $erros = [];
    
    foreach ($dados->unidades as $uni) {
        if (empty($uni['bloco']) || empty($uni['numero'])) {
            $erros[] = "Unidade sem bloco/número: " . json_encode($uni);
            continue;
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO unidades (bloco, numero, tipo, nome_proprietario, email_proprietario) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    tipo = VALUES(tipo),
                    nome_proprietario = VALUES(nome_proprietario),
                    email_proprietario = VALUES(email_proprietario)
            ");
            $stmt->execute([
                strtoupper($uni['bloco']),
                $uni['numero'],
                $uni['tipo'] ?? 'residencial',
                $uni['nome_proprietario'] ?? null,
                $uni['email_proprietario'] ?? null
            ]);
            $importados++;
            
        } catch (PDOException $e) {
            $erros[] = "Erro ao importar {$uni['bloco']}-{$uni['numero']}: " . $e->getMessage();
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'message' => "Importação concluída.",
        'importados' => $importados,
        'total' => count($dados->unidades),
        'erros' => $erros
    ]);
}
?>
