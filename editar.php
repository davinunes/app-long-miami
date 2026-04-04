<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Notificação - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
                <a href="lista.php" class="back-link"><i class="material-icons">arrow_back</i> Voltar para Lista</a>
                <h1>Editar Notificação</h1>
            </div>

            <div class="content">
                <div class="form-section">
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
        let ocorrenciaVinculadaData = null;
        
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
        });

        document.addEventListener('DOMContentLoaded', async function() {
            addFato();
            await fetchInitialData();
            $('select').formSelect();
            configurarCampoBloco();
            vincularCamposUnidadeBloco();
            inicializarBuscaRegimento();
            
            $('#tipo_id').on('change', function() {
                toggleMultaField();
            });
            
            $('#ocorrencia_busca').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    buscarOcorrencias();
                }
            });
            
            if (NOTIFICACAO_ID) {
                await inicializarFormularioEdicao();
            } else {
                $('#btnSalvar').on('click', salvarNotificacao);
            }
        });

        async function buscarOcorrencias() {
            const busca = document.getElementById('ocorrencia_busca').value.trim();
            const container = document.getElementById('ocorrencia_busca_resultados');
            
            if (busca.length < 2 && busca.length > 0) {
                container.innerHTML = '<p style="color: #666;">Digite pelo menos 2 caracteres para buscar.</p>';
                return;
            }
            
            container.innerHTML = '<p style="color: #666;">Buscando...</p>';
            
            try {
                const url = `${API_BASE_URL_PHP}/notificacoes.php?buscar_ocorrencias=${encodeURIComponent(busca)}`;
                const response = await fetch(url);
                
                if (!response.ok) throw new Error('Erro ao buscar');
                
                const ocorrencias = await response.json();
                
                if (ocorrencias.length === 0) {
                    container.innerHTML = '<p style="color: #666;">Nenhuma ocorrência homologada disponível encontrada.</p>';
                    return;
                }
                
                container.innerHTML = '<div style="display: flex; flex-direction: column; gap: 8px;">' + 
                    ocorrencias.map(o => `
                        <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; cursor: pointer;" onclick="vincularOcorrencia(${o.id})">
                            <strong>#${o.id}</strong> - ${o.titulo}
                            <br><small style="color: #666;">${o.unidades || 'Sem unidades'} | ${formatDate(o.data_fato)}</small>
                        </div>
                    `).join('') + '</div>';
                
            } catch (error) {
                container.innerHTML = '<p style="color: red;">Erro ao buscar ocorrências.</p>';
                console.error(error);
            }
        }

        async function vincularOcorrencia(id) {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php?id=${id}`);
                if (!response.ok) throw new Error('Erro ao carregar ocorrência');
                
                ocorrenciaVinculadaData = await response.json();
                
                document.getElementById('ocorrencia_id').value = ocorrenciaVinculadaData.id;
                document.getElementById('ocorrencia_titulo').textContent = `Ocorrência #${ocorrenciaVinculadaData.id}: ${ocorrenciaVinculadaData.titulo}`;
                document.getElementById('ver_ocorrencia_link').href = `ocorrencia_detalhe.php?id=${ocorrenciaVinculadaData.id}`;
                document.getElementById('ocorrencia_info').style.display = 'block';
                document.getElementById('ocorrencia_busca_section').style.display = 'none';
                document.getElementById('ocorrencia_busca_resultados').innerHTML = '';
                
                const unidade = ocorrenciaVinculadaData.unidades && ocorrenciaVinculadaData.unidades.length > 0 
                    ? ocorrenciaVinculadaData.unidades[0] 
                    : null;
                if (unidade) {
                    document.getElementById('unidade').value = unidade.unidade_numero || '';
                    document.getElementById('bloco').value = unidade.unidade_bloco || '';
                }
                
                if (ocorrenciaVinculadaData.descricao_fato) {
                    document.getElementById('fatos-container').innerHTML = '';
                    addFato(ocorrenciaVinculadaData.descricao_fato);
                }
                
                renderEvidenciasOcorrencia(ocorrenciaVinculadaData.anexos || []);
                
                M.toast({html: 'Ocorrência vinculada! Use "Sincronizar Evidências" para adicionar as fotos.', classes: 'green'});
                
            } catch (error) {
                M.toast({html: 'Erro ao vincular ocorrência', classes: 'red'});
                console.error(error);
            }
        }

        function renderEvidenciasOcorrencia(anexos) {
            const container = document.getElementById('evidencias-ocorrencia');
            if (!container) return;
            
            const section = document.getElementById('evidencias_ocorrencia_section');
            
            const imagens = anexos.filter(a => a.tipo === 'imagem');
            
            if (imagens.length === 0) {
                if (section) section.style.display = 'none';
                return;
            }
            
            if (section) section.style.display = 'block';
            
            container.innerHTML = imagens.map(img => `
                <div class="img-preview-item">
                    <img src="${img.url}" alt="${img.nome_original}" style="max-width: 150px; max-height: 150px; cursor: pointer;" onclick="window.open('${img.url}', '_blank')">
                    <small>${img.nome_original}</small>
                </div>
            `).join('');
        }

        let sincronizando = false;

        async function sincronizarEvidencias() {
            const ocorrenciaId = document.getElementById('ocorrencia_id').value;
            const notificacaoId = NOTIFICACAO_ID;
            
            if (!ocorrenciaId || !notificacaoId) {
                M.toast({html: 'Ocorrencia ou notificação não encontrada.', classes: 'red'});
                return;
            }
            
            if (sincronizando) {
                M.toast({html: 'Sincronização já em andamento.', classes: 'orange'});
                return;
            }
            
            sincronizando = true;
            const btn = document.getElementById('btn_sincronizar_evidencias');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="material-icons" style="font-size: 16px; vertical-align: middle;">sync</i> Sincronizando...';
            }
            
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'sincronizar_evidencias',
                        notificacao_id: parseInt(notificacaoId),
                        ocorrencia_id: parseInt(ocorrenciaId)
                    })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    M.toast({html: result.message || `Sincronizado!`, classes: 'green'});
                    window.location.reload();
                } else {
                    M.toast({html: 'Erro: ' + (result.message || 'Falha na sincronização'), classes: 'red'});
                }
            } catch (error) {
                M.toast({html: 'Erro de conexão ao sincronizar.', classes: 'red'});
                console.error(error);
            } finally {
                sincronizando = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="material-icons" style="font-size: 16px; vertical-align: middle;">sync</i> Sincronizar Evidências';
                }
            }
        }
    </script>
</body>
</html>
