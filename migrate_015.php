<?php
/**
 * Migration 015 - Ciclo de Vida das Notificações
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

$pdo = getDbConnection();
if (!$pdo) {
    die("Erro ao conectar ao banco de dados.\n");
}

echo "=== Migration 015: Ciclo de Vida das Notificações ===\n\n";

// 1. Novas permissões para a fase de recurso e controle de fluxo
$permissoes = [
    ['notificacao.registrar_recurso', 'Registrar Recurso', 'Registrar a interposição de recurso pelo morador', 'notificacao'],
    ['notificacao.julgar_recurso', 'Julgar Recurso', 'Deferir ou indeferir recursos de notificação', 'notificacao'],
    ['notificacao.marcar_cobranca', 'Marcar para Cobrança', 'Disponibilizar a notificação para lançamento em boleto', 'notificacao'],
    ['notificacao.encerrar', 'Encerrar Notificação', 'Marcar que a cobrança já foi lançada no boleto', 'notificacao'],
    ['notificacao.reabrir', 'Reabrir Notificação', 'Reverter o status encerrada ou cobrança para a fase anterior', 'notificacao'],
    ['notificacao.lavrar', 'Lavrar Notificação', 'Assinar a notificação para que se torne um documento oficial', 'notificacao'],
    ['notificacao.marcar_enviada', 'Marcar como Enviada', 'Indicar que a notificação foi entregue ao destinatário', 'notificacao'],
    ['notificacao.registrar_ciencia', 'Registrar Ciência', 'Registrar que o morador tomou ciência da notificação', 'notificacao'],
    ['notificacao.alterar_fase', 'Alterar Fase', 'Permissão geral para transições de fase da notificação', 'notificacao'],
];

echo "Inserindo permissões...\n";
foreach ($permissoes as $p) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissoes (slug, nome, descricao, modulo) VALUES (?, ?, ?, ?)");
    $stmt->execute($p);
    echo "  - {$p[0]}\n";
}

// 2. Adicionar novos campos na tabela notificacoes
echo "\nAdicionando campos na tabela notificacoes...\n";

$campos = [
    ['data_lavratura', 'DATETIME DEFAULT NULL'],
    ['lavrada_por', 'INT DEFAULT NULL'],
    ['data_ciencia', 'DATETIME DEFAULT NULL'],
    ['ciencia_por', 'VARCHAR(100) DEFAULT NULL'],
    ['tem_recurso', 'BOOLEAN DEFAULT FALSE'],
    ['data_recurso', 'DATETIME DEFAULT NULL'],
    ['prazo_recurso_expira', 'DATE DEFAULT NULL'],
    ['recurso_texto', 'TEXT DEFAULT NULL'],
    ['recurso_status', "VARCHAR(20) DEFAULT NULL"],
    ['encerrada', 'BOOLEAN DEFAULT FALSE'],
    ['data_encerramento', 'DATETIME DEFAULT NULL'],
    ['motivo_encerramento', 'VARCHAR(50) DEFAULT NULL'],
];

foreach ($campos as $campo) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM notificacoes LIKE ?");
    $stmt->execute([$campo[0]]);
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE notificacoes ADD COLUMN {$campo[0]} {$campo[1]}");
        echo "  + {$campo[0]}\n";
    } else {
        echo "  = {$campo[0]} (já existe)\n";
    }
}

// 3. Criar tabela de histórico de fases
echo "\nCriando tabela notificacao_fase_log...\n";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS notificacao_fase_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notificacao_id INT NOT NULL,
        usuario_id INT DEFAULT NULL,
        fase_anterior VARCHAR(50) DEFAULT NULL,
        fase_nova VARCHAR(50) NOT NULL,
        observacao TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_notificacao_fase (notificacao_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 4. Atualizar status na tabela notificacao_status
echo "\nAtualizando status da notificação...\n";
$pdo->exec("TRUNCATE TABLE notificacao_status");
$status = [
    [1, 'Rascunho', 'rascunho'],
    [2, 'Lavrada', 'lavrada'],
    [3, 'Enviada', 'enviada'],
    [4, 'Ciente', 'ciente'],
    [5, 'Em Recurso', 'em_recurso'],
    [6, 'Recurso Deferido', 'recurso_deferido'],
    [7, 'Recurso Indeferido', 'recurso_indeferido'],
    [8, 'Em Cobrança', 'cobranca'],
    [9, 'Encerrada', 'encerrada'],
];

foreach ($status as $s) {
    $stmt = $pdo->prepare("INSERT INTO notificacao_status (id, nome, slug) VALUES (?, ?, ?)");
    $stmt->execute($s);
    echo "  + {$s[1]} ({$s[2]})\n";
}

// 5. Linkar permissões ao Admin
echo "\nVinculando permissões ao Admin...\n";
$stmt = $pdo->prepare("INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id) SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin' AND p.slug = ?");
foreach (array_column($permissoes, 0) as $slug) {
    $stmt->execute([$slug]);
    echo "  + {$slug} -> Admin\n";
}

echo "\n✅ Migration 015 concluída!\n";
