<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

$pdo = getDbConnection();

echo "Corrigindo caminhos de imagens em notificacao_imagens...\n\n";

$stmt = $pdo->query("
    SELECT ni.id, ni.caminho_arquivo, ni.anexo_ocorrencia_id, oa.url as caminho_original
    FROM notificacao_imagens ni
    LEFT JOIN ocorrencia_anexos oa ON ni.anexo_ocorrencia_id = oa.id
    WHERE ni.ocorrencia_id IS NOT NULL AND ni.anexo_ocorrencia_id IS NOT NULL
");
$imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($imagens as $img) {
    if ($img['caminho_original'] && $img['caminho_original'] !== $img['caminho_arquivo']) {
        $novo_caminho = ltrim($img['caminho_original'], '/');
        $stmt_update = $pdo->prepare("UPDATE notificacao_imagens SET caminho_arquivo = ? WHERE id = ?");
        $stmt_update->execute([$novo_caminho, $img['id']]);
        echo "ID {$img['id']}: {$img['caminho_arquivo']} -> {$novo_caminho}\n";
        $count++;
    }
}

echo "\nTotal corrigido: {$count} imagens\n";
