<?php
// API de Configurações Gerais

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
    $papeis = getPapeisUsuario();
    if (empty($papeis) || (!in_array('admin', $papeis) && !in_array('dev', $papeis))) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado. Apenas administradores.']);
        exit();
    }
}

switch ($metodo) {
    case 'GET':
        if (isset($_GET['chave'])) {
            $chave = $_GET['chave'];
            $stmt = $pdo->prepare("SELECT * FROM configuracoes WHERE chave = ?");
            $stmt->execute([$chave]);
            $config = $stmt->fetch();
            if ($config) {
                if ($config['tipo'] === 'file' && $config['valor']) {
                    $config['valor'] = '/' . $config['valor'];
                }
                echo json_encode($config);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Configuração não encontrada.']);
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM configuracoes ORDER BY chave");
            $configs = $stmt->fetchAll();
            foreach ($configs as &$config) {
                if ($config['tipo'] === 'file' && $config['valor']) {
                    $config['valor'] = '/' . $config['valor'];
                }
            }
            echo json_encode($configs);
        }
        break;

    case 'POST':
        verificarAdmin($usuario);
        $dados = json_decode(file_get_contents("php://input"), true);
        
        if (isset($dados['action'])) {
            switch ($dados['action']) {
                case 'salvar_config':
                    $chave = $dados['chave'] ?? '';
                    $valor = $dados['valor'] ?? '';
                    $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
                    $stmt->execute([$valor, $chave]);
                    echo json_encode(['success' => true, 'message' => 'Configuração salva.']);
                    break;
                    
                case 'upload_logo':
                    if (!isset($_FILES['logo'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Nenhum arquivo enviado.']);
                        exit();
                    }
                    $file = $_FILES['logo'];
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $novoNome = 'logo_' . uniqid() . '.' . $ext;
                    $caminho = dirname(__DIR__) . '/uploads/config/' . $novoNome;
                    
                    if (!is_dir(dirname($caminho))) {
                        mkdir(dirname($caminho), 0755, true);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $caminho)) {
                        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'condominio_logo'");
                        $stmt->execute(['uploads/config/' . $novoNome]);
                        echo json_encode(['success' => true, 'path' => '/uploads/config/' . $novoNome]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erro ao salvar arquivo.']);
                    }
                    break;
                    
                case 'upload_regimento':
                    if (!isset($_FILES['regimento'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Nenhum arquivo enviado.']);
                        exit();
                    }
                    $file = $_FILES['regimento'];
                    $content = file_get_contents($file['tmp_name']);
                    $json = json_decode($content, true);
                    
                    if ($json === null) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Arquivo JSON inválido.']);
                        exit();
                    }
                    
                    $caminho = dirname(__DIR__) . '/regimento.json';
                    if (file_put_contents($caminho, $content)) {
                        echo json_encode(['success' => true, 'message' => 'Regimento atualizado.']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erro ao salvar arquivo.']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Ação desconhecida.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Ação não especificada.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido.']);
}
?>
