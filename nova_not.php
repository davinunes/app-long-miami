<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Notificação - App Long Miami</title>
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
                <h1>Criar Nova Notificação</h1>
                <p>Preencha os dados para gerar e salvar uma nova notificação</p>
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
        const OCORRENCIA_ID = <?php echo isset($_GET['ocorrencia_id']) ? (int)$_GET['ocorrencia_id'] : 'null'; ?>;
        let ocorrenciaData = null;
        
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
        });

        document.addEventListener('DOMContentLoaded', async function() {
            document.getElementById('data_emissao').value = new Date().toISOString().split('T')[0];
            addFato();
            await fetchInitialData();
            $('select').formSelect();
            configurarCampoBloco();
            vincularCamposUnidadeBloco();
            inicializarBuscaRegimento();
            await fetchProximoNumero();
            
            if (OCORRENCIA_ID) {
                await carregarOcorrenciaVinculada(OCORRENCIA_ID);
            }
            
            $('#btnSalvar').on('click', salvarNotificacao);
        });

        async function carregarOcorrenciaVinculada(id) {
            try {
                const response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php?id=' + id);
                if (!response.ok) throw new Error('Erro ao carregar ocorrência');
                
                ocorrenciaData = await response.json();
                
                document.getElementById('ocorrencia_id').value = ocorrenciaData.id;
                document.getElementById('ocorrencia_titulo').textContent = 'Ocorrência #' + ocorrenciaData.id + ': ' + ocorrenciaData.titulo;
                document.getElementById('ver_ocorrencia_link').href = 'ocorrencia_detalhe.php?id=' + ocorrenciaData.id;
                document.getElementById('ocorrencia_info').style.display = 'block';
                
                const unidade = ocorrenciaData.unidades && ocorrenciaData.unidades.length > 0 
                    ? ocorrenciaData.unidades[0] 
                    : null;
                if (unidade) {
                    document.getElementById('unidade').value = unidade.unidade_numero || '';
                    document.getElementById('bloco').value = unidade.unidade_bloco || '';
                }
                
                if (ocorrenciaData.descricao_fato) {
                    addFato(ocorrenciaData.descricao_fato);
                }
                
                renderEvidenciasOcorrencia(ocorrenciaData.anexos || []);
                
            } catch (error) {
                console.error('Erro ao carregar ocorrência:', error);
                M.toast({html: 'Erro ao carregar dados da ocorrência', classes: 'red'});
            }
        }

        function renderEvidenciasOcorrencia(anexos) {
            const container = document.getElementById('evidencias-ocorrencia');
            if (!container) return;
            
            const imagens = anexos.filter(a => a.tipo === 'imagem');
            if (imagens.length === 0) {
                container.innerHTML = '<p style="color: #999;">Nenhuma evidência fotográfica disponível.</p>';
                return;
            }
            
            container.innerHTML = imagens.map(img => `
                <div class="img-preview-item">
                    <img src="${img.url}" alt="${img.nome_original}" style="max-width: 150px; max-height: 150px; cursor: pointer;" onclick="window.open('${img.url}', '_blank')">
                    <small>${img.nome_original}</small>
                </div>
            `).join('');
        }
    </script>
</body>
</html>
