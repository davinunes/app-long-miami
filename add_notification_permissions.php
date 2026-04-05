<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

$pdo = getDbConnection();

$novasPermissoes = [
    ['slug' => 'notificacao.acao_rapida', 'nome' => 'Ações Rápidas', 'descricao' => 'Usar ações rápidas (Enviar/Encerrar) na lista', 'modulo' => 'notificacao'],
    ['slug' => 'notificacao.listar_lavradas', 'nome' => 'Listar Lavradas', 'descricao' => 'Ver notificações no status lavrada', 'modulo' => 'notificacao'],
    ['slug' => 'notificacao.listar_enviadas', 'nome' => 'Listar Enviadas', 'descricao' => 'Ver notificações no status enviada', 'modulo' => 'notificacao'],
    ['slug' => 'notificacao.listar_em_cobranca', 'nome' => 'Listar em Cobrança', 'descricao' => 'Ver notificações em cobrança', 'modulo' => 'notificacao'],
    ['slug' => 'notificacao.editar_campos', 'nome' => 'Editar Campos', 'descricao' => 'Permite editar os campos da notificação', 'modulo' => 'notificacao'],
];

echo "Criando novas permissões...\n\n";

foreach ($novasPermissoes as $p) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissoes (slug, nome, descricao, modulo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$p['slug'], $p['nome'], $p['descricao'], $p['modulo']]);
    
    if ($stmt->rowCount() > 0) {
        echo "+ {$p['slug']} - criado\n";
    } else {
        echo "- {$p['slug']} - já existe\n";
    }
}

// Link novas permissões ao Admin
echo "\nLinkando ao grupo Admin...\n";
$stmtLink = $pdo->prepare("INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id) SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin' AND p.slug = ?");
foreach ($novasPermissoes as $p) {
    $stmtLink->execute([$p['slug']]);
    echo "  -> {$p['slug']} linkada ao Admin\n";
}

echo "\nPronto!\n";
