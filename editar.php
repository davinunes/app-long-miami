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

// Permissões de fase
$podeLavrar = isAdmin() || temPermissao('notificacao.lavrar');
$podeRevogarAssinatura = isAdmin() || temPermissao('notificacao.retornar_rascunho');
$podeEnviar = isAdmin() || temPermissao('notificacao.marcar_enviada');
$podeRegistrarCiencia = isAdmin() || temPermissao('notificacao.registrar_ciencia');
$podeRegistrarRecurso = isAdmin() || temPermissao('notificacao.registrar_recurso');
$podeJulgarRecurso = isAdmin() || temPermissao('notificacao.julgar_recurso');
$podeEncerrar = isAdmin() || temPermissao('notificacao.encerrar');
$podeAlterarFase = isAdmin() || temPermissao('notificacao.alterar_fase');
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
        .timeline-section { margin-top: 30px; }
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
                <a href="lista.php" class="back-link"><i class="material-icons">arrow_back</i> Voltar para Lista</a>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <h1 id="page-title">Nova Notificação</h1>
                    <div id="status-badge-container"></div>
                </div>
            </div>

            <div id="lifecycle-section" class="lifecycle-controls" style="display: none;">
                <div class="section-title">Controle do Ciclo de Vida</div>
                <div id="lifecycle-actions" class="lifecycle-actions">
                    <!-- Botões injetados via JS -->
                </div>
            </div>

            <div id="form-lock-info" class="lock-info" style="display: none;">
                <i class="material-icons">lock</i>
                <span>Esta notificação já foi lavrada e o formulário está bloqueado para edição.</span>
            </div>

            <div class="content">
                <div class="form-section" id="form-section-container">
                    <form id="documentForm" onsubmit="return false;">
                        <?php include '_form.php'; ?>
                    </form>
                </div>
                
                <div class="preview-section">
                    <h3>Visualização do PDF</h3>
                    <div class="pdf-preview" id="pdfPreview">
                        <div class="pdf-placeholder" id="pdfPlaceholder">
                            <div>📋</div>
                            <p>O PDF gerado aparecerá aqui</p>
                        </div>
                        <iframe id="pdfViewer" style="display: none; width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn-secondary" onclick="baixarPDF()" id="btnDownload" style="display: none;">
                            💾 Baixar PDF
                        </button>
                    </div>

                    <!-- Sessão do Histórico -->
                    <div class="timeline-section section-card" id="history-section" style="display: none; margin-top: 30px;">
                        <div class="section-title">Histórico de Fases</div>
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
        
        let notificationData = null;
        let ocorrenciaVinculadaData = null;

        // Permissões de fase específicas para a UI se necessário (ou use temPermissao global)
        const PODE_LAVRAR = <?php echo $podeLavrar ? 'true' : 'false'; ?>;
        const PODE_REVOGAR = <?php echo $podeRevogarAssinatura ? 'true' : 'false'; ?>;
        const PODE_ENVIAR = <?php echo $podeEnviar ? 'true' : 'false'; ?>;
        const PODE_CIENCIA = <?php echo $podeRegistrarCiencia ? 'true' : 'false'; ?>;
        const PODE_RECURSO = <?php echo $podeRegistrarRecurso ? 'true' : 'false'; ?>;
        const PODE_JULGAR = <?php echo $podeJulgarRecurso ? 'true' : 'false'; ?>;
        const PODE_ENCERRAR = <?php echo $podeEncerrar ? 'true' : 'false'; ?>;

        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
        });

        document.addEventListener('DOMContentLoaded', async function() {
            await fetchInitialData();
            $('select').formSelect();
            configurarCampoBloco();
            vincularCamposUnidadeBloco();
            inicializarBuscaRegimento();
            
            $('#tipo_id').on('change', toggleMultaField);
            
            if (NOTIFICACAO_ID) {
                await loadNotificationData();
            } else {
                $('#btnSalvar').on('click', salvarNotificacao);
                // Se estiver criando a partir de uma ocorrência
                const urlParams = new URLSearchParams(window.location.search);
                const occId = urlParams.get('ocorrencia_id');
                if (occId) {
                    vincularOcorrencia(occId);
                } else {
                    addFato();
                }
            }
        });

        async function loadNotificationData() {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${NOTIFICACAO_ID}`);
                if (!response.ok) throw new Error('Erro ao carregar notificação');
                notificationData = await response.json();
                renderNotificationView(notificationData);
            } catch (error) {
                M.toast({html: 'Erro: ' + error.message, classes: 'red'});
            }
        }

        function renderNotificationView(data) {
            $('#page-title').text(`Notificação #${data.numero}/${data.ano}`);
            $('#status-badge-container').html(`<span class="status-badge status-${data.status_slug}">${data.status_nome}</span>`);
            preencherFormulario(data);
            renderLifecycleActions(data);
            renderTimeline(data.fase_log || []);
            
            const btnSalvar = $('#btnSalvar');
            if (data.status_slug === 'rascunho' || EH_ADMIN_DEV) {
                btnSalvar.off('click').on('click', salvarNotificacao);
                btnSalvar.show();
            } else {
                $('#documentForm').addClass('form-locked');
                $('#form-lock-info').show();
                btnSalvar.prop('disabled', true).hide();
            }

            if (data.ocorrencia_id) {
                $('#ocorrencia_id').val(data.ocorrencia_id);
                $('#ocorrencia_titulo').text(`Ocorrência #${data.ocorrencia_id}: ${data.ocorrencia_titulo}`);
                $('#ver_ocorrencia_link').attr('href', `ocorrencia_detalhe.php?id=${data.ocorrencia_id}`);
                $('#ocorrencia_info').show();
                $('#ocorrencia_busca_section').hide();
            }
            if (data.imagens) renderNotificationImages(data.imagens);
        }

        function preencherFormulario(data) {
            $('#notificacao_id').val(data.id);
            $('#numero').val(`${data.numero}/${data.ano}`);
            $('#unidade').val(data.unidade);
            $('#bloco').val(data.bloco);
            $('#data_emissao').val(data.data_emissao);
            $('#valor_multa').val(data.valor_multa);
            $('#url_recurso').val(data.url_recurso);
            setTimeout(() => {
                $('#tipo_id').val(data.tipo_id).formSelect();
                $('#assunto_id').val(data.assunto_id).formSelect();
                toggleMultaField();
            }, 500);
            $('#fatos-container').empty();
            if (data.fatos && data.fatos.length > 0) data.fatos.forEach(fato => addFato(fato));
            else addFato();
            $('#fundamentacao_legal').val(data.fundamentacao_legal);
            M.textareaAutoResize($('#fundamentacao_legal'));
            if (data.artigos) renderSelectedArticles(data.artigos);
        }

        function toggleMultaField() {
            const tipoId = $('#tipo_id').val();
            // Se for multa (id=2), exibe o campo
            if (tipoId == '2' || tipoId == 2) {
                $('#valor_multa_container').slideDown();
            } else {
                $('#valor_multa_container').slideUp();
            }
        }

        function addFato(texto = '') {
            const container = $('#fatos-container');
            const fatoHtml = $(`
                <div class="fato-item">
                    <textarea class="materialize-textarea fato-texto" placeholder="Descreva aqui o fato observado...">${texto}</textarea>
                    <button type="button" class="btn-remove-fato red btn-flat" onclick="removerFato(this)">×</button>
                </div>
            `);
            container.append(fatoHtml);
            M.textareaAutoResize(fatoHtml.find('textarea'));
        }

        function removerFato(btn) {
            const container = $('#fatos-container');
            if (container.children().length > 1) {
                $(btn).closest('.fato-item').remove();
            } else {
                $(btn).closest('.fato-item').find('textarea').val('');
            }
        }

        function renderSelectedArticles(artigos) {
            const container = $('#selected-articles-list');
            container.empty();
            if (!artigos || artigos.length === 0) {
                container.append('<p style="color: #999;">Nenhum artigo selecionado.</p>');
                return;
            }
            artigos.forEach((art, index) => {
                const artHtml = $(`
                    <div class="selected-article-item">
                        <div class="article-notation">${art.notation}</div>
                        <div class="article-text">${art.text || ''}</div>
                        <button type="button" class="btn-remove-article" onclick="removerArtigo(${index})">×</button>
                    </div>
                `);
                container.append(artHtml);
            });
        }

        function removerArtigo(index) {
            if (notificationData && notificationData.artigos) {
                notificationData.artigos.splice(index, 1);
                renderSelectedArticles(notificationData.artigos);
            }
        }

        function formatDate(data) {
            if (!data) return '-';
            const d = new Date(data + 'T00:00:00');
            return d.toLocaleDateString('pt-BR');
        }

        function renderLifecycleActions(data) {
            const container = $('#lifecycle-actions');
            container.empty();
            $('#lifecycle-section').show();
            const slug = data.status_slug;
            const actions = [];
            
            let carenciaPassou = false;
            if (data.data_ciencia) {
                const dataCiencia = new Date(data.data_ciencia.split(' ')[0]);
                const hoje = new Date();
                hoje.setHours(0,0,0,0);
                const diffTime = Math.abs(hoje - dataCiencia);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                if (diffDays >= 7) carenciaPassou = true;
            }

            if (slug === 'rascunho' && PODE_LAVRAR) {
                actions.push({ label: 'Lavrar (Assinar)', icon: 'fountain_pen', color: 'blue', target: 'lavrada' });
            } else if (slug === 'lavrada') {
                if (PODE_REVOGAR) actions.push({ label: 'Revogar Assinatura', icon: 'undo', color: 'orange', target: 'rascunho' });
                if (PODE_ENVIAR) actions.push({ label: 'Marcar como Enviada', icon: 'send', color: 'green', target: 'enviada' });
            } else if (slug === 'enviada') {
                if (PODE_CIENCIA) actions.push({ label: 'Registrar Ciência', icon: 'done_all', color: 'green', target: 'ciente' });
            } else if (slug === 'ciente') {
                if (PODE_RECURSO) actions.push({ label: 'Registrar Recurso', icon: 'gavel', color: 'amber', target: 'em_recurso' });
                if (carenciaPassou && temPermissao('notificacao.marcar_cobranca')) {
                    actions.push({ label: 'Lançar Cobrança', icon: 'payments', color: 'blue', target: 'cobranca' });
                }
            } else if (slug === 'em_recurso' && PODE_JULGAR) {
                actions.push({ label: 'Deferir Recurso', icon: 'thumb_up', color: 'green', target: 'recurso_deferido' });
                actions.push({ label: 'Indeferir Recurso', icon: 'thumb_down', color: 'red', target: 'recurso_indeferido' });
            } else if (slug === 'recurso_indeferido') {
                if (temPermissao('notificacao.marcar_cobranca')) {
                    actions.push({ label: 'Lançar Cobrança', icon: 'payments', color: 'blue', target: 'cobranca' });
                }
            } else if (slug === 'cobranca' && PODE_ENCERRAR) {
                actions.push({ label: 'Encerrar (Lançado no Boleto)', icon: 'check_circle', color: 'green', target: 'encerrada' });
            }
            if ((slug === 'cobranca' || slug === 'encerrada') && temPermissao('notificacao.reabrir')) {
                actions.push({ label: 'Reabrir', icon: 'refresh', color: 'orange italic', target: 'reabrir' });
            }
            if (actions.length === 0) { $('#lifecycle-section').hide(); return; }
            actions.forEach(act => {
                const btn = $(`<button class="btn ${act.color}"><i class="material-icons left">${act.icon}</i> ${act.label}</button>`);
                btn.on('click', () => changePhase(act.target, act.label));
                container.append(btn);
            });
        }

        async function changePhase(targetSlug, label) {
            let obs = '';
            if (['rascunho', 'encerrada', 'recurso_indeferido', 'reabrir', 'cobranca'].includes(targetSlug)) {
                let msg = `Informe uma observação para a ação "${label}":`;
                if (targetSlug === 'reabrir') msg = "Justifique a reabertura da notificação (será revertida para a fase anterior):";
                obs = prompt(msg);
                if (obs === null) return;
            } else if (!confirm(`Confirmar ação: "${label}"?`)) return;
            
            const payload = { mudar_fase: true, id: NOTIFICACAO_ID, nova_fase: targetSlug, observacao: obs };
            if (targetSlug === 'ciente') payload.metodo_ciencia = prompt('Método de ciência (ex: Assinatura, WhatsApp):', 'Assinatura');
            
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                M.toast({html: 'Status atualizado!', classes: 'green'});
                loadNotificationData();
            } catch (error) { M.toast({html: 'Erro: ' + error.message, classes: 'red'}); }
        }

        function renderTimeline(logs) {
            const container = $('#timeline-content');
            const section = $('#history-section');
            if (!logs || logs.length === 0) { section.hide(); return; }
            section.show();
            container.empty();
            const statusNames = { 'rascunho': 'Rascunho', 'lavrada': 'Lavrada', 'enviada': 'Enviada', 'ciente': 'Ciente', 'em_recurso': 'Em Recurso', 'recurso_deferido': 'Recurso Deferido', 'recurso_indeferido': 'Recurso Indeferido', 'cobranca': 'Em Cobrança', 'encerrada': 'Encerrada' };
            logs.forEach(log => {
                const title = log.fase_anterior ? `${statusNames[log.fase_anterior] || log.fase_anterior} ➜ ${statusNames[log.fase_nova] || log.fase_nova}` : `Iniciado como ${statusNames[log.fase_nova] || log.fase_nova}`;
                container.append($(`
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-header"><span class="timeline-title">${title}</span><span class="timeline-date">${formatDateTime(log.created_at)}</span></div>
                        <div class="timeline-user">por ${log.usuario_nome || 'Sistema'}</div>
                        ${log.observacao ? `<div class="timeline-body">${log.observacao}</div>` : ''}
                    </div>
                `));
            });
        }

        function renderNotificationImages(imagens) {
            const container = $('#preview-container');
            container.empty();
            imagens.forEach(img => {
                container.append($(`
                    <div class="img-preview-item" id="img_${img.id}">
                        <img src="${img.caminho_arquivo}" alt="${img.nome_original}" onclick="window.open('${img.caminho_arquivo}', '_blank')">
                        <div class="img-name">${img.nome_original}</div>
                        ${EH_ADMIN_DEV || notificationData.status_slug === 'rascunho' ? `<button type="button" class="btn-remove" onclick="removerImagem(${img.id})">×</button>` : ''}
                    </div>
                `));
            });
        }

        async function removerImagem(id) {
            if (!confirm('Deseja remover esta imagem?')) return;
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ deletar_imagem: true, id: id }) });
                if (response.ok) { $(`#img_${id}`).remove(); M.toast({html: 'Imagem removida', classes: 'green'}); }
            } catch (e) { M.toast({html: 'Erro ao remover imagem', classes: 'red'}); }
        }

        function formatDateTime(data) {
            if (!data) return '-';
            var d = new Date(data);
            return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
        }

        async function buscarOcorrencias() {
            const busca = document.getElementById('ocorrencia_busca').value.trim();
            const container = document.getElementById('ocorrencia_busca_resultados');
            if (busca.length < 2 && busca.length > 0) return;
            container.innerHTML = '<p>Buscando...</p>';
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?buscar_ocorrencias=${encodeURIComponent(busca)}`);
                const ocorrencias = await response.json();
                if (ocorrencias.length === 0) { container.innerHTML = '<p>Nenhuma ocorrência encontrada.</p>'; return; }
                container.innerHTML = ocorrencias.map(o => `<div class="search-result-item" onclick="vincularOcorrencia(${o.id})"><strong>#${o.id}</strong> - ${o.titulo}<br><small>${o.unidades || 'Sem unidades'} | ${formatDate(o.data_fato)}</small></div>`).join('');
            } catch (error) { container.innerHTML = '<p style="color:red">Erro ao buscar.</p>'; }
        }

        async function vincularOcorrencia(id) {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php?id=${id}`);
                ocorrenciaVinculadaData = await response.json();
                $('#ocorrencia_id').val(ocorrenciaVinculadaData.id);
                $('#ocorrencia_titulo').text(`Ocorrência #${ocorrenciaVinculadaData.id}: ${ocorrenciaVinculadaData.titulo}`);
                $('#ver_ocorrencia_link').attr('href', `ocorrencia_detalhe.php?id=${ocorrenciaVinculadaData.id}`);
                $('#ocorrencia_info').show();
                $('#ocorrencia_busca_section').hide();
                if (!$('#unidade').val()) {
                    const u = ocorrenciaVinculadaData.unidades && ocorrenciaVinculadaData.unidades[0];
                    if (u) { $('#unidade').val(u.unidade_numero); $('#bloco').val(u.unidade_bloco); M.updateTextFields(); }
                }
                if ($('#fatos-container').children().length <= 1 && !$('#fatos-container textarea').val()) {
                    $('#fatos-container').empty();
                    addFato(ocorrenciaVinculadaData.descricao_fato);
                }
                M.toast({html: 'Ocorrência vinculada!', classes: 'green'});
            } catch (e) { M.toast({html: 'Erro ao vincular', classes: 'red'}); }
        }
        async function fetchInitialData() {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/config.php`);
                const data = await response.json();
                initialData = data;
                const assuntoSelect = $('#assunto_id');
                assuntoSelect.empty().append('<option value="" disabled selected>Selecione um assunto</option>');
                data.assuntos.forEach(a => assuntoSelect.append(`<option value="${a.id}">${a.descricao}</option>`));
                const tipoSelect = $('#tipo_id');
                tipoSelect.empty().append('<option value="" disabled selected>Selecione o tipo</option>');
                data.tipos.forEach(t => tipoSelect.append(`<option value="${t.id}">${t.nome}</option>`));
            } catch (e) { M.toast({html: 'Erro ao carregar dados iniciais', classes: 'red'}); }
        }

        function configurarCampoBloco() {
            $('#unidade').on('input', function() {
                const val = $(this).val().toUpperCase();
                const match = val.match(/^([A-Z]+)(\d+)$/);
                if (match) { $('#bloco').val(match[1]); $(this).val(match[2]); M.updateTextFields(); }
            });
        }

        function vincularCamposUnidadeBloco() {
            $('#bloco, #unidade').on('change', function() {
                const b = $('#bloco').val();
                const u = $('#unidade').val();
                if (b && u) console.log(`Unidade vinculada: ${b}${u}`);
            });
        }

        function inicializarBuscaRegimento() {
            $('#input-busca-regimento').on('input', function() {
                const busca = $(this).val().toLowerCase();
                const resultados = $('#resultados-regimento');
                resultados.empty();
                if (busca.length < 2) return;
                if (!initialData || !initialData.permissoes) return;
                // No contexto de regimento, buscamos nos artigos iniciais se houver
                // Se não, simulamos ou buscamos via API se implementado.
                // Aqui vamos usar um mock ou buscar do config se disponível.
            });
        }

        function abrirModalArtigos() {
             const modal = M.Modal.getInstance(document.getElementById('modal-regimento'));
             if (modal) modal.open();
        }

        async function salvarNotificacao() {
            const btn = $('#btnSalvar');
            btn.prop('disabled', true).text('Salvando...');
            const dados = getFormData();
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                M.toast({html: 'Salvo com sucesso!', classes: 'green'});
                if (!NOTIFICACAO_ID) window.location.href = `editar.php?id=${result.id}`;
                else loadNotificationData();
            } catch (error) { M.toast({html: 'Erro: ' + error.message, classes: 'red'}); }
            finally { btn.prop('disabled', false).text(NOTIFICACAO_ID ? 'Salvar Alterações' : 'Criar Notificação'); }
        }

        function getFormData() {
            const fatos = [];
            $('.fato-texto').each(function() { if ($(this).val().trim()) fatos.push($(this).val().trim()); });
            const data = {
                id: $('#notificacao_id').val(),
                unidade: $('#unidade').val(),
                bloco: $('#bloco').val(),
                numero: $('#numero').val(),
                data_emissao: $('#data_emissao').val(),
                assunto_id: $('#assunto_id').val(),
                tipo_id: $('#tipo_id').val(),
                valor_multa: $('#valor_multa').val(),
                url_recurso: $('#url_recurso').val(),
                fundamentacao_legal: $('#fundamentacao_legal').val(),
                ocorrencia_id: $('#ocorrencia_id').val(),
                fatos: fatos,
                artigos: notificationData ? notificationData.artigos : []
            };
            return data;
        }

        function inicializarBuscaRegimento() {
            $('#input-busca-regimento').on('keyup', function(e) {
                if (e.key === 'Enter') buscarRegimento();
            });
        }

        async function buscarRegimento() {
            const query = $('#input-busca-regimento').val();
            const container = $('#resultados-regimento');
            container.html('<p>Buscando no regimento...</p>');
            // Implementação de busca seria via API ou local se regimento_json carregado
            container.html('<p class="grey-text">Busca de regimento não implementada localmente. Use a fundamentação legal manual.</p>');
        }
    </script>
</body>
</html>
