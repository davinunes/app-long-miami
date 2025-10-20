<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Notificação</title>
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Criar Nova Notificação</h1>
            <p>Preencha os dados para gerar e salvar uma nova notificação</p>
        </div>

        <div class="content">
            <div class="form-section">
                <form id="documentForm" onsubmit="return false;">
                    <?php 
                        // Inclui o formulário reutilizável
                        include '_form.php'; 
                    ?>
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

    <script>
        // Aponta para o IP do host onde o container Python está exposto
        const API_BASE_URL_PYTHON = 'http://172.24.100.30:5000'; 
        
        // Caminho relativo para a API PHP, que está no mesmo serviço
        const API_BASE_URL_PHP = '/api'; 
    </script>

    <script src="js/helpers.js"></script>
    <script src="js/funcs.js"></script> 
	<script src="js/main.js"></script>
    
</body>
</html>