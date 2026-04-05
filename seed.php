<?php
/**
 * SEED - Popula dados iniciais do sistema
 * 
 * Este script popula apenas os dados, sem alterar estrutura.
 * Ideal para atualizar dados em produção sem perder informações.
 * 
 * Uso:
 *   - CLI: php seed.php [--force]
 *   - Navegador: acessar diretamente
 */

require_once __DIR__ . '/config.php';

$isWeb = php_sapi_name() !== 'cli';
$confirmado = false;

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Seed de Dados</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #eee; }
        pre { background: #16213e; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
        .success { background: #28a745; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { background: #17a2b8; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .btn { padding: 12px 30px; background: #667eea; color: white; border-radius: 5px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <h1>🔑 Seed de Dados</h1>
    <div class="info">
        <p>Este script popula os dados iniciais do sistema:</p>
        <ul>
            <li>Permissões</li>
            <li>Status de notificações</li>
            <li>Tipos de notificação</li>
            <li>Assuntos</li>
            <li>Grupos padrão</li>
        </ul>
        <p>Dados existentes serão IGNORADOS (INSERT IGNORE).</p>
    </div>
    <form method="POST">
        <button type="submit" class="btn">Executar Seed</button>
    </form>
';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $confirmado = true;
    } else {
        echo '</body></html>';
        exit;
    }
} else {
    echo "=== SEED de Dados ===\n\n";
}

$pdo = getDbConnection();
if (!$pdo) {
    die("Erro ao conectar ao banco.\n");
}

$output = function($msg, $type = 'info') use ($isWeb) {
    if ($isWeb) {
        $color = $type === 'success' ? '#28a745' : ($type === 'error' ? '#dc3545' : '#17a2b8');
        echo '<div style="background:' . $color . ';padding:10px;margin:5px 0;border-radius:5px;">' . htmlspecialchars($msg) . '</div>';
    } else {
        $prefix = $type === 'success' ? '✓' : ($type === 'error' ? '✗' : '  ');
        echo $prefix . ' ' . $msg . "\n";
    }
};

$count = 0;

// =====================================================
// PERMISSÕES
// =====================================================

$permissoes = [
    // Ocorrências
    ['ocorrencia.criar', 'Criar Ocorrência', 'Permite criar novas ocorrências', 'ocorrencia'],
    ['ocorrencia.editar', 'Editar Ocorrência', 'Editar qualquer ocorrência', 'ocorrencia'],
    ['ocorrencia.editar_propria', 'Editar Própria Ocorrência', 'Editar apenas ocorrências criadas', 'ocorrencia'],
    ['ocorrencia.excluir', 'Excluir Ocorrência', 'Excluir ocorrências', 'ocorrencia'],
    ['ocorrencia.listar', 'Listar Ocorrências', 'Listar todas as ocorrências', 'ocorrencia'],
    ['ocorrencia.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de ocorrências', 'ocorrencia'],
    ['ocorrencia.alterar_fase', 'Alterar Fase', 'Alterar fase da ocorrência', 'ocorrencia'],
    ['ocorrencia.homologar', 'Homologar', 'Homologar ocorrências', 'ocorrencia'],
    ['ocorrencia.recusar', 'Recusar', 'Recusar ocorrências', 'ocorrencia'],
    ['ocorrencia.gerar_notificacao', 'Gerar Notificação', 'Gerar notificação a partir da ocorrência', 'ocorrencia'],
    ['ocorrencia.colocar_em_analise', 'Colocar em Análise', 'Mudar para fase análise', 'ocorrencia'],
    ['ocorrencia.solicitar_complemento', 'Solicitar Complemento', 'Mudar para fase complemento', 'ocorrencia'],
    ['ocorrencia.finalizar', 'Finalizar', 'Mudar para fase finalizada', 'ocorrencia'],
    ['ocorrencia.marcar_pronta', 'Marcar Pronta', 'Mudar para fase pronta', 'ocorrencia'],
    ['ocorrencia.unidade.vincular', 'Vincular Unidade', 'Vincular unidades a ocorrências', 'ocorrencia'],
    ['ocorrencia.unidade.remover', 'Remover Unidade', 'Remover unidades de ocorrências', 'ocorrencia'],
    ['ocorrencia.mensagem.criar', 'Criar Mensagem', 'Adicionar mensagens', 'ocorrencia'],
    ['ocorrencia.mensagem.editar', 'Editar Mensagem', 'Editar qualquer mensagem', 'ocorrencia'],
    ['ocorrencia.mensagem.editar_propria', 'Editar Própria Mensagem', 'Editar apenas mensagens criadas', 'ocorrencia'],
    ['ocorrencia.mensagem.excluir', 'Excluir Mensagem', 'Excluir qualquer mensagem', 'ocorrencia'],
    ['ocorrencia.mensagem.excluir_propria', 'Excluir Própria Mensagem', 'Excluir apenas mensagens criadas', 'ocorrencia'],
    ['ocorrencia.evidencia.marcar', 'Marcar Evidência', 'Marcar mensagem como evidência', 'ocorrencia'],
    ['ocorrencia.evidencia.anexar', 'Anexar Evidência', 'Anexar evidência (arquivo)', 'ocorrencia'],
    ['ocorrencia.evidencia.link', 'Adicionar Link', 'Adicionar link como evidência', 'ocorrencia'],
    ['ocorrencia.evidencia.excluir', 'Excluir Evidência', 'Excluir evidência', 'ocorrencia'],
    ['ocorrencia.anexo.criar', 'Anexar Arquivo', 'Anexar arquivos', 'ocorrencia'],
    ['ocorrencia.anexo.excluir', 'Excluir Anexo', 'Excluir qualquer anexo', 'ocorrencia'],
    ['ocorrencia.anexo.excluir_proprio', 'Excluir Próprio Anexo', 'Excluir apenas anexos criados', 'ocorrencia'],
    ['ocorrencia.link.criar', 'Adicionar Link', 'Adicionar link como anexo', 'ocorrencia'],
    ['ocorrencia.link.excluir', 'Excluir Link', 'Excluir link', 'ocorrencia'],
    
    // Notificações
    ['notificacao.criar', 'Criar Notificação', 'Criar novas notificações', 'notificacao'],
    ['notificacao.editar', 'Editar Notificação', 'Editar qualquer notificação', 'notificacao'],
    ['notificacao.editar_propria', 'Editar Própria Notificação', 'Editar apenas notificações criadas', 'notificacao'],
    ['notificacao.excluir', 'Excluir Notificação', 'Excluir notificações', 'notificacao'],
    ['notificacao.excluir_propria', 'Excluir Própria Notificação', 'Excluir apenas notificações próprias', 'notificacao'],
    ['notificacao.listar', 'Listar Notificações', 'Listar todas as notificações', 'notificacao'],
    ['notificacao.ver', 'Ver Notificação', 'Ver detalhes de notificação', 'notificacao'],
    ['notificacao.lavrar', 'Lavrar Notificação', 'Assinar/lavrar notificações', 'notificacao'],
    ['notificacao.revogar_assinatura', 'Revogar Assinatura', 'Revogar assinatura de notificação', 'notificacao'],
    ['notificacao.registrar_ciencia', 'Registrar Ciência', 'Registrar ciência do morador', 'notificacao'],
    ['notificacao.gerar_pdf', 'Gerar PDF', 'Gerar PDF da notificação', 'notificacao'],
    ['notificacao.marcar_enviada', 'Marcar Enviada', 'Marcar notificação como enviada', 'notificacao'],
    ['notificacao.encerrar', 'Encerrar Notificação', 'Encerrar notificação', 'notificacao'],
    ['notificacao.retornar_rascunho', 'Retornar ao Rascunho', 'Retornar notificação lavrada para rascunho', 'notificacao'],
    ['notificacao.alterar_fase', 'Alterar Fase', 'Permissão geral para transições de fase', 'notificacao'],
    ['notificacao.registrar_recurso', 'Registrar Recurso', 'Registrar interposição de recurso', 'notificacao'],
    ['notificacao.julgar_recurso', 'Julgar Recurso', 'Deferir ou indeferir recursos', 'notificacao'],
    ['notificacao.marcar_cobranca', 'Marcar para Cobrança', 'Disponibilizar para lançamento em boleto', 'notificacao'],
    ['notificacao.reabrir', 'Reabrir Notificação', 'Reverter status encerrada ou cobrança', 'notificacao'],
    ['notificacao.acao_rapida', 'Ações Rápidas', 'Usar ações rápidas na lista', 'notificacao'],
    ['notificacao.listar_lavradas', 'Listar Lavradas', 'Ver notificações no status lavrada', 'notificacao'],
    ['notificacao.listar_enviadas', 'Listar Enviadas', 'Ver notificações no status enviada', 'notificacao'],
    ['notificacao.listar_em_cobranca', 'Listar em Cobrança', 'Ver notificações em cobrança', 'notificacao'],
    ['notificacao.editar_campos', 'Editar Campos', 'Permite editar os campos da notificação', 'notificacao'],
    ['notificacao.editar_datas', 'Editar Datas', 'Permite editar datas de envio e ciência', 'notificacao'],
    ['notificacao.imagem.anexar', 'Anexar Imagem', 'Anexar imagem à notificação', 'notificacao'],
    ['notificacao.imagem.sincronizar', 'Sincronizar Imagens', 'Sincronizar imagens da ocorrência', 'notificacao'],
    ['notificacao.imagem.remover', 'Remover Imagem', 'Remover imagem da notificação', 'notificacao'],
    ['notificacao.imagem.ativar', 'Reativar Imagem', 'Reativar imagem removida', 'notificacao'],
    ['notificacao.assunto.editar', 'Editar Assunto', 'Editar assunto da notificação', 'notificacao'],
    ['notificacao.tipo.editar', 'Editar Tipo', 'Editar tipo da notificação', 'notificacao'],
    ['notificacao.fato.adicionar', 'Adicionar Fato', 'Adicionar fato à notificação', 'notificacao'],
    ['notificacao.fato.editar', 'Editar Fato', 'Editar fato da notificação', 'notificacao'],
    ['notificacao.fato.remover', 'Remover Fato', 'Remover fato da notificação', 'notificacao'],
    ['notificacao.artigo.vincular', 'Vincular Artigo', 'Vincular artigo do regimento', 'notificacao'],
    ['notificacao.artigo.desvincular', 'Desvincular Artigo', 'Desvincular artigo', 'notificacao'],
    
    // Configurações
    ['configuracao.acessar', 'Acessar Configurações', 'Acessar página de configurações', 'configuracao'],
    ['configuracao.condominio.editar', 'Editar Condomínio', 'Editar dados do condomínio', 'configuracao'],
    ['configuracao.sindico.gerenciar', 'Gerenciar Síndicos', 'Gerenciar síndicos', 'configuracao'],
    ['configuracao.regimento.editar', 'Editar Regimento', 'Editar regimento interno', 'configuracao'],
    
    // Usuários
    ['usuario.listar', 'Listar Usuários', 'Listar usuários', 'usuario'],
    ['usuario.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de usuário', 'usuario'],
    ['usuario.criar', 'Criar Usuário', 'Criar novo usuário', 'usuario'],
    ['usuario.editar', 'Editar Usuário', 'Editar usuário', 'usuario'],
    ['usuario.editar_papeis', 'Editar Papéis', 'Alterar papéis do usuário', 'usuario'],
    ['usuario.editar_grupo', 'Editar Grupo', 'Alterar grupo do usuário', 'usuario'],
    ['usuario.excluir', 'Excluir Usuário', 'Excluir usuário', 'usuario'],
    ['usuario.trocar_senha', 'Trocar Senha', 'Trocar senha de qualquer usuário', 'usuario'],
    
    // Grupos
    ['grupo.listar', 'Listar Grupos', 'Listar grupos', 'grupo'],
    ['grupo.criar', 'Criar Grupo', 'Criar novo grupo', 'grupo'],
    ['grupo.editar', 'Editar Grupo', 'Editar grupo', 'grupo'],
    ['grupo.excluir', 'Excluir Grupo', 'Excluir grupo', 'grupo'],
    ['grupo.gerenciar_permissoes', 'Gerenciar Permissões', 'Associar/desassociar permissões', 'grupo'],
];

$output("Populando permissões...");
$stmtPerm = $pdo->prepare("INSERT IGNORE INTO permissoes (slug, nome, descricao, modulo) VALUES (?, ?, ?, ?)");
foreach ($permissoes as $p) {
    $stmtPerm->execute($p);
    $count++;
}

// =====================================================
// STATUS DE NOTIFICAÇÃO
// =====================================================

$status = [
    ['Rascunho', 'rascunho'],
    ['Lavrada', 'lavrada'],
    ['Enviada', 'enviada'],
    ['Ciente', 'ciente'],
    ['Em Recurso', 'em_recurso'],
    ['Recurso Deferido', 'recurso_deferido'],
    ['Recurso Indeferido', 'recurso_indeferido'],
    ['Em Cobrança', 'cobranca'],
    ['Encerrada', 'encerrada'],
];

$output("Populando status de notificação...");
$stmtStatus = $pdo->prepare("INSERT IGNORE INTO notificacao_status (nome, slug) VALUES (?, ?)");
foreach ($status as $s) {
    $stmtStatus->execute($s);
    $count++;
}

// =====================================================
// TIPOS DE NOTIFICAÇÃO
// =====================================================

$tipos = [
    'Advertência',
    'Multa',
    'Orientação Educativa',
    'Notificação Extrajudicial',
];

$output("Populando tipos de notificação...");
$stmtTipos = $pdo->prepare("INSERT IGNORE INTO notificacao_tipos (nome) VALUES (?)");
foreach ($tipos as $t) {
    $stmtTipos->execute([$t]);
    $count++;
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

$output("Populando assuntos...");
$stmtAss = $pdo->prepare("INSERT IGNORE INTO assuntos (descricao) VALUES (?)");
foreach ($assuntos as $a) {
    $stmtAss->execute([$a]);
    $count++;
}

// =====================================================
// GRUPOS PADRÃO
// =====================================================

$grupos = [
    ['Admin', 'Grupo de administradores com acesso total'],
    ['Conselho', 'Conselho consultivo do condomínio'],
    ['Fiscal', 'Equipe de fiscalização'],
    ['Operacional', 'Equipe operacional'],
    ['Gerencial', 'Equipe gerencial'],
];

$output("Populando grupos...");
$stmtGrupo = $pdo->prepare("INSERT IGNORE INTO grupos (nome, descricao) VALUES (?, ?)");
foreach ($grupos as $g) {
    $stmtGrupo->execute($g);
    $count++;
}

// =====================================================
// VINCULAR TODAS PERMISSÕES AO ADMIN
// =====================================================

$output("Vinculando permissões ao Admin...");
$stmtVinculo = $pdo->prepare("
    INSERT IGNORE INTO grupo_permissoes (grupo_id, permissao_id)
    SELECT g.id, p.id FROM grupos g, permissoes p WHERE g.nome = 'Admin'
");
$stmtVinculo->execute();
$count += $stmtVinculo->rowCount();

// =====================================================
// RESETAR AUTO_INCREMENT
// =====================================================

$output("Resetando contadores AUTO_INCREMENT...");
$tablesToReset = ['permissoes', 'notificacao_status', 'notificacao_tipos', 'assuntos', 'grupos'];
foreach ($tablesToReset as $table) {
    try {
        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
    } catch (PDOException $e) {
        // Ignorar erros de tabelas sem AUTO_INCREMENT
    }
}

// =====================================================
// RESULTADO
// =====================================================

$output("✅ Seed concluído! ({$count} operações)", 'success');

if ($isWeb) {
    echo '<div class="success">
        <p><strong>' . $count . '</strong> registros processados.</p>
        <p>As permissões foram vinculadas ao grupo Admin automaticamente.</p>
    </div>';
    echo '<a href="dashboard_content.php" class="btn">Ir para Dashboard</a>';
    echo '</body></html>';
} else {
    echo "\n{$count} registros processados.\n";
}
