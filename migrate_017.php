<?php
/**
 * Migration 017 - Adicionar campo data_envio
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

$pdo = getDbConnection();
if (!$pdo) {
    die("Erro ao conectar ao banco de dados.\n");
}

echo "Verificando campo data_envio...\n";

// Adicionar campo data_envio se não existir
$stmt = $pdo->query("SHOW COLUMNS FROM notificacoes LIKE 'data_envio'");
if ($stmt->fetch()) {
    echo "Campo data_envio já existe.\n";
} else {
    echo "Adicionando campo data_envio...\n";
    $pdo->exec("ALTER TABLE notificacoes ADD COLUMN data_envio DATETIME NULL AFTER data_lavratura");
    echo "Campo data_envio adicionado com sucesso!\n";
}

// Criar permissão para editar datas
echo "\nCriando permissão notificacao.editar_datas...\n";
$stmt = $pdo->prepare("INSERT IGNORE INTO permissoes (slug, nome, descricao, modulo) VALUES (?, ?, ?, ?)");
$stmt->execute(['notificacao.editar_datas', 'Editar Datas', 'Permite editar datas de envio e ciência', 'notificacao']);
if ($stmt->rowCount() > 0) {
    echo "Permissão criada!\n";
} else {
    echo "Permissão já existe.\n";
}

echo "\nLinkando ao Admin...\n";
$stmt = $pdo->prepare("INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id) SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin' AND p.slug = 'notificacao.editar_datas'");
$stmt->execute();

echo "Pronto!\n";
