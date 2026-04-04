<?php
/**
 * API PHP para gerar PDF de notificação
 * Substitui a verificação JWT pela sessão PHP
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers.php';
requireApiLogin();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

$pythonApiUrl = getenv('PYTHON_API_URL') ?: 'http://app:5000';

$ch = curl_init($pythonApiUrl . '/gerar_documento');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($input),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Erro ao comunicar com o serviço de PDF: ' . $error]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo $response;
    exit;
}

if (strpos($response, '%PDF') === 0) {
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($response));
} else {
    header('Content-Type: application/json; charset=utf-8');
}

echo $response;
