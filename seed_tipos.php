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
// TIPOS DE NOTIFICAÇÃO (sem descricao - tabela não tem)
// =====================================================

$tipos = [
    'Advertência',
    'Multa',
    'Orientação Educativa',
    'Notificação Extrajudicial',
];

echo "Inserindo tipos de notificação...\n";
foreach ($tipos as $nome) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO notificacao_tipos (nome) VALUES (?)");
    $stmt->execute([$nome]);
    if ($stmt->rowCount() > 0) {
        echo "  + {$nome}\n";
    } else {
        echo "  = {$nome} (já existe)\n";
    }
}

// =====================================================
// STATUS DE NOTIFICAÇÃO (usa SLUG, não ID fixo)
// =====================================================

$status = [
    ['nome' => 'Rascunho', 'slug' => 'rascunho'],
    ['nome' => 'Lavrada', 'slug' => 'lavrada'],
    ['nome' => 'Enviada', 'slug' => 'enviada'],
    ['nome' => 'Ciente', 'slug' => 'ciente'],
    ['nome' => 'Em Recurso', 'slug' => 'em_recurso'],
    ['nome' => 'Recurso Deferido', 'slug' => 'recurso_deferido'],
    ['nome' => 'Recurso Indeferido', 'slug' => 'recurso_indeferido'],
    ['nome' => 'Em Cobrança', 'slug' => 'cobranca'],
    ['nome' => 'Encerrada', 'slug' => 'encerrada'],
];

echo "\nInserindo status de notificação...\n";
foreach ($status as $s) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO notificacao_status (nome, slug) VALUES (?, ?)");
    $stmt->execute([$s['nome'], $s['slug']]);
    if ($stmt->rowCount() > 0) {
        echo "  + {$s['nome']} ({$s['slug']})\n";
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
