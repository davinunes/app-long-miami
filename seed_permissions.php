<?php
/**
 * Script para popular a tabela de permissões com base nos migrates antigos
 * 
 * Uso:
 *   - Navegador: acessar diretamente
 *   - CLI: php seed_permissions.php
 */

require_once __DIR__ . '/config.php';

$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Permissões</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #eee; }
        .card { background: #16213e; padding: 25px; border-radius: 10px; margin: 20px 0; }
        pre { background: #0f3460; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
        .success { background: #28a745; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #dc3545; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; margin-top: 15px; }
        h1 { color: #667eea; }
    </style>
</head>
<body>
    <h1>🔑 Seed de Permissões</h1>
    <div class="card">
';
}

$permissoes = [
    // =====================================================
    // PERMISSÕES DE OCORRÊNCIAS
    // =====================================================
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
    
    // Unidades da Ocorrência
    ['ocorrencia.unidade.vincular', 'Vincular Unidade', 'Vincular unidades a ocorrências', 'ocorrencia'],
    ['ocorrencia.unidade.remover', 'Remover Unidade', 'Remover unidades de ocorrências', 'ocorrencia'],
    
    // Mensagens
    ['ocorrencia.mensagem.criar', 'Criar Mensagem', 'Adicionar mensagens', 'ocorrencia'],
    ['ocorrencia.mensagem.editar', 'Editar Mensagem', 'Editar qualquer mensagem', 'ocorrencia'],
    ['ocorrencia.mensagem.editar_propria', 'Editar Própria Mensagem', 'Editar apenas mensagens criadas', 'ocorrencia'],
    ['ocorrencia.mensagem.excluir', 'Excluir Mensagem', 'Excluir qualquer mensagem', 'ocorrencia'],
    ['ocorrencia.mensagem.excluir_propria', 'Excluir Própria Mensagem', 'Excluir apenas mensagens criadas', 'ocorrencia'],
    
    // Evidências
    ['ocorrencia.evidencia.marcar', 'Marcar Evidência', 'Marcar mensagem como evidência', 'ocorrencia'],
    ['ocorrencia.evidencia.anexar', 'Anexar Evidência', 'Anexar evidência (arquivo)', 'ocorrencia'],
    ['ocorrencia.evidencia.link', 'Adicionar Link', 'Adicionar link como evidência', 'ocorrencia'],
    ['ocorrencia.evidencia.excluir', 'Excluir Evidência', 'Excluir evidência', 'ocorrencia'],
    
    // Anexos
    ['ocorrencia.anexo.criar', 'Anexar Arquivo', 'Anexar arquivos', 'ocorrencia'],
    ['ocorrencia.anexo.excluir', 'Excluir Anexo', 'Excluir qualquer anexo', 'ocorrencia'],
    ['ocorrencia.anexo.excluir_proprio', 'Excluir Próprio Anexo', 'Excluir apenas anexos criados', 'ocorrencia'],
    ['ocorrencia.link.criar', 'Adicionar Link', 'Adicionar link como anexo', 'ocorrencia'],
    ['ocorrencia.link.excluir', 'Excluir Link', 'Excluir link', 'ocorrencia'],
    
    // Transições de fase (015)
    ['ocorrencia.colocar_em_analise', 'Colocar em Análise', 'Mudar para fase análise', 'ocorrencia'],
    ['ocorrencia.solicitar_complemento', 'Solicitar Complemento', 'Mudar para fase complemento', 'ocorrencia'],
    ['ocorrencia.finalizar', 'Finalizar', 'Mudar para fase finalizada', 'ocorrencia'],
    ['ocorrencia.pronta', 'Marcar Pronta', 'Mudar para fase pronta', 'ocorrencia'],
    
    // =====================================================
    // PERMISSÕES DE NOTIFICAÇÕES
    // =====================================================
    ['notificacao.criar', 'Criar Notificação', 'Criar novas notificações', 'notificacao'],
    ['notificacao.editar', 'Editar Notificação', 'Editar qualquer notificação', 'notificacao'],
    ['notificacao.editar_propria', 'Editar Própria Notificação', 'Editar apenas notificações criadas', 'notificacao'],
    ['notificacao.excluir', 'Excluir Notificação', 'Excluir notificações', 'notificacao'],
    ['notificacao.listar', 'Listar Notificações', 'Listar notificações', 'notificacao'],
    ['notificacao.ver', 'Ver Notificação', 'Ver detalhes de notificação', 'notificacao'],
    ['notificacao.lavrar', 'Lavrar Notificação', 'Assinar/lavrar notificações', 'notificacao'],
    ['notificacao.revogar_assinatura', 'Revogar Assinatura', 'Revogar assinatura de notificação', 'notificacao'],
    ['notificacao.registrar_ciencia', 'Registrar Ciência', 'Registrar ciência do morador', 'notificacao'],
    ['notificacao.gerar_pdf', 'Gerar PDF', 'Gerar PDF da notificação', 'notificacao'],
    ['notificacao.marcar_enviada', 'Marcar Enviada', 'Marcar notificação como enviada', 'notificacao'],
    ['notificacao.encerrar', 'Encerrar Notificação', 'Encerrar notificação', 'notificacao'],
    
    // Ciclo de vida (015)
    ['notificacao.alterar_fase', 'Alterar Fase', 'Permissão geral para transições de fase da notificação', 'notificacao'],
    ['notificacao.registrar_recurso', 'Registrar Recurso', 'Registrar a interposição de recurso pelo morador', 'notificacao'],
    ['notificacao.julgar_recurso', 'Julgar Recurso', 'Deferir ou indeferir recursos de notificação', 'notificacao'],
    ['notificacao.marcar_cobranca', 'Marcar para Cobrança', 'Disponibilizar a notificação para lançamento em boleto', 'notificacao'],
    ['notificacao.reabrir', 'Reabrir Notificação', 'Reverter o status encerrada ou cobrança para a fase anterior', 'notificacao'],
    
    // Ações rápidas (185d745)
    ['notificacao.acao_rapida', 'Ações Rápidas', 'Usar ações rápidas (Enviar/Encerrar) na lista', 'notificacao'],
    ['notificacao.listar_lavradas', 'Listar Lavradas', 'Ver notificações no status lavrada', 'notificacao'],
    ['notificacao.listar_enviadas', 'Listar Enviadas', 'Ver notificações no status enviada', 'notificacao'],
    ['notificacao.listar_em_cobranca', 'Listar em Cobrança', 'Ver notificações em cobrança', 'notificacao'],
    ['notificacao.editar_campos', 'Editar Campos', 'Permite editar os campos da notificação', 'notificacao'],
    ['notificacao.editar_datas', 'Editar Datas', 'Permite editar datas de envio e ciência', 'notificacao'],
    ['notificacao.retornar_rascunho', 'Retornar ao Rascunho', 'Retornar notificação lavrada para rascunho', 'notificacao'],
    
    // Imagens
    ['notificacao.imagem.anexar', 'Anexar Imagem', 'Anexar imagem à notificação', 'notificacao'],
    ['notificacao.imagem.sincronizar', 'Sincronizar Imagens', 'Sincronizar imagens da ocorrência', 'notificacao'],
    ['notificacao.imagem.remover', 'Remover Imagem', 'Remover imagem da notificação', 'notificacao'],
    ['notificacao.imagem.ativar', 'Reativar Imagem', 'Reativar imagem removida', 'notificacao'],
    
    // Conteúdo
    ['notificacao.assunto.editar', 'Editar Assunto', 'Editar assunto da notificação', 'notificacao'],
    ['notificacao.tipo.editar', 'Editar Tipo', 'Editar tipo da notificação', 'notificacao'],
    ['notificacao.fato.adicionar', 'Adicionar Fato', 'Adicionar fato à notificação', 'notificacao'],
    ['notificacao.fato.editar', 'Editar Fato', 'Editar fato da notificação', 'notificacao'],
    ['notificacao.fato.remover', 'Remover Fato', 'Remover fato da notificação', 'notificacao'],
    ['notificacao.artigo.vincular', 'Vincular Artigo', 'Vincular artigo do regimento', 'notificacao'],
    ['notificacao.artigo.desvincular', 'Desvincular Artigo', 'Desvincular artigo', 'notificacao'],
    
    // =====================================================
    // PERMISSÕES DE CONFIGURAÇÕES
    // =====================================================
    ['configuracao.acessar', 'Acessar Configurações', 'Acessar página de configurações', 'configuracao'],
    ['configuracao.condominio.editar', 'Editar Condomínio', 'Editar dados do condomínio', 'configuracao'],
    ['configuracao.sindico.gerenciar', 'Gerenciar Síndicos', 'Gerenciar síndicos', 'configuracao'],
    ['configuracao.regimento.editar', 'Editar Regimento', 'Editar regimento interno', 'configuracao'],
    
    // =====================================================
    // PERMISSÕES DE USUÁRIOS
    // =====================================================
    ['usuario.listar', 'Listar Usuários', 'Listar usuários', 'usuario'],
    ['usuario.ver_detalhes', 'Ver Detalhes', 'Ver detalhes de usuário', 'usuario'],
    ['usuario.criar', 'Criar Usuário', 'Criar novo usuário', 'usuario'],
    ['usuario.editar', 'Editar Usuário', 'Editar usuário', 'usuario'],
    ['usuario.editar_papeis', 'Editar Papéis', 'Alterar papéis do usuário', 'usuario'],
    ['usuario.editar_grupo', 'Editar Grupo', 'Alterar grupo do usuário', 'usuario'],
    ['usuario.excluir', 'Excluir Usuário', 'Excluir usuário', 'usuario'],
    ['usuario.trocar_senha', 'Trocar Senha', 'Trocar senha de qualquer usuário', 'usuario'],
    
    // =====================================================
    // PERMISSÕES DE GRUPOS
    // =====================================================
    ['grupo.listar', 'Listar Grupos', 'Listar grupos', 'grupo'],
    ['grupo.criar', 'Criar Grupo', 'Criar novo grupo', 'grupo'],
    ['grupo.editar', 'Editar Grupo', 'Editar grupo', 'grupo'],
    ['grupo.excluir', 'Excluir Grupo', 'Excluir grupo', 'grupo'],
    ['grupo.gerenciar_permissoes', 'Gerenciar Permissões', 'Associar/desassociar permissões', 'grupo'],
];

function seedPermissoes($pdo, $permissoes, $isWeb = false) {
    $output = function($msg) use ($isWeb) {
        if ($isWeb) {
            echo htmlspecialchars($msg) . "<br>\n";
        } else {
            echo $msg . "\n";
        }
    };
    
    $criadas = 0;
    $existentes = 0;
    
    foreach ($permissoes as $p) {
        $slug = $p[0];
        $nome = $p[1];
        $descricao = $p[2];
        $modulo = $p[3];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO permissoes (slug, nome, descricao, modulo) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nome = VALUES(nome), descricao = VALUES(descricao)");
            $stmt->execute([$slug, $nome, $descricao, $modulo]);
            
            if ($stmt->rowCount() > 0) {
                $criadas++;
                $output("+ {$slug}");
            } else {
                $existentes++;
            }
        } catch (PDOException $e) {
            $output("ERRO {$slug}: " . $e->getMessage());
        }
    }
    
    return ['criadas' => $criadas, 'existentes' => $existentes];
}

$pdo = getDbConnection();
if (!$pdo) {
    $msg = "Erro ao conectar ao banco de dados.";
    if ($isWeb) {
        echo '<div class="error"><h3>❌ Erro</h3><p>' . $msg . '</p></div></div></body></html>';
    } else {
        echo "❌ " . $msg . "\n";
    }
    exit(1);
}

$resultado = seedPermissoes($pdo, $permissoes, $isWeb);

if ($isWeb) {
    echo '<div class="success">
        <h3>✅ Seed concluído!</h3>
        <p>Criadas: ' . $resultado['criadas'] . ' | Já existentes: ' . $resultado['existentes'] . '</p>
    </div>';
    echo '<a href="dashboard_content.php" style="color: #667eea;">← Voltar ao Dashboard</a>';
    echo '</div></body></html>';
} else {
    echo "\n✅ Seed concluído!\n";
    echo "Criadas: {$resultado['criadas']} | Já existentes: {$resultado['existentes']}\n";
}
