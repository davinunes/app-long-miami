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
                
                // Sincroniza artigos com o buscador do regimento (se disponível)
                if (typeof setSelectedArticles === 'function') {
                    setSelectedArticles(notificationData.artigos || []);
                }
                
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
                if (typeof toggleMultaField === 'function') toggleMultaField();
            }, 500);

            $('#fatos-container').empty();
            if (data.fatos && data.fatos.length > 0) {
                data.fatos.forEach(fato => addFato(fato));
            } else if (data.status_slug === 'rascunho') {
                if (typeof addFato === 'function') addFato();
            }
            
            $('#fundamentacao_legal').val(data.fundamentacao_legal);
            if (typeof autoExpand === 'function') autoExpand(document.getElementById('fundamentacao_legal'));
        }

        function renderLifecycleActions(data) {
            const container = $('#lifecycle-actions');
            container.empty();
            $('#lifecycle-section').show();
            const slug = data.status_slug;
            const actions = [];
            
            let carenciaPassou = false;
            if (data.data_ciencia) {
                // Cálculo de 7 dias de carência simplificado
                const dataCiencia = new Date(data.data_ciencia.split(' ')[0]);
                const hoje = new Date();
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
                if (carenciaPassou) {
                    actions.push({ label: 'Lançar Cobrança', icon: 'payments', color: 'blue', target: 'cobranca' });
                }
            } else if (slug === 'em_recurso' && PODE_JULGAR) {
                actions.push({ label: 'Deferir Recurso', icon: 'thumb_up', color: 'green', target: 'recurso_deferido' });
                actions.push({ label: 'Indeferir Recurso', icon: 'thumb_down', color: 'red', target: 'recurso_indeferido' });
            } else if (slug === 'recurso_indeferido') {
                actions.push({ label: 'Lançar Cobrança', icon: 'payments', color: 'blue', target: 'cobranca' });
            } else if (slug === 'cobranca' && PODE_ENCERRAR) {
                actions.push({ label: 'Encerrar (No Boleto)', icon: 'check_circle', color: 'green', target: 'encerrada' });
            }
            
            if ((slug === 'cobranca' || slug === 'encerrada') || (EH_ADMIN_DEV)) {
                actions.push({ label: 'Reabrir', icon: 'refresh', color: 'red italic', target: 'reabrir' });
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
                obs = prompt(`Informe uma observação para a ação "${label}":`);
                if (obs === null) return;
            } else if (!confirm(`Confirmar ação: "${label}"?`)) return;
            
            const payload = { mudar_fase: true, id: NOTIFICACAO_ID, nova_fase: targetSlug, observacao: obs };
            if (targetSlug === 'ciente') payload.metodo_ciencia = prompt('Método de ciência:', 'Assinatura');
            
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
            const statusNames = { 'rascunho': 'Rascunho', 'lavrada': 'Lavrada', 'enviada': 'Enviada', 'ciente': 'Ciente', 'em_recurso': 'Em Recurso', 'recurso_deferido': 'Recurso Deferido', 'recurso_indeferido': 'Recurso Indeferido', 'cobranca': 'Cobrança', 'encerrada': 'Encerrada' };
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
                const url = img.caminho_arquivo.includes('/') ? img.caminho_arquivo : 'uploads/imagens/' + img.caminho_arquivo;
                const isInactive = img.inactive == 1;

                container.append($(`
                    <div class="img-preview-item existing-image ${isInactive ? 'inativa' : ''}" id="img_${img.id}">
                        <img src="${url}" alt="${img.nome_original}" class="${isInactive ? 'grayscale' : ''}" onclick="window.open('${url}', '_blank')">
                        <div class="img-name">${img.nome_original}</div>
                        <div class="img-actions" style="margin-top: 5px; display: flex; justify-content: center; gap: 5px;">
                            ${(EH_ADMIN_DEV || notificationData.status_slug === 'rascunho') ? `
                                <button type="button" class="btn-floating btn-small ${isInactive ? 'green' : 'orange'}" onclick="alternarImagem(${img.id}, ${isInactive ? 0 : 1})" title="${isInactive ? 'Ativar/Desativar'}">
                                    <i class="material-icons">${isInactive ? 'check' : 'block'}</i>
                                </button>
                                <button type="button" class="btn-floating btn-small red" onclick="removerImagem(${img.id})" title="Excluir">
                                    <i class="material-icons">delete</i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `));
            });
        }

        async function alternarImagem(id, status) {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alternar_imagem_ocorrencia: true, id: id, status: status })
                });
                if (response.ok) {
                    M.toast({html: 'Status atualizado', classes: 'green'});
                    loadNotificationData();
                }
            } catch (e) { M.toast({html: 'Erro ao alternar', classes: 'red'}); }
        }

        async function removerImagem(id) {
            if (!confirm('Deseja remover esta imagem permanentemente?')) return;
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ deletar_imagem: true, id: id }) });
                if (response.ok) { $(`#img_${id}`).remove(); M.toast({html: 'Imagem removida', classes: 'green'}); }
            } catch (e) { M.toast({html: 'Erro ao remover', classes: 'red'}); }
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
                if (ocorrencias.length === 0) { container.innerHTML = '<p>Nenhuma encontrada.</p>'; return; }
                container.innerHTML = ocorrencias.map(o => `<div class="search-result-item" onclick="vincularOcorrencia(${o.id})"><strong>#${o.id}</strong> - ${o.titulo}</div>`).join('');
            } catch (error) { container.innerHTML = '<p style="color:red">Erro.</p>'; }
        }

        async function vincularOcorrencia(id) {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php?id=${id}`);
                ocorrenciaVinculadaData = await response.json();
                $('#ocorrencia_id').val(ocorrenciaVinculadaData.id);
                $('#ocorrencia_info').show();
                $('#ocorrencia_busca_section').hide();
                M.toast({html: 'Ocorrência vinculada!', classes: 'green'});
            } catch (e) { M.toast({html: 'Erro ao vincular', classes: 'red'}); }
        }
    </script>
</body>
</html>
