<?php
// Pega o ID da URL, garantindo que seja um inteiro. Medida de segurança.
$notificacao_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se não houver um ID válido, não há o que editar. Redireciona de volta para a lista.
if ($notificacao_id === 0) {
    header('Location: lista.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Notificação #<?php echo htmlspecialchars($notificacao_id); ?></title>
    
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Pequeno ajuste para o link de 'voltar' */
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
		
		/* NOVO: Estilo para imagens marcadas para deleção */
    .img-preview-item.marcada-para-delecao img {
        opacity: 0.3;
        border: 2px solid #e74c3c;
    }
    .img-preview-item .remove-btn-existing {
        position: absolute; top: -5px; right: 5px; background: #e74c3c;
        color: white; border: none; border-radius: 50%; width: 20px; height: 20px;
        cursor: pointer; font-weight: bold; line-height: 20px; text-align: center;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Editar Notificação #<?php echo htmlspecialchars($notificacao_id); ?></h1>
        </div>
        <div class="content">
            <div class="form-section">
                <a href="lista.php" class="back-link">&larr; Voltar para a Lista</a>

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

    <script>
        // Configuração das URLs das APIs
        const API_BASE_URL_PYTHON = 'http://172.24.100.30:5000';
        const API_BASE_URL_PHP = '/api';
        // Passa o ID da notificação do PHP para o JavaScript
        const NOTIFICACAO_ID = <?php echo $notificacao_id; ?>;
    </script>
    
    <script src="js/helpers.js"></script>
    <script src="js/funcs.js"></script> 
	<script src="js/editar.js"></script> 
</body>
</html>