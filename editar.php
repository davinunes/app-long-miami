<?php
require_once 'auth.php';
requireLogin();

$usuarioLogado = getUsuario();
$permissoes = getPermissoesUsuario();

$podeVer = isAdmin() || temPermissao('notificacao.ver');
if (!$podeVer) {
    header('Location: lista.php');
    exit;
}

// Permissões de edição de campos
$podeEditarCampos = isAdmin() || temPermissao('notificacao.editar') || temPermissao('notificacao.editar_campos');

// Permissões de imagem
$podeRemoverImagem = isAdmin() || temPermissao('notificacao.imagem.remover');
$podeAnexarImagem = isAdmin() || temPermissao('notificacao.imagem.anexar');

// Permissões de fase
$podeLavrar = isAdmin() || temPermissao('notificacao.lavrar');
$podeRevogarAssinatura = isAdmin() || temPermissao('notificacao.retornar_rascunho');
$podeEnviar = isAdmin() || temPermissao('notificacao.marcar_enviada');
$podeRegistrarCiencia = isAdmin() || temPermissao('notificacao.registrar_ciencia');
$podeRegistrarRecurso = isAdmin() || temPermissao('notificacao.registrar_recurso');
$podeJulgarRecurso = isAdmin() || temPermissao('notificacao.julgar_recurso');
$podeEncerrar = isAdmin() || temPermissao('notificacao.encerrar');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificação - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .lock-info { background: #fff3e0; padding: 10px; border-radius: 4px; margin-bottom: 20px; border-left: 5px solid #ff9800; display: flex; align-items: center; gap: 10px; }
        .form-locked input, .form-locked textarea, .form-locked select { pointer-events: none; opacity: 0.7; }
        .timeline-item { border-left: 2px solid #2196f3; padding-left: 15px; margin-bottom: 15px; position: relative; }
        .timeline-marker { width: 10px; height: 10px; background: #2196f3; border-radius: 50%; position: absolute; left: -6px; top: 5px; }
        .timeline-header { display: flex; justify-content: space-between; font-size: 13px; }
        .timeline-user { font-size: 12px; color: #666; }
        .timeline-body { margin-top: 5px; font-style: italic; font-size: 13px; }
        .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 13px; font-weight: bold; }
        .status-rascunho { background: #e0e0e0; color: #616161; }
        .status-lavrada { background: #bbdefb; color: #1976d2; }
        .status-enviada { background: #c8e6c9; color: #388e3c; }
        .status-ciente { background: #d1c4e9; color: #512da8; }
        .status-cobranca { background: #fff9c4; color: #fbc02d; }
        .img-preview-item { position: relative; display: inline-block; margin: 5px; }
        .img-preview-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .img-preview-item.inativa img { opacity: 0.4; filter: grayscale(1); }
        .img-actions { position: absolute; bottom: 5px; right: 5px; display: flex; gap: 2px; }
    </style>
</head>
<body>
    <header><?php include '_partials/menu.php'; ?></header>

    <main class="main-content">
        <div class="container">
            <div class="header">
                <a href="lista.php" class="back-link"><i class="material-icons">arrow_back</i> Voltar</a>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <h1 id="page-title">Notificação</h1>
                    <div id="status-badge-container"></div>
                </div>
            </div>

            <div id="lifecycle-section" class="lifecycle-controls" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div class="section-title" style="margin-bottom: 10px; font-weight: bold;">Ações de Status</div>
                <div id="lifecycle-actions" class="lifecycle-actions" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
            </div>

            <div id="form-lock-info" class="lock-info" style="display: none;">
                <i class="material-icons">lock</i>
                <span>Documento assinado/lavrado. Campos bloqueados para edição.</span>
            </div>

            <div class="content">
                <div class="form-section">
                    <form id="documentForm" onsubmit="return false;">
                        <?php include '_form.php'; ?>
                    </form>
                </div>
                
                <div class="preview-section">
                    <h3>Preview do Documento</h3>
                    <div class="pdf-preview" id="pdfPreview" style="background: #fafafa; border: 1px solid #eee; min-height: 400px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <div class="pdf-placeholder" id="pdfPlaceholder">
                            <div>📋</div>
                            <p>Clique em "Gerar Preview" para visualizar o PDF</p>
                        </div>
                        <iframe id="pdfViewer" style="display: none; width: 100%; height: 600px; border: none;"></iframe>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn blue" onclick="gerarPDF()">
                            <i class="material-icons left">visibility</i> Gerar Preview
                        </button>
                        <button type="button" class="btn-secondary" onclick="baixarPDF()" id="btnDownload" style="display: none;">
                            💾 Baixar PDF
                        </button>
                    </div>
                    
                    <div class="timeline-section section-card" id="history-section" style="display: none; margin-top: 30px;">
                        <div class="section-title" style="font-weight: bold; margin-bottom: 15px;">Histórico de Fases</div>
                        <div id="timeline-content" class="timeline-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/funcs.js?v=<?php echo time(); ?>"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/regimento-busca.js?v=<?php echo time(); ?>"></script>
    
    <script>
        const NOTIFICACAO_ID = <?php echo isset($_GET['id']) ? (int)$_GET['id'] : 'null'; ?>;
        const USUARIO_ID = <?php echo $usuarioLogado['id']; ?>;
        const PODE_EDITAR_CAMPOS = <?php echo $podeEditarCampos ? 'true' : 'false'; ?>;
        const PODE_REMOVER_IMAGEM = <?php echo $podeRemoverImagem ? 'true' : 'false'; ?>;
        const PODE_ANEXAR_IMAGEM = <?php echo $podeAnexarImagem ? 'true' : 'false'; ?>;
        const PODE_LAVRAR = <?php echo $podeLavrar ? 'true' : 'false'; ?>;
        const PODE_REVOGAR = <?php echo $podeRevogarAssinatura ? 'true' : 'false'; ?>;
        const PODE_ENVIAR = <?php echo $podeEnviar ? 'true' : 'false'; ?>;
        const PODE_CIENCIA = <?php echo $podeRegistrarCiencia ? 'true' : 'false'; ?>;
        const PODE_RECURSO = <?php echo $podeRegistrarRecurso ? 'true' : 'false'; ?>;
        const PODE_JULGAR = <?php echo $podeJulgarRecurso ? 'true' : 'false'; ?>;
        const PODE_ENCERRAR = <?php echo $podeEncerrar ? 'true' : 'false'; ?>;
        // EH_ADMIN_DEV, PERMISSOES_USUARIO e API_BASE_URL_PHP já são definidos pelo menu.php

        let notificationData = null;

        $(document).ready(function() {
            M.AutoInit();
        });

        document.addEventListener('DOMContentLoaded', async function() {
            await fetchInitialData();
            if (NOTIFICACAO_ID) {
                await loadNotificationData();
            } else {
                if (typeof addFato === 'function') addFato();
            }
        });

        async function fetchInitialData() {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/config.php`);
                const data = await response.json();
                const $assunto = $('#assunto_id'), $tipo = $('#tipo_id');
                $assunto.empty().append('<option value="" disabled selected>Selecione o assunto...</option>');
                if (data.assuntos) data.assuntos.forEach(a => $assunto.append(`<option value="${a.id}">${a.descricao}</option>`));
                $tipo.empty().append('<option value="" disabled selected>Selecione o tipo...</option>');
                if (data.tipos) data.tipos.forEach(t => $tipo.append(`<option value="${t.id}">${t.nome}</option>`));
                $('select').formSelect();
            } catch (e) { console.error('Erro dados iniciais', e); }
        }

        async function loadNotificationData() {
            try {
                const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${NOTIFICACAO_ID}`);
                notificationData = await res.json();
                if (typeof setSelectedArticles === 'function') setSelectedArticles(notificationData.artigos || []);
                renderNotificationView(notificationData);
            } catch (e) { M.toast({html: 'Erro ao carregar', classes: 'red'}); }
        }

        function renderNotificationView(data) {
            $('#page-title').text(`Notificação #${data.numero}/${data.ano}`);
            $('#status-badge-container').html(`<span class="status-badge status-${data.status_slug}">${data.status_nome}</span>`);
            
            $('#notificacao_id').val(data.id);
            $('#numero').val(data.numero + '/' + data.ano);
            $('#unidade').val(data.unidade);
            $('#bloco').val(data.bloco);
            $('#data_emissao').val(data.data_emissao);
            $('#valor_multa').val(data.valor_multa);
            $('#fundamentacao_legal').val(data.fundamentacao_legal);
            $('#url_recurso').val(data.url_recurso);
            
            // Aplicar controle de edição de campos
            if (!PODE_EDITAR_CAMPOS || (data.status_slug !== 'rascunho' && !EH_ADMIN_DEV)) {
                $('#documentForm input:not(#notificacao_id):not(#ocorrencia_id)').prop('readonly', true).css('background-color', '#f5f5f5');
                $('#documentForm textarea').prop('readonly', true).css('background-color', '#f5f5f5');
                $('#documentForm select').prop('disabled', true).formSelect();
                $('#documentForm').addClass('form-locked');
                $('#form-lock-info').show();
                $('#btnSalvar').hide();
            }
            
            // Ocultar input de anexar imagem se não tiver permissão
            if (!PODE_ANEXAR_IMAGEM) {
                $('#fotos_fatos').closest('.form-group').hide();
            }
            
            if (data.ocorrencia_id) {
                $('#ocorrencia_id').val(data.ocorrencia_id);
                $('#ocorrencia_titulo').text(`Ocorrência #${data.ocorrencia_id}: ${data.ocorrencia_titulo || ''}`);
                $('#ver_ocorrencia_link').attr('href', `ocorrencia_detalhe.php?id=${data.ocorrencia_id}`);
                $('#ocorrencia_info').show();
                $('#ocorrencia_busca_section').hide();
            } else if (PODE_EDITAR_CAMPOS) {
                $('#ocorrencia_busca_section').show();
            }
            
            setTimeout(() => {
                $('#tipo_id').val(data.tipo_id).formSelect();
                $('#assunto_id').val(data.assunto_id).formSelect();
                if (typeof toggleMultaField === 'function') toggleMultaField();
                M.updateTextFields();
            }, 300);

            $('#fatos-container').empty();
            if (data.fatos) data.fatos.forEach(f => { if (typeof addFato === 'function') addFato(f); });

            renderLifecycleActions(data);
            renderTimeline(data.fase_log || []);
            renderNotificationImages(data.imagens || []);
        }

        function renderTimeline(logs) {
            const $cont = $('#timeline-content');
            if (!logs || !logs.length) { $('#history-section').hide(); return; }
            $('#history-section').show();
            $cont.empty();
            logs.forEach(log => {
                $cont.append(`<div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-header"><strong>${log.fase_nova}</strong> <span>${log.created_at}</span></div>
                    <div class="timeline-user">por ${log.usuario_nome}</div>
                    ${log.observacao ? `<div class="timeline-body">${log.observacao}</div>` : ''}
                </div>`);
            });
        }

        function renderNotificationImages(imagens) {
            const $cont = $('#preview-container');
            $cont.empty();
            imagens.forEach(img => {
                const url = img.caminho_arquivo.includes('/') ? img.caminho_arquivo : 'uploads/imagens/' + img.caminho_arquivo;
                const isInactive = (img.inactive == 1);
                const actionsHtml = PODE_REMOVER_IMAGEM ? `
                    <div class="img-actions">
                         <button type="button" class="btn-floating btn-small ${isInactive ? 'green' : 'orange'}" onclick="alternarImagem(${img.id}, ${isInactive ? 0 : 1})">
                            <i class="material-icons">${isInactive ? 'check' : 'block'}</i>
                         </button>
                    </div>
                ` : '';
                $cont.append(`
                    <div class="img-preview-item existing-image ${isInactive ? 'inativa' : ''}" id="img_${img.id}">
                        <img src="${url}" class="${isInactive ? 'grayscale' : ''}" title="${img.nome_original}" onclick="window.open('${url}')">
                        ${actionsHtml}
                    </div>
                `);
            });
        }

        async function alternarImagem(id, status) {
            console.log('alternarImagem called:', { id, status });
            try {
                const body = JSON.stringify({ alternar_imagem_ocorrencia: true, id: id, status: status });
                console.log('Request body:', body);
                const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: body
                });
                const text = await res.text();
                console.log('Response status:', res.status, 'Body:', text);
                if (res.ok) { 
                    M.toast({html: 'Imagem atualizada'}); 
                    loadNotificationData(); 
                } else {
                    M.toast({html: 'Erro: ' + text});
                }
            } catch(e) { 
                console.error('Fetch error:', e);
                M.toast({html: 'Erro ao atualizar imagem'}); 
            }
        }

        function renderLifecycleActions(data) {
            const $cont = $('#lifecycle-actions');
            $cont.empty();
            $('#lifecycle-section').show();
            const slug = data.status_slug;
            const actions = [];
            
            if (slug === 'rascunho' && PODE_LAVRAR) actions.push({ label: 'Lavrar', icon: 'fountain_pen', color: 'blue', target: 'lavrada' });
            else if (slug === 'lavrada') {
               if (PODE_REVOGAR) actions.push({ label: 'Retornar Rascunho', icon: 'undo', color: 'orange', target: 'rascunho' });
               if (PODE_ENVIAR) actions.push({ label: 'Enviar', icon: 'send', color: 'green', target: 'enviada' });
            }
            else if (slug === 'enviada' && PODE_CIENCIA) actions.push({ label: 'Registrar Ciência', icon: 'done_all', color: 'green', target: 'ciente' });
            
            if (actions.length) {
                actions.forEach(a => {
                    const $btn = $(`<button class="btn ${a.color}"><i class="material-icons left">${a.icon}</i> ${a.label}</button>`);
                    $btn.on('click', () => changePhase(a.target, a.label));
                    $cont.append($btn);
                });
            } else { $('#lifecycle-section').hide(); }
        }

        async function changePhase(targetSlug, label) {
            const obs = prompt(`Observação para a ação "${label}":`);
            if (obs === null) return;
            try {
                const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mudar_fase: true, id: NOTIFICACAO_ID, nova_fase: targetSlug, observacao: obs })
                });
                if (res.ok) { M.toast({html: 'Status atualizado'}); loadNotificationData(); }
                else { const err = await res.json(); M.toast({html: 'Erro: '+err.message}); }
            } catch(e) { M.toast({html: 'Erro de conexão'}); }
        }
        
        async function salvarNotificacao() {
            if (typeof getFormData !== 'function') return;
            const dados = getFormData();
            try {
                const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                if (res.ok) { 
                    const r = await res.json();
                    M.toast({html: 'Salvo com sucesso'});
                    if (!NOTIFICACAO_ID) window.location.href = 'editar.php?id='+r.id;
                    else loadNotificationData();
                }
            } catch(e) { M.toast({html: 'Erro ao salvar'}); }
        }

        async function buscarOcorrencias() {
            const busca = document.getElementById('ocorrencia_busca').value.trim();
            const container = document.getElementById('ocorrencia_busca_resultados');
            if (busca.length < 2) {
                container.innerHTML = '<p style="color: #666;">Digite pelo menos 2 caracteres.</p>';
                return;
            }
            container.innerHTML = '<p style="color: #666;">Buscando...</p>';
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?buscar_ocorrencias=${encodeURIComponent(busca)}`);
                const ocorrencias = await response.json();
                if (!ocorrencias.length) {
                    container.innerHTML = '<p style="color: #666;">Nenhuma ocorrência homologada encontrada.</p>';
                    return;
                }
                container.innerHTML = '<div style="display: flex; flex-direction: column; gap: 8px;">' +
                    ocorrencias.map(o => `<div style="background: #f5f5f5; padding: 10px; border-radius: 4px; cursor: pointer;" onclick="vincularOcorrencia(${o.id})">
                        <strong>#${o.id}</strong> - ${o.titulo}<br><small style="color: #666;">${o.unidades || 'Sem unidades'}</small>
                    </div>`).join('') + '</div>';
            } catch (e) { container.innerHTML = '<p style="color: red;">Erro ao buscar.</p>'; }
        }

        async function vincularOcorrencia(id) {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php?id=${id}`);
                const occ = await response.json();
                document.getElementById('ocorrencia_id').value = occ.id;
                document.getElementById('ocorrencia_titulo').textContent = `Ocorrência #${occ.id}: ${occ.titulo}`;
                document.getElementById('ver_ocorrencia_link').href = `ocorrencia_detalhe.php?id=${occ.id}`;
                document.getElementById('ocorrencia_info').style.display = 'block';
                document.getElementById('ocorrencia_busca_section').style.display = 'none';
                document.getElementById('ocorrencia_busca_resultados').innerHTML = '';
                M.toast({html: 'Ocorrência vinculada!', classes: 'green'});
            } catch (e) { M.toast({html: 'Erro ao vincular', classes: 'red'}); }
        }

        let sincronizando = false;
        
        async function sincronizarEvidencias() {
            const ocorrenciaId = document.getElementById('ocorrencia_id').value;
            if (!ocorrenciaId || !NOTIFICACAO_ID) {
                M.toast({html: 'Vincule uma ocorrência primeiro.', classes: 'red'});
                return;
            }
            if (sincronizando) {
                M.toast({html: 'Sincronização em andamento...', classes: 'orange'});
                return;
            }
            sincronizando = true;
            try {
                const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'sincronizar_evidencias',
                        notificacao_id: NOTIFICACAO_ID,
                        ocorrencia_id: parseInt(ocorrenciaId)
                    })
                });
                const result = await res.json();
                if (res.ok) {
                    M.toast({html: result.message || 'Sincronizado!', classes: 'green'});
                    loadNotificationData();
                } else {
                    M.toast({html: 'Erro: ' + (result.message || 'Falha'), classes: 'red'});
                }
            } catch(e) { M.toast({html: 'Erro de conexão', classes: 'red'}); }
            finally { sincronizando = false; }
        }
    </script>
</body>
</html>
