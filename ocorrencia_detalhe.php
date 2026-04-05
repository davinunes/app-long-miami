<?php
require_once 'auth.php';
requireAlgumaPermissao(['ocorrencia.ver_detalhes', 'ocorrencia.listar', 'ocorrencia.editar']);

$usuario = getUsuario();
$isAdmin = isAdmin();
$podeNotificar = isAdmin() || temPermissao('notificacao.criar');

// Permissões granulares para ações de fase
$podeColocarEmAnalise = isAdmin() || temPermissao('ocorrencia.colocar_em_analise');
$podeMarcarPronta = isAdmin() || temPermissao('ocorrencia.marcar_pronta');
$podeHomologar = isAdmin() || temPermissao('ocorrencia.homologar');
$podeRecusar = isAdmin() || temPermissao('ocorrencia.recusar');
$podeVoltarAnalise = isAdmin() || temPermissao('ocorrencia.retornar_analise');
$podeVincularUnidade = isAdmin() || temPermissao('ocorrencia.unidade.vincular');
$podeCriarMensagem = isAdmin() || temPermissao('ocorrencia.mensagem.criar');
$podeCriarAnexo = isAdmin() || temPermissao('ocorrencia.anexo.criar');
$podeCriarEvidencia = isAdmin() || temPermissao('ocorrencia.evidencia.anexar');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Ocorrência - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { padding-top: 0; }
        .simple-header {
            background: #2d3748;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .simple-header a { color: white; text-decoration: none; }
        .simple-nav a { color: white; margin-left: 20px; text-decoration: none; font-size: 14px; }
        .simple-nav a:hover { text-decoration: underline; }
        
        .fase-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .fase-nova { background: #2196F3; color: white; }
        .fase-em_analise { background: #FF9800; color: white; }
        .fase-pronta { background: #9C27B0; color: white; }
        .fase-recusada { background: #F44336; color: white; }
        .fase-homologada { background: #4CAF50; color: white; }

        .section-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .unidade-tag {
            display: inline-block;
            background: #e0e0e0;
            padding: 4px 12px;
            border-radius: 12px;
            margin: 2px;
            font-size: 13px;
        }

        .mensagem-chat {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f5f5f5;
        }
        .mensagem-chat.evidencia {
            background: #fff3e0;
            border-left: 4px solid #FF9800;
        }
        .mensagem-chat .msg-autor {
            font-weight: bold;
            font-size: 12px;
            color: #1976D2;
        }
        .mensagem-chat .msg-hora {
            font-size: 11px;
            color: #999;
            float: right;
        }
        .mensagem-chat.evidencia .msg-autor::before {
            content: "EVIDÊNCIA ";
            font-size: 10px;
            background: #FF9800;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            margin-right: 5px;
        }
        .mensagem-chat p { margin: 8px 0 0 0; }

        .anexo-lista { display: flex; flex-wrap: wrap; gap: 10px; }
        .anexo-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: #f9f9f9;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        .anexo-item i { margin-right: 8px; color: #666; }
        .anexo-item a { color: #1976D2; }

        .historico-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .historico-item:last-child { border-bottom: none; }
        .historico-fase { font-weight: bold; color: #333; }
        .historico-obs { color: #666; font-size: 13px; margin: 5px 0; }
        .historico-data { font-size: 11px; color: #999; }
    </style>
</head>
<body>
    <header>
        <?php include '_partials/menu.php'; ?>
    </header>

    <a href="#" class="custom-sidenav-toggle mobile-menu-btn">
        <i class="material-icons">menu</i>
    </a>

    <main class="main-content">
        <div class="container">
            <div class="header">
                <a href="ocorrencias.php" class="back-link"><i class="material-icons">arrow_back</i> Voltar para Ocorrências</a>
                <h1 id="page-title">Ocorrência</h1>
            </div>
            
            <div id="fase-container" style="margin-bottom: 15px;"></div>
            
            <div id="ocorrencia-content">
                <div style="text-align: center; padding: 50px;">
                    <div class="preloader-wrapper small active">
                        <div class="spinner-layer spinner-blue">
                            <div class="circle-clipper left">
                                <div class="circle"></div>
                            </div>
                        </div>
                    </div>
                    <p style="margin-top: 15px; color: #666;">Carregando...</p>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
const urlParams = new URLSearchParams(window.location.search);
const ocorrenciaId = urlParams.get('id');
const podeNotificar = <?php echo $podeNotificar ? 'true' : 'false'; ?>;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const usuarioId = <?php echo $usuario['id']; ?>;

// Permissões granulares injetadas pelo PHP
const PODE_VINCULAR_UNIDADE = <?php echo $podeVincularUnidade ? 'true' : 'false'; ?>;
const PODE_CRIAR_MENSAGEM = <?php echo $podeCriarMensagem ? 'true' : 'false'; ?>;
const PODE_CRIAR_ANEXO = <?php echo $podeCriarAnexo ? 'true' : 'false'; ?>;
const PODE_CRIAR_EVIDENCIA = <?php echo $podeCriarEvidencia ? 'true' : 'false'; ?>;

// Permissões de fase
const PODE_COLOCAR_EM_ANALISE = <?php echo $podeColocarEmAnalise ? 'true' : 'false'; ?>;
const PODE_MARCAR_PRONTA = <?php echo $podeMarcarPronta ? 'true' : 'false'; ?>;
const PODE_HOMOLOGAR = <?php echo $podeHomologar ? 'true' : 'false'; ?>;
const PODE_RECUSAR = <?php echo $podeRecusar ? 'true' : 'false'; ?>;
const PODE_VOLTAR_ANALISE = <?php echo $podeVoltarAnalise ? 'true' : 'false'; ?>;

console.log('[Permissões] Fase: COLOCAR_EM_ANALISE:', PODE_COLOCAR_EM_ANALISE);
console.log('[Permissões] Fase: MARCAR_PRONTA:', PODE_MARCAR_PRONTA);
console.log('[Permissões] Fase: HOMOLOGAR:', PODE_HOMOLOGAR);
console.log('[Permissões] Fase: RECUSAR:', PODE_RECUSAR);
console.log('[Permissões] Fase: VOLTAR_ANALISE:', PODE_VOLTAR_ANALISE);

let ocorrenciaData = null;

$(document).ready(async function() {
    $('.sidenav').sidenav({edge: 'left'});
    
    $('#user-name').text('<?php echo htmlspecialchars($usuario['nome']); ?>');
    $('#user-email').text('<?php echo htmlspecialchars($usuario['email']); ?>');
    
    if (!ocorrenciaId) {
        $('#ocorrencia-content').html('<p style="color: red;">ID da ocorrência não fornecido.</p>');
        return;
    }
    await carregarOcorrencia();
});

function fazerLogout() {
    window.location.href = 'logout.php';
}

async function carregarOcorrencia() {
    try {
        const response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php?id=' + ocorrenciaId);
        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.message || 'Erro ao carregar.');
        }
        ocorrenciaData = await response.json();
        renderOcorrencia(ocorrenciaData);
    } catch (error) {
        $('#ocorrencia-content').html('<p style="color: red;">Erro: ' + error.message + '</p>');
    }
}

function renderOcorrencia(occ) {
    var faseLabel = occ.fase.replace('_', ' ');
    var isProprio = occ.created_by === usuarioId;
    var podeEditarProprio = isProprio && !isAdmin;
    
    $('#page-title').text('Ocorrência #' + occ.id);
    $('#fase-container').html('<span class="fase-badge fase-' + occ.fase + '">' + faseLabel + '</span>');
    
    var unidadesHtml = '';
    if (occ.unidades && occ.unidades.length > 0) {
        unidadesHtml = occ.unidades.map(function(u) {
            return '<span class="unidade-tag">' + (u.unidade_bloco || '') + u.unidade_numero + 
                (PODE_VINCULAR_UNIDADE || isProprio ? ' <button onclick="removerUnidade(' + u.id + ')" style="background:none;border:none;cursor:pointer;color:#999;padding:0;">×</button>' : '') + 
                '</span>';
        }).join('');
    } else {
        unidadesHtml = '<span style="color: #999;">Nenhuma</span>';
    }
    
    var unidadesForm = '';
    if (PODE_VINCULAR_UNIDADE) {
        unidadesForm = '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ddd;">' +
            '<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">' +
            '<select id="unidade-bloco" class="browser-default" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-width: 80px;">' +
            '<option value="">Bloco</option>' +
            '<option value="A">A</option><option value="B">B</option><option value="C">C</option>' +
            '<option value="D">D</option><option value="E">E</option><option value="F">F</option>' +
            '</select>' +
            '<input type="text" id="unidade-numero" placeholder="Número" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100px;">' +
            '<button class="btn-small blue" onclick="adicionarUnidadeFromForm()">+ Vincular</button>' +
            '</div></div>';
    }
    
    var faseControlsHtml = '';
    
    // Construir botões de ação baseados na fase atual e permissões
    var acoesFase = [];
    
    if (occ.fase === 'nova' && PODE_COLOCAR_EM_ANALISE) {
        acoesFase.push({
            classe: 'blue',
            icone: 'search',
            texto: 'Colocar em Análise',
            onclick: 'colocarEmAnalise()'
        });
    }
    
    if (occ.fase === 'em_analise' && PODE_MARCAR_PRONTA) {
        acoesFase.push({
            classe: 'orange',
            icone: 'check_circle',
            texto: 'Marcar como Pronta',
            onclick: 'marcarPronta()'
        });
    }
    
    if (occ.fase === 'pronta' && PODE_HOMOLOGAR) {
        acoesFase.push({
            classe: 'green',
            icone: 'verified',
            texto: 'Homologar',
            onclick: 'homologarOcorrencia()'
        });
    }
    
    if ((occ.fase === 'em_analise' || occ.fase === 'pronta') && PODE_RECUSAR) {
        acoesFase.push({
            classe: 'red',
            icone: 'cancel',
            texto: 'Recusar',
            onclick: 'recusarOcorrencia()'
        });
    }
    
    if (occ.fase === 'recusada' && PODE_VOLTAR_ANALISE) {
        acoesFase.push({
            classe: 'grey',
            icone: 'replay',
            texto: 'Voltar para Análise',
            onclick: 'voltarParaAnalise()'
        });
    }
    
    if (acoesFase.length > 0) {
        faseControlsHtml = '<div class="section-card">' +
            '<div class="section-title">Ações de Fase</div>' +
            '<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">';
        
        acoesFase.forEach(function(acao) {
            faseControlsHtml += '<button class="btn ' + acao.classe + '" onclick="' + acao.onclick + '">' +
                '<i class="material-icons left">' + acao.icone + '</i>' + acao.texto +
                '</button>';
        });
        
        faseControlsHtml += '</div>' +
            '<div style="display: flex; gap: 10px; align-items: center;">' +
            '<input type="text" id="fase-obs" placeholder="Observação (opcional)" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
            '</div></div>';
    }
    
    var notificacaoHtml = '';
    if (occ.notificacao) {
        notificacaoHtml = '<div class="section-card">' +
            '<div class="section-title">Notificação Vinculada</div>' +
            '<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">' +
            '<span class="fase-badge fase-' + (occ.notificacao.status === 'Deferido' ? 'homologada' : 'em_analise') + '">' + occ.notificacao.status + '</span>' +
            '<span><strong>Notificação #' + occ.notificacao.numero + '/' + occ.notificacao.ano + '</strong></span>' +
            '<a href="editar.php?id=' + occ.notificacao.id + '" class="btn-small blue">Ver Notificação</a>' +
            '</div></div>';
    } else if (occ.fase === 'homologada' && podeNotificar) {
        notificacaoHtml = '<div class="section-card">' +
            '<div class="section-title">Notificação</div>' +
            '<p style="color: #666; margin-bottom: 15px;">Esta ocorrência homologada ainda não possui uma notificação vinculada.</p>' +
            '<button class="btn green" onclick="criarNotificacao()"><i class="material-icons">add</i> Criar Notificação</button>' +
            '</div>';
    } else if (occ.fase === 'homologada') {
        notificacaoHtml = '<div class="section-card">' +
            '<div class="section-title">Notificação</div>' +
            '<p style="color: #666;"><em>Esta ocorrência homologada ainda não possui uma notificação vinculada.</em></p>' +
            '</div>';
    }
    
    var html = faseControlsHtml + notificacaoHtml + 
        '<div class="section-card">' +
        '<div class="section-title">Dados da Ocorrência</div>' +
        '<h4 style="margin: 0 0 15px 0;">' + occ.titulo + '</h4>' +
        '<p style="color: #666; line-height: 1.6;">' + occ.descricao_fato.replace(/\n/g, '<br>') + '</p>' +
        '<hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">' +
        '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">' +
        '<div><strong style="color: #666; font-size: 12px;">DATA DO FATO</strong><p style="margin: 5px 0;">' + formatDate(occ.data_fato) + '</p></div>' +
        '<div><strong style="color: #666; font-size: 12px;">UNIDADES</strong><p style="margin: 5px 0;">' + unidadesHtml + '</p>' + unidadesForm + '</div>' +
        '<div><strong style="color: #666; font-size: 12px;">CRIADO POR</strong><p style="margin: 5px 0;">' + (occ.autor_nome || '-') + '</p></div>' +
        '<div><strong style="color: #666; font-size: 12px;">CRIADO EM</strong><p style="margin: 5px 0;">' + formatDateTime(occ.data_criacao) + '</p></div>' +
        '</div></div>' +
        
        '<div class="section-card">' +
        '<div class="section-title">Mensagens e Evidências</div>' +
        '<div id="mensagens-container" style="max-height: 400px; overflow-y: auto;">' + renderMensagens(occ.mensagens || [], podeEditarProprio) + '</div>';
    
    if (PODE_CRIAR_MENSAGEM) {
        html += '<hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">' +
        '<div style="display: flex; gap: 10px; align-items: center;">' +
        '<input type="text" id="nova-mensagem" placeholder="Digite uma mensagem ou cole (Ctrl+V)..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
        '<label style="display: flex; align-items: center; gap: 5px;"><input type="checkbox" id="msg-evidencia"><span>Evidência</span></label>' +
        '<button class="btn blue" onclick="enviarMensagem()">Enviar</button>' +
        '</div>' +
        '<small style="color: #999; font-size: 11px;">💡 Dica: Cole (Ctrl+V) texto ou imagem diretamente aqui</small>';
    }
    
    html += '</div>' +
        
        '<div class="section-card">' +
        '<div class="section-title">Anexos</div>' +
        '<div class="anexo-lista" id="anexos-container">' + renderAnexos(occ.anexos || [], podeEditarProprio) + '</div>';
    
    if (PODE_CRIAR_ANEXO || PODE_CRIAR_EVIDENCIA) {
        html += '<hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">' +
        '<div style="margin-bottom: 10px;">' +
        '<label style="color: #666; font-size: 12px; display: block; margin-bottom: 5px;">Adicionar Link (Google Drive, OneDrive, etc.)</label>' +
        '<div style="display: flex; gap: 10px; align-items: center;">' +
        '<input type="url" id="link-url" placeholder="https://drive.google.com/..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
        '<input type="text" id="link-nome" placeholder="Nome do link" style="width: 150px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
        '<button class="btn blue" onclick="adicionarLink()">Adicionar</button>' +
        '</div>' +
        '</div>' +
        '<div style="border-top: 1px dashed #ddd; padding-top: 15px;">' +
        '<label style="color: #666; font-size: 12px; display: block; margin-bottom: 5px;">Anexar Arquivo</label>' +
        '<div style="display: flex; gap: 10px; align-items: center;">' +
        '<input type="file" id="anexo-file" accept="image/*,.pdf,.doc,.docx" style="flex: 1;">' +
        '<button class="btn blue" onclick="uploadAnexo()">Anexar</button>' +
        '</div>' +
        '</div>' +
        '<small style="color: #999; font-size: 11px;">💡 Dica: Cole (Ctrl+V) uma imagem para anexá-la diretamente</small>';
    }
    
    html += '</div>' +
        
        '<div class="section-card">' +
        '<div class="section-title">Histórico de Alterações</div>' +
        '<div id="historico-container">' + renderHistorico(occ.fase_log || []) + '</div>' +
        '</div>';
    
    $('#ocorrencia-content').html(html);
}

function podeExcluirMsg(usuarioIdMsg) {
    return podeEditar && (isAdmin || usuarioIdMsg === usuarioId);
}

function podeExcluirAnexo(usuarioIdAnexo) {
    return podeEditar && (isAdmin || usuarioIdAnexo === usuarioId);
}

function renderMensagens(mensagens, podeEditarLocal) {
    if (!mensagens || mensagens.length === 0) {
        return '<p style="color: #999; text-align: center; padding: 20px;">Nenhuma mensagem.</p>';
    }
    return mensagens.map(function(m) {
        var podeExcluir = podeEditarLocal && (isAdmin || m.usuario_id === usuarioId);
        var btnExcluir = podeExcluir ? '<button class="btn-floating btn-small red" onclick="excluirMensagem(' + m.id + ')" title="Excluir"><i class="material-icons" style="font-size: 18px;">delete</i></button>' : '';
        var msgComLinks = converterLinks(m.mensagem);
        return '<div class="mensagem-chat ' + (m.eh_evidencia ? 'evidencia' : '') + '">' +
            '<span class="msg-autor">' + (m.autor_nome || 'Sistema') + '</span>' +
            '<span class="msg-hora">' + formatDateTime(m.created_at) + '</span>' +
            '<div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">' +
            '<p style="flex: 1; margin: 0; word-break: break-word;">' + msgComLinks + '</p>' +
            btnExcluir +
            '</div></div>';
    }).join('');
}

function converterLinks(texto) {
    var urlRegex = /(https?:\/\/[^\s<]+)/g;
    return texto.replace(urlRegex, function(url) {
        var nomeExibido = url.length > 50 ? url.substring(0, 50) + '...' : url;
        return '<a href="' + url + '" target="_blank" style="color: #2196F3; text-decoration: underline;">' + nomeExibido + '</a>';
    });
}

function renderAnexos(anexos, podeEditarLocal) {
    if (!anexos || anexos.length === 0) {
        return '<p style="color: #999;">Nenhum anexo.</p>';
    }
    return anexos.map(function(a) {
        var podeExcluir = podeEditarLocal && (isAdmin || a.usuario_id === usuarioId);
        var conteudo = '';
        if (a.tipo === 'link') {
            var iconeLink = getIconeLink(a.url);
            conteudo = '<div class="link-anexo"><a href="' + a.url + '" target="_blank" class="link-externo"><i class="material-icons">' + iconeLink + '</i><span>' + a.nome_original + '</span><i class="material-icons open-icon">open_in_new</i></a></div>';
        } else if (a.tipo === 'imagem') {
            conteudo = '<div style="margin-top: 10px;"><img src="' + a.url + '" alt="' + a.nome_original + '" style="max-width: 100%; max-height: 300px; border-radius: 6px; cursor: pointer;" onclick="window.open(\'' + a.url + '\', \'_blank\')"></div>';
        } else if (a.tipo === 'audio') {
            conteudo = '<div style="margin-top: 10px;"><audio controls style="width: 100%;"><source src="' + a.url + '" type="' + (a.mime_type || 'audio/mpeg') + '"></audio></div>';
        } else if (a.tipo === 'video') {
            conteudo = '<div style="margin-top: 10px;"><video controls style="max-width: 100%; max-height: 300px; border-radius: 6px;"><source src="' + a.url + '" type="' + (a.mime_type || 'video/mp4') + '"></video></div>';
        } else {
            conteudo = '<a href="' + a.url + '" target="_blank" download="' + a.nome_original + '"><i class="material-icons">' + getIconeTipo(a.tipo) + '</i> ' + a.nome_original + '</a>';
        }
        var btnExcluir = podeExcluir ? '<button class="btn-floating btn-small red" onclick="excluirAnexo(' + a.id + ')" title="Excluir" style="margin-left: 10px;"><i class="material-icons" style="font-size: 18px;">delete</i></button>' : '';
        return '<div class="anexo-item" style="flex: 1; justify-content: space-between;">' + conteudo + btnExcluir + '</div>';
    }).join('');
}

function getIconeLink(url) {
    if (url.includes('drive.google.com') || url.includes('docs.google.com')) {
        return 'description';
    } else if (url.includes('dropbox.com')) {
        return 'cloud';
    } else if (url.includes('sharepoint.com') || url.includes('onedrive.live.com') || url.includes('1drv.ms')) {
        return 'cloud_circle';
    } else if (url.includes('youtube.com') || url.includes('youtu.be')) {
        return 'play_circle_filled';
    } else if (url.includes('github.com')) {
        return 'code';
    } else {
        return 'link';
    }
}

function renderHistorico(logs) {
    if (!logs || logs.length === 0) {
        return '<p style="color: #999;">Nenhum histórico.</p>';
    }
    
    var labelsFase = {
        'nova': 'Nova',
        'em_analise': 'Em Análise',
        'pronta': 'Pronta',
        'recusada': 'Recusada',
        'homologada': 'Homologada'
    };
    
    var labelsAcao = {
        'mensagem_adicionada': '💬 Mensagem adicionada',
        'evidencia_adicionada': '📎 Evidência adicionada',
        'anexo_adicionado': '📎 Anexo adicionado',
        'mensagem_removida': '🗑️ Mensagem removida',
        'evidencia_removida': '🗑️ Evidência removida',
        'anexo_removido': '🗑️ Anexo removido'
    };
    
    return logs.map(function(l) {
        var titulo = '';
        if (labelsFase[l.fase_nova]) {
            titulo = (l.fase_anterior ? labelsFase[l.fase_anterior] + ' → ' : '') + labelsFase[l.fase_nova];
        } else if (labelsAcao[l.fase_nova]) {
            titulo = labelsAcao[l.fase_nova];
        } else {
            titulo = l.fase_nova;
        }
        
        return '<div class="historico-item">' +
            '<div class="historico-fase">' + titulo + (l.usuario_nome ? ' <span style="color:#666;font-weight:normal;">por ' + l.usuario_nome + '</span>' : '') + '</div>' +
            (l.observacao ? '<div class="historico-obs">' + l.observacao + '</div>' : '') +
            '<div class="historico-data">' + formatDateTime(l.created_at) + '</div>' +
            '</div>';
    }).join('');
}

async function enviarMensagem() {
    var mensagem = $('#nova-mensagem').val().trim();
    if (!mensagem) return;
    var ehEvidencia = $('#msg-evidencia').is(':checked');
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ocorrencia_id: ocorrenciaId, mensagem: mensagem, eh_evidencia: ehEvidencia })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        $('#nova-mensagem').val('');
        $('#msg-evidencia').prop('checked', false);
        carregarOcorrencia();
    } catch (error) {
        alert(error.message);
    }
}

async function uploadAnexo() {
    var input = document.getElementById('anexo-file');
    if (!input.files[0]) {
        alert('Selecione um arquivo.');
        return;
    }
    var file = input.files[0];
    var reader = new FileReader();
    
    reader.onload = async function(e) {
        var dados = {
            ocorrencia_id: ocorrenciaId,
            tipo: file.type.startsWith('image/') ? 'imagem' : 'documento',
            nome_original: file.name,
            dados: e.target.result.split(',')[1],
            mime_type: file.type,
            tamanho_bytes: file.size
        };
        
        try {
            var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php?upload=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            var result = await response.json();
            if (!response.ok) throw new Error(result.message);
            input.value = '';
            carregarOcorrencia();
        } catch (error) {
            alert(error.message);
        }
    };
    reader.readAsDataURL(file);
}

async function adicionarLink() {
    var url = document.getElementById('link-url').value.trim();
    var nome = document.getElementById('link-nome').value.trim() || 'Link';
    
    if (!url) {
        M.toast({html: 'Informe a URL do link.', classes: 'orange'});
        return;
    }
    
    if (!/^https?:\/\//i.test(url)) {
        url = 'https://' + url;
    }
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php?upload=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ocorrencia_id: ocorrenciaId,
                tipo: 'link',
                url: url,
                nome_original: nome
            })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        document.getElementById('link-url').value = '';
        document.getElementById('link-nome').value = '';
        M.toast({html: 'Link adicionado!', classes: 'green'});
        carregarOcorrencia();
    } catch (error) {
        M.toast({html: 'Erro: ' + error.message, classes: 'red'});
    }
}

async function excluirMensagem(id) {
    if (!confirm('Tem certeza que deseja excluir esta mensagem?')) return;
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ deletar_mensagem: true, id: id })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        M.toast({html: 'Mensagem excluída.', classes: 'green'});
        carregarOcorrencia();
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function excluirAnexo(id) {
    if (!confirm('Tem certeza que deseja excluir este anexo? O arquivo será removido do disco.')) return;
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ deletar_anexo: true, id: id })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        M.toast({html: 'Anexo excluído.', classes: 'green'});
        carregarOcorrencia();
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function mudarFase(novaFase) {
    var observacao = $('#fase-obs').val().trim();
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mudar_fase: true, id: ocorrenciaId, nova_fase: novaFase, observacao: observacao })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        M.toast({html: 'Fase alterada com sucesso!', classes: 'green'});
        carregarOcorrencia();
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function colocarEmAnalise() {
    if (!confirm('Colocar esta ocorrência em análise?')) return;
    await mudarFase('em_analise');
}

async function marcarPronta() {
    if (!confirm('Marcar esta ocorrência como pronta para homologação?')) return;
    await mudarFase('pronta');
}

async function homologarOcorrencia() {
    if (!confirm('Homologar esta ocorrência? Após isso, será possível criar uma notificação.')) return;
    await mudarFase('homologada');
}

async function recusarOcorrencia() {
    var obs = prompt('Informe o motivo da recusa:');
    if (obs === null) return;
    $('#fase-obs').val(obs);
    await mudarFase('recusada');
}

async function voltarParaAnalise() {
    if (!confirm('Voltar esta ocorrência para análise?')) return;
    await mudarFase('em_analise');
}

function criarNotificacao() {
    window.location.href = 'nova_not.php?ocorrencia_id=' + ocorrenciaId;
}

function formatDate(data) {
    if (!data) return '-';
    var d = new Date(data + 'T00:00:00');
    return d.toLocaleDateString('pt-BR');
}

function formatDateTime(data) {
    if (!data) return '-';
    var d = new Date(data);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

function getIconeTipo(tipo) {
    var icones = { imagem: 'image', video: 'movie', audio: 'audiotrack', documento: 'description', link: 'link' };
    return icones[tipo] || 'attach_file';
}

document.addEventListener('paste', async function(e) {
    if (!PODE_CRIAR_EVIDENCIA) return;
    
    const items = e.clipboardData.items;
    for (let item of items) {
        if (item.type.startsWith('image/')) {
            e.preventDefault();
            const file = item.getAsFile();
            if (file) {
                await processarColagemImagem(file);
            }
            break;
        }
    }
});

async function processarColagemImagem(file) {
    M.toast({html: 'Processando imagem...', classes: 'blue'});
    
    var reader = new FileReader();
    reader.onload = async function(e) {
        var dados = {
            ocorrencia_id: ocorrenciaId,
            tipo: 'imagem',
            nome_original: 'paste_' + Date.now() + '.png',
            dados: e.target.result.split(',')[1],
            mime_type: file.type || 'image/png',
            tamanho_bytes: file.size
        };
        
        try {
            var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php?upload=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            var result = await response.json();
            if (!response.ok) throw new Error(result.message);
            M.toast({html: 'Imagem colada como evidência!', classes: 'green'});
            carregarOcorrencia();
        } catch (error) {
            M.toast({html: 'Erro ao colar imagem: ' + error.message, classes: 'red'});
        }
    };
    reader.readAsDataURL(file);
}

async function adicionarUnidade(bloco, numero) {
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                adicionar_unidade: true,
                ocorrencia_id: ocorrenciaId,
                bloco: bloco,
                numero: numero
            })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        M.toast({html: 'Unidade vinculada!', classes: 'green'});
        carregarOcorrencia();
    } catch (error) {
        M.toast({html: 'Erro ao vincular unidade: ' + error.message, classes: 'red'});
    }
}

async function removerUnidade(id) {
    if (!confirm('Remover esta unidade?')) return;
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                remover_unidade: true,
                unidade_id: id
            })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        M.toast({html: 'Unidade removida!', classes: 'green'});
        carregarOcorrencia();
    } catch (error) {
        M.toast({html: 'Erro ao remover unidade: ' + error.message, classes: 'red'});
    }
}
    </script>
</body>
</html>
