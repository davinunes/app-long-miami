<input type="hidden" id="notificacao_id" value="">
<input type="hidden" id="ocorrencia_id" value="">

<div id="status" class="status" style="display: none;"></div>

<div id="ocorrencia_info" class="form-group" style="display: none; background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196F3;">
    <label style="color: #1976D2; font-weight: bold;">Ocorrência Vinculada:</label>
    <p id="ocorrencia_titulo" style="margin: 5px 0; color: #333;"></p>
    <div style="display: flex; gap: 10px; margin-top: 10px;">
        <a href="#" id="ver_ocorrencia_link" class="btn-small" target="_blank">Ver Ocorrência</a>
        <button type="button" id="btn_sincronizar_evidencias" class="btn-small blue" onclick="sincronizarEvidencias()">
            <i class="material-icons" style="font-size: 16px; vertical-align: middle;">sync</i> Sincronizar Evidências
        </button>
    </div>
</div>

<div id="ocorrencia_busca_section" class="form-group" style="display: none; background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #FF9800;">
    <label style="color: #E65100; font-weight: bold;">Vincular Ocorrência:</label>
    <p style="color: #666; font-size: 12px; margin: 5px 0;">Busque uma ocorrência homologada para vincular.</p>
    <div style="display: flex; gap: 10px;">
        <input type="text" id="ocorrencia_busca" placeholder="Buscar por título ou descrição..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        <button type="button" class="btn orange" onclick="buscarOcorrencias()">Buscar</button>
    </div>
    <div id="ocorrencia_busca_resultados" style="margin-top: 10px; max-height: 200px; overflow-y: auto;"></div>
</div>

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

<div class="form-group" id="evidencias_ocorrencia_section" style="display: none;">
    <label>Evidências da Ocorrência:</label>
    <div class="evidencias-grid" id="evidencias-ocorrencia" style="display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0;"></div>
    <p style="color: #666; font-size: 12px; margin-top: 5px;"><i class="material-icons" style="font-size: 14px; vertical-align: middle;">info</i> As evidências da ocorrência estão disponíveis acima. Você pode adicionar mais fotos manualmente abaixo.</p>
</div>

<div class="form-group">
    <label for="fotos_fatos">Adicionar Evidências Fotográficas:</label>
    <input type="file" id="fotos_fatos" accept="image/*" multiple onchange="handleFiles(this, 'preview-container')">
    <div class="image-preview-container" id="preview-container"></div>
</div>

<div class="form-group">
    <label>Fundamentação Legal:</label>
    
    <div class="regimento-busca">
        <input type="text" id="regimento-search" placeholder="Buscar artigo (número ou palavra-chave)...">
        <div class="regimento-results" id="regimento-results"></div>
    </div>
    
    <div class="selected-articles" id="selected-articles" style="display: none;">
        <div class="selected-articles-title">Artigos selecionados:</div>
        <div id="selected-articles-list"></div>
        <button type="button" class="add-to-textarea-btn" onclick="adicionarArtigosATextarea()">
            + Adicionar à Fundamentação
        </button>
    </div>
    
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