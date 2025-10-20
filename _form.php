<input type="hidden" id="notificacao_id" value="">

<div id="status" class="status" style="display: none;"></div>

<div class="form-group">
    <label for="tipo_id">Tipo de Notificação:</label>
    <select id="tipo_id" onchange="toggleMultaField()"></select>
</div>

<div class="form-group">
    <label for="numero">Número da Notificação:</label>
    <input type="text" id="numero" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
</div>

<div class="form-group">
    <label for="unidade">Unidade:</label>
    <input type="text" id="unidade" placeholder="Ex: A101 ou 101" required>
</div>

<div class="form-group">
    <label for="bloco">Bloco (automático):</label>
    <input type="text" id="bloco" placeholder="Ex: A"  style="background-color: #e9ecef;">
</div>

<div class="form-group">
    <label for="assunto_id">Assunto:</label>
    <select id="assunto_id" required></select>
</div>

<div class="form-group">
    <label>Fatos:</label>
    <div id="fatos-container"></div>
    <button type="button" class="add-fato" onclick="addFato()">+ Adicionar Fato</button>
</div>

<div class="form-group">
    <label for="fotos_fatos">Evidências Fotográficas:</label>
    <input type="file" id="fotos_fatos" accept="image/*" multiple onchange="handleFiles(this, 'preview-container')">
    <div class="image-preview-container" id="preview-container"></div>
</div>

<div class="form-group">
    <label for="fundamentacao_legal">Fundamentação Legal:</label>
    <textarea id="fundamentacao_legal" rows="4" placeholder="Artigos do regimento, leis, etc..."></textarea>
</div>

<div class="form-group hidden" id="valor_multa_group">
    <label for="valor_multa">Valor da Multa:</label>
    <input type="text" id="valor_multa">
</div>

<div class="form-group">
    <label for="data_emissao">Data de Emissão:</label>
    <input type="date" id="data_emissao" required>
</div>

<div class="form-group">
    <label for="url_recurso">URL para Recurso:</label>
    <input type="url" id="url_recurso" placeholder="https://seu-condominio.com/recursos">
</div>

<div class="button-group">
    <button type="button" class="btn-primary" id="btnSalvar">
        💾 Salvar
    </button>
    <button type="button" class="btn-secondary" onclick="gerarPDF()">
        📄 Gerar Preview
    </button>
</div>