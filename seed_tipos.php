<?php
/**
 * Seed - Tipos e Status de Notificação
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

$pdo = getDbConnection();
if (!$pdo) {
    die("Erro ao conectar ao banco.\n");
}

echo "=== Seed: Tipos e Status de Notificação ===\n\n";

// =====================================================
// TIPOS DE NOTIFICAÇÃO
// =====================================================

$tipos = [
    ['nome' => 'Advertência', 'descricao' => 'Notificação para fatos leves que não geram multa.'],
    ['nome' => 'Multa', 'descricao' => 'Infração de que gera cobrança.'],
    ['nome' => 'Orientação Educativa', 'descricao' => 'Sabor Advertência.'],
    ['nome' => 'Notificação Extrajudicial', 'descricao' => 'Ultimato.'],
];

echo "Inserindo tipos de notificação...\n";
foreach ($tipos as $t) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO notificacao_tipos (nome, descricao) VALUES (?, ?)");
    $stmt->execute([$t['nome'], $t['descricao']]);
    if ($stmt->rowCount() > 0) {
        echo "  + {$t['nome']}\n";
    } else {
        echo "  = {$t['nome']} (já existe)\n";
    }
}

// =====================================================
// STATUS DE NOTIFICAÇÃO
// =====================================================

$status = [
    ['id' => 1, 'nome' => 'Rascunho', 'slug' => 'rascunho'],
    ['id' => 2, 'nome' => 'Lavrada', 'slug' => 'lavrada'],
    ['id' => 3, 'nome' => 'Enviada', 'slug' => 'enviada'],
    ['id' => 4, 'nome' => 'Ciente', 'slug' => 'ciente'],
    ['id' => 5, 'nome' => 'Em Recurso', 'slug' => 'em_recurso'],
    ['id' => 6, 'nome' => 'Recurso Deferido', 'slug' => 'recurso_deferido'],
    ['id' => 7, 'nome' => 'Recurso Indeferido', 'slug' => 'recurso_indeferido'],
    ['id' => 8, 'nome' => 'Em Cobrança', 'slug' => 'cobranca'],
    ['id' => 9, 'nome' => 'Encerrada', 'slug' => 'encerrada'],
];

echo "\nInserindo status de notificação...\n";
foreach ($status as $s) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO notificacao_status (id, nome, slug) VALUES (?, ?, ?)");
    $stmt->execute([$s['id'], $s['nome'], $s['slug']]);
    if ($stmt->rowCount() > 0) {
        echo "  + {$s['nome']}\n";
    } else {
        echo "  = {$s['nome']} (já existe)\n";
    }
}

// =====================================================
// ASSUNTOS
// =====================================================

$assuntos = [
    'Pertubação do Sossego',
    'Uso da área comum sem autorização',
    'Estacionamento irregular',
    'Vazamento de água',
    'Deveres com Animais de Estimação',
    'Lixo em local inadequado',
    'Manutenção de fachada',
    'Alteração não autorizada na unidade',
    'Lançamento de objetos',
    'Fumo em área comum',
    'Prática esportiva em local proibido',
    'Veículo em local proibido',
    'Circulação de pessoas não autorizadas',
    'Desperdício de água',
    'Danos às áreas comuns',
    'Desobediência às normas do regimento',
    'Infração de caráter ambiental',
    'Ausência de identificação',
    'Outros',
];

echo "\nInserindo assuntos...\n";
foreach ($assuntos as $a) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO assuntos (descricao) VALUES (?)");
    $stmt->execute([$a]);
    if ($stmt->rowCount() > 0) {
        echo "  + {$a}\n";
    } else {
        echo "  = {$a} (já existe)\n";
    }
}

echo "\n✅ Seed concluído!\n";
