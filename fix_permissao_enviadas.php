<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

$pdo = getDbConnection();

echo "Corrigindo permissões...\n\n";

// Atualizar apenas o nome (não o slug) para manter compatibilidade
echo "Atualizando nome da permissão 'listar_lavradas'...\n";
$stmt = $pdo->prepare("UPDATE permissoes SET nome = 'Listar Enviadas/Lavrada', descricao = 'Ver notificações nos status lavrada e enviada' WHERE slug = 'notificacao.listar_lavradas'");
$stmt->execute();
if ($stmt->rowCount() > 0) {
    echo "  + Permissão atualizada!\n";
} else {
    echo "  - Permissão não encontrada ou já atualizada.\n";
}

echo "\nVerificando resultado...\n";
$stmt = $pdo->query("SELECT id, slug, nome FROM permissoes WHERE slug IN ('notificacao.listar_enviadas', 'notificacao.listar_lavradas')");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nPronto!\n";
