
    <div class="header">
        <h1>Editar Notificação</h1>
    </div>
    <div class="content">
        <div class="form-section">
            <a href="lista.php" class="back-link ajax-link">&larr; Voltar para a Lista</a>

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
