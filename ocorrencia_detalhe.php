<?php
require_once 'auth.php';
requirePapel(['protocolar', 'diligente', 'promotor', 'admin', 'dev']);

$usuario = getUsuario();
$podeMudarFase = temAlgumPapel(['promotor', 'admin', 'dev']);
$isAdmin = temAlgumPapel(['admin', 'dev']);
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

    <a href="#" data-target="slide-out" class="sidenav-trigger mobile-menu-btn">
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
const API_BASE_URL_PHP = window.location.origin + '/api';
const urlParams = new URLSearchParams(window.location.search);
const ocorrenciaId = urlParams.get('id');
const podeMudarFase = <?php echo $podeMudarFase ? 'true' : 'false'; ?>;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const usuarioId = <?php echo $usuario['id']; ?>;
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
    var podeEditar = (occ.fase !== 'homologada') && (isAdmin || occ.created_by === usuarioId);
    
    $('#page-title').text('Ocorrência #' + occ.id);
    $('#fase-container').html('<span class="fase-badge fase-' + occ.fase + '">' + faseLabel + '</span>');
    
    var unidadesHtml = '';
    if (occ.unidades && occ.unidades.length > 0) {
        unidadesHtml = occ.unidades.map(function(u) {
            return '<span class="unidade-tag">' + (u.unidade_bloco || '') + u.unidade_numero + '</span>';
        }).join('');
    } else {
        unidadesHtml = '<span style="color: #999;">Nenhuma</span>';
    }
    
    var faseControlsHtml = '';
    if (podeMudarFase) {
        faseControlsHtml = '<div class="section-card">' +
            '<div class="section-title">Alterar Fase</div>' +
            '<div style="display: flex; gap: 10px; align-items: center;">' +
            '<select id="nova-fase" class="browser-default" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
            '<option value="nova"' + (occ.fase === 'nova' ? ' selected' : '') + '>Nova</option>' +
            '<option value="em_analise"' + (occ.fase === 'em_analise' ? ' selected' : '') + '>Em Análise</option>' +
            '<option value="recusada"' + (occ.fase === 'recusada' ? ' selected' : '') + '>Recusada</option>' +
            '<option value="homologada"' + (occ.fase === 'homologada' ? ' selected' : '') + '>Homologada</option>' +
            '</select>' +
            '<input type="text" id="fase-obs" placeholder="Observação" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">' +
            '<button class="btn blue" onclick="mudarFase()">Alterar</button>' +
            '</div></div>';
    }
    
    var notificacaoHtml = '';
    if (occ.notificacao) {
        notificacaoHtml = '<div class="section-card">' +
            '<div class="section-title">Notificação Vinculada</div>' +
            '<div style="display: flex; align-items: center; gap: 15px;">' +
            '<span class="fase-badge fase-' + (occ.notificacao.status === 'Deferido' ? 'homologada' : 'em_analise') + '">' + occ.notificacao.status + '</span>' +
            '<span><strong>Notificação #' + occ.notificacao.numero + '/' + occ.notificacao.ano + '</strong></span>' +
            '<a href="notificacao_detalhe.php?id=' + occ.notificacao.id + '" class="btn-small blue">Ver Notificação</a>' +
            '</div></div>';
    } else if (occ.fase === 'homologada') {
        notificacaoHtml = '<div class="section-card">' +
            '<div class="section-title">Notificação</div>' +
            '<p style="color: #666; margin-bottom: 15px;">Esta ocorrência homologada ainda não possui uma notificação vinculada.</p>' +
            '<button class="btn green" onclick="gerarNotificacao()"><i class="material-icons">add</i> Gerar Notificação</button>' +
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
        '<div><strong style="color: #666; font-size: 12px;">UNIDADES</strong><p style="margin: 5px 0;">' + unidadesHtml + '</p></div>' +
        '<div><strong style="color: #666; font-size: 12px;">CRIADO POR</strong><p style="margin: 5px 0;">' + (occ.autor_nome || '-') + '</p></div>' +
        '<div><strong style="color: #666; font-size: 12px;">CRIADO EM</strong><p style="margin: 5px 0;">' + formatDateTime(occ.data_criacao) + '</p></div>' +
        '</div></div>' +
        
        '<div class="section-card">' +
        '<div class="section-title">Mensagens e Evidências</div>' +
        '<div id="mensagens-container" style="max-height: 400px; overflow-y: auto;">' + renderMensagens(occ.mensagens || [], podeEditar) + '</div>';
    
    if (podeEditar) {
        html += '<hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">' +
        '<div style="display: flex; gap: 10px; align-items: center;">' +
        '<input type="text" id="nova-mensagem" placeholder="Digite uma mensagem..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
        '<label style="display: flex; align-items: center; gap: 5px;"><input type="checkbox" id="msg-evidencia"><span>Evidência</span></label>' +
        '<button class="btn blue" onclick="enviarMensagem()">Enviar</button>' +
        '</div>';
    }
    
    html += '</div>' +
        
        '<div class="section-card">' +
        '<div class="section-title">Anexos</div>' +
        '<div class="anexo-lista" id="anexos-container">' + renderAnexos(occ.anexos || [], podeEditar) + '</div>';
    
    if (podeEditar) {
        html += '<hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">' +
        '<div style="display: flex; gap: 10px; align-items: center;">' +
        '<input type="file" id="anexo-file" accept="image/*,.pdf,.doc,.docx" style="flex: 1;">' +
        '<button class="btn blue" onclick="uploadAnexo()">Anexar</button>' +
        '</div>';
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
        return '<div class="mensagem-chat ' + (m.eh_evidencia ? 'evidencia' : '') + '">' +
            '<span class="msg-autor">' + (m.autor_nome || 'Sistema') + '</span>' +
            '<span class="msg-hora">' + formatDateTime(m.created_at) + '</span>' +
            '<div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">' +
            '<p style="flex: 1; margin: 0;">' + m.mensagem + '</p>' +
            btnExcluir +
            '</div></div>';
    }).join('');
}

function renderAnexos(anexos, podeEditarLocal) {
    if (!anexos || anexos.length === 0) {
        return '<p style="color: #999;">Nenhum anexo.</p>';
    }
    return anexos.map(function(a) {
        var podeExcluir = podeEditarLocal && (isAdmin || a.usuario_id === usuarioId);
        var conteudo = '';
        if (a.tipo === 'imagem') {
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

function renderHistorico(logs) {
    if (!logs || logs.length === 0) {
        return '<p style="color: #999;">Nenhum histórico.</p>';
    }
    return logs.map(function(l) {
        return '<div class="historico-item">' +
            '<div class="historico-fase">' + (l.fase_anterior ? l.fase_anterior + ' → ' : '') + l.fase_nova + '</div>' +
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

async function mudarFase() {
    var novaFase = $('#nova-fase').val();
    var observacao = $('#fase-obs').val().trim();
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mudar_fase: true, id: ocorrenciaId, nova_fase: novaFase, observacao: observacao })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        carregarOcorrencia();
    } catch (error) {
        alert(error.message);
    }
}

async function gerarNotificacao() {
    if (!confirm('Deseja gerar uma notificação para esta ocorrência?')) return;
    
    try {
        var response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ gerar_notificacao: true, ocorrencia_id: ocorrenciaId })
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.message);
        alert('Notificação #' + result.numero + ' gerada com sucesso!');
        carregarOcorrencia();
    } catch (error) {
        alert(error.message);
    }
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
    </script>
</body>
</html>
