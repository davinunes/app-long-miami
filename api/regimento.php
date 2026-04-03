<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/jwt_loader.php';

use Firebase\JWT\JWT;

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token não fornecido.']);
    exit;
}

$token = $matches[1];
try {
    JWT::decode($token, new Firebase\JWT\Key(JWT_SECRET_KEY, JWT_ALGORITHM));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido.']);
    exit;
}

$dbFile = __DIR__ . '/../regimento.json';
if (!file_exists($dbFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Banco de dados do regimento não encontrado.']);
    exit;
}

$database = json_decode(file_get_contents($dbFile), true);

$query = $_GET['q'] ?? '';
$mode = $_GET['mode'] ?? 'all';

if (strlen($query) < 1) {
    echo json_encode(['results' => []]);
    exit;
}

$results = [];

function encontrarNoCaminho($database, $notacao) {
    if (!$database || !isset($database['artigos']) || !$notacao) return null;
    $partes = explode('.', $notacao);
    $resultado = $database['artigos'][$partes[0]] ?? null;
    if (!$resultado) return null;
    for ($i = 1; $i < count($partes); $i++) {
        $encontrado = false;
        foreach (['incisos', 'paragrafos', 'alineas'] as $nivel) {
            if (isset($resultado[$nivel][$partes[$i]])) {
                $resultado = $resultado[$nivel][$partes[$i]];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) return null;
    }
    return $resultado;
}

function getCapituloInfo($database, $artigoNum) {
    if (!isset($database['artigos'][$artigoNum])) return null;
    $capituloNum = $database['artigos'][$artigoNum]['capitulo'] ?? null;
    return [
        'numero' => $capituloNum,
        'titulo' => $database['capitulos'][$capituloNum] ?? ''
    ];
}

if (preg_match('/^\d+$/', $query)) {
    $artigoNum = $query;
    if (isset($database['artigos'][$artigoNum])) {
        $artigoObj = $database['artigos'][$artigoNum];
        $capituloInfo = getCapituloInfo($database, $artigoNum);
        
        $results[] = [
            'notacao' => $artigoNum,
            'texto' => $artigoObj['titulo_artigo'] ?? $artigoObj['texto'] ?? "Artigo $artigoNum",
            'capitulo' => $capituloInfo
        ];
        
        if (isset($artigoObj['paragrafos'])) {
            foreach ($artigoObj['paragrafos'] as $key => $paragrafo) {
                $results[] = [
                    'notacao' => "{$artigoNum}.p{$key}",
                    'texto' => $paragrafo['texto'] ?? "Parágrafo {$key}",
                    'capitulo' => $capituloInfo
                ];
            }
        }
        
        if (isset($artigoObj['incisos'])) {
            foreach ($artigoObj['incisos'] as $key => $inciso) {
                $results[] = [
                    'notacao' => "{$artigoNum}.i{$key}",
                    'texto' => $inciso['texto'] ?? "Inciso {$key}",
                    'capitulo' => $capituloInfo
                ];
            }
        }
    }
} else if (strlen($query) >= 2) {
    $termoBusca = mb_strtolower($query);
    
    function explorar($obj, $caminho, $database, &$resultados, $termoBusca) {
        if (isset($obj['texto']) && mb_strpos(mb_strtolower($obj['texto']), $termoBusca) !== false) {
            $artigoNum = explode('.', $caminho)[0];
            $resultados[] = [
                'notacao' => $caminho,
                'texto' => $obj['texto'],
                'capitulo' => getCapituloInfo($database, $artigoNum)
            ];
        }
        foreach (['paragrafos', 'incisos', 'alineas'] as $chave) {
            if (isset($obj[$chave]) && is_array($obj[$chave])) {
                foreach ($obj[$chave] as $subChave => $subObj) {
                    explorar($subObj, "{$caminho}.{$subChave}", $database, $resultados, $termoBusca);
                }
            }
        }
    }
    
    foreach ($database['artigos'] as $artigoNum => $artigoObj) {
        explorar($artigoObj, $artigoNum, $database, $results, $termoBusca);
    }
}

echo json_encode(['results' => array_slice($results, 0, 50)]);
