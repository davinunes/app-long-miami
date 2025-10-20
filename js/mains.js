// A constante API_BASE_URL agora aponta para a pasta da sua API
const API_BASE_URL_PYTHON = `${window.location.protocol}//${window.location.hostname}:5000`;
const API_BASE_URL_PHP = '/api'; // Caminho relativo para a API PHP

// Variáveis globais para armazenar dados do formulário
let imageStore = [];
let currentPdfUrl = null;
let configData = {}; // Armazenará tipos e assuntos vindos do PHP/API

/**
 * Função principal que é executada quando o DOM está pronto.
 * Preenche data e busca configurações iniciais.
 */
document.addEventListener('DOMContentLoaded', async function() {
    document.getElementById('data_emissao').value = new Date().toISOString().split('T')[0];
    addFato();
    await fetchInitialData(); // AGORA USA A FUNÇÃO REAL
});

/**
 * (Exemplo) Popula os selects com dados mocados.
 */
function populateSelectsWithMockData() {
    configData.tipos = [
        { id: 1, nome: 'Advertência' },
        { id: 2, nome: 'Multa' }
    ];
    configData.assuntos = [
        { id: 1, descricao: 'Uso indevido de áreas comuns' },
        { id: 2, descricao: 'Perturbação do sossego' },
        { id: 3, descricao: 'Manutenção de unidade' },
    ];

    const tipoSelect = document.getElementById('tipo_id');
    configData.tipos.forEach(tipo => {
        const option = document.createElement('option');
        option.value = tipo.id;
        option.textContent = tipo.nome;
        tipoSelect.appendChild(option);
    });

    const assuntoSelect = document.getElementById('assunto_id');
    configData.assuntos.forEach(assunto => {
        const option = document.createElement('option');
        option.value = assunto.id;
        option.textContent = assunto.descricao;
        assuntoSelect.appendChild(option);
    });

    toggleMultaField(); // Chama para ajustar a visibilidade inicial
}


/**
 * (Futuro) Busca dados iniciais (tipos, assuntos) da API PHP.
 */
/**
 * Busca dados iniciais (tipos, assuntos) da API PHP.
 */
async function fetchInitialData() {
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/config.php`);
        if (!response.ok) throw new Error('Falha ao carregar configurações do servidor.');
        
        configData = await response.json();
        
        const tipoSelect = document.getElementById('tipo_id');
        configData.tipos.forEach(tipo => {
            const option = document.createElement('option');
            option.value = tipo.id;
            option.textContent = tipo.nome;
            tipoSelect.appendChild(option);
        });

        const assuntoSelect = document.getElementById('assunto_id');
        configData.assuntos.forEach(assunto => {
            const option = document.createElement('option');
            option.value = assunto.id;
            option.textContent = assunto.descricao;
            assuntoSelect.appendChild(option);
        });

        toggleMultaField();
    } catch (error) {
        showStatus(error.message, 'error');
        console.error("Fetch initial data error:", error);
    }
}


/**
 * Mostra ou esconde o campo "Valor da Multa" baseado no tipo de notificação.
 */
function toggleMultaField() {
    const tipoSelect = document.getElementById('tipo_id');
    const selectedOption = tipoSelect.options[tipoSelect.selectedIndex];
    const valorMultaGroup = document.getElementById('valor_multa_group');
    
    if (selectedOption && selectedOption.text.toLowerCase().includes('multa')) {
        valorMultaGroup.classList.remove('hidden');
    } else {
        valorMultaGroup.classList.add('hidden');
        document.getElementById('valor_multa').value = '';
    }
}

/**
 * Adiciona um novo campo para descrever um fato.
 */
function addFato() {
    const container = document.getElementById('fatos-container');
    const div = document.createElement('div');
    div.className = 'fato-item';
    div.innerHTML = `
        <input type="text" placeholder="Descreva o fato...">
        <button type="button" class="remove-fato" onclick="removeFato(this)">×</button>
    `;
    container.appendChild(div);
}

/**
 * Remove um campo de fato.
 * @param {HTMLButtonElement} button - O botão de remover que foi clicado.
 */
function removeFato(button) {
    button.parentElement.remove();
}

/**
 * Manipula os arquivos de imagem selecionados, gerando previews e armazenando em Base64.
 * @param {HTMLInputElement} input - O input de arquivo.
 * @param {string} previewContainerId - O ID do container onde os previews serão exibidos.
 */
function handleFiles(input, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    previewContainer.innerHTML = '';
    imageStore = [];
    const files = input.files;

    for (const file of files) {
        if (!file.type.startsWith('image/')) continue;
        const reader = new FileReader();
        reader.onload = function(e) {
            const base64String = e.target.result.split(',')[1];
            const fileData = { name: file.name, b64: base64String };
            imageStore.push(fileData);
            createImagePreview(e.target.result, file.name, previewContainer);
        }
        reader.readAsDataURL(file);
    }
}

/**
 * Cria o preview visual de uma imagem selecionada.
 * @param {string} imageDataUrl - A URL de dados da imagem (gerada pelo FileReader).
 * @param {string} fileName - O nome do arquivo.
 * @param {HTMLElement} container - O elemento container dos previews.
 */
function createImagePreview(imageDataUrl, fileName, container) {
    const item = document.createElement('div');
    item.className = 'img-preview-item';
    item.setAttribute('data-filename', fileName);
    item.innerHTML = `
        <img src="${imageDataUrl}" alt="Preview">
        <button type="button" class="remove-img" onclick="removeImage('${fileName}')">&times;</button>
    `;
    container.appendChild(item);
}

/**
 * Remove uma imagem da seleção e do preview.
 * @param {string} fileName - O nome do arquivo da imagem a ser removida.
 */
function removeImage(fileName) {
    imageStore = imageStore.filter(img => img.name !== fileName);
    const container = document.getElementById('preview-container');
    const itemToRemove = container.querySelector(`[data-filename="${fileName}"]`);
    if (itemToRemove) container.removeChild(itemToRemove);
}

/**
 * Coleta todos os dados do formulário e os formata em um objeto.
 * @param {boolean} forPDF - Se verdadeiro, formata dados para a API Python de PDF.
 * @returns {object} - O objeto com os dados do formulário.
 */
function getFormData(forPDF = false) {
    const tipoSelect = document.getElementById('tipo_id');
    const selectedTipoOption = tipoSelect.options[tipoSelect.selectedIndex];

    const assuntoSelect = document.getElementById('assunto_id');
    const selectedAssuntoOption = assuntoSelect.options[assuntoSelect.selectedIndex];

    const dados = {
        numero: document.getElementById('numero').value,
        unidade: document.getElementById('unidade').value,
        bloco: document.getElementById('bloco').value,
        data_emissao: document.getElementById('data_emissao').value,
        fundamentacao_legal: document.getElementById('fundamentacao_legal').value,
        texto_descritivo: document.getElementById('texto_descritivo').value,
        fatos: Array.from(document.querySelectorAll('#fatos-container input')).map(input => input.value).filter(Boolean),
        // Agora o 'fotos_fatos' envia um objeto com nome e base64
        fotos_fatos: imageStore
    };

    if (forPDF) {
        // A API de PDF espera os nomes/textos, não os IDs
        dados.tipo_notificacao = selectedTipoOption.text;
        dados.tipo_penalidade = selectedTipoOption.text.toUpperCase();
        dados.assunto = selectedAssuntoOption.text;
        dados.url_recurso = document.getElementById('url_recurso').value;
        if (selectedTipoOption.text.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
        // A API do PDF espera um array de strings base64, não o objeto completo
        dados.fotos_fatos = imageStore.map(img => img.b64);

    } else {
        // A API de salvamento (PHP) espera os IDs e outros dados
        dados.tipo_id = parseInt(selectedTipoOption.value);
        dados.assunto_id = parseInt(selectedAssuntoOption.value);
        dados.url_recurso = document.getElementById('url_recurso').value;
        dados.cidade_emissao = "Taguatinga/DF"; // Exemplo, pode ser um campo
        dados.prazo_recurso = 5; // Exemplo
         if (selectedTipoOption.text.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
    }
    
    return dados;
}


/**
 * Envia os dados para a API PHP para salvar no banco de dados.
 */
async function salvarNotificacao() {
    const dados = getFormData(false); // Formata para salvar no banco (com IDs)

    if (!dados.numero || !dados.unidade || !dados.assunto_id) {
        showStatus('Preencha os campos obrigatórios: Número, Unidade e Assunto.', 'error');
        return;
    }
    showStatus('Salvando notificação...', 'loading');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        if (response.ok) {
            showStatus(`Notificação salva com sucesso! ID: ${result.id}`, 'success');
        } else {
            showStatus(`Erro ao salvar: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão com a API PHP: ${error.message}`, 'error');
    }
}


/**
 * Envia os dados para a API Python para gerar um preview do PDF.
 */
async function gerarPDF() {
    const dados = getFormData(true);
    if (!dados.numero || !dados.unidade) {
        showStatus('Preencha pelo menos Número e Unidade para gerar o preview.', 'error');
        return;
    }
    showStatus('Gerando preview do PDF...', 'loading');
    try {
        const response = await fetch(`${API_BASE_URL_PYTHON}/gerar_documento`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        if (response.ok) {
            const pdfBlob = await response.blob();
            currentPdfUrl = URL.createObjectURL(pdfBlob);
            document.getElementById('pdfPlaceholder').style.display = 'none';
            const pdfViewer = document.getElementById('pdfViewer');
            pdfViewer.src = currentPdfUrl;
            pdfViewer.style.display = 'block';
            document.getElementById('btnDownload').style.display = 'block';
            showStatus('Preview gerado com sucesso!', 'success');
        } else {
            const errorData = await response.json();
            showStatus(`Erro ao gerar PDF: ${errorData.error}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão com a API Python: ${error.message}`, 'error');
    }
}


/**
 * Permite baixar o PDF atualmente em preview.
 */
function baixarPDF() {
    if (currentPdfUrl) {
        const link = document.createElement('a');
        link.href = currentPdfUrl;
        link.download = `notificacao_${document.getElementById('numero').value.replace('/', '-') || 'preview'}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

/**
 * Exibe uma mensagem de status para o usuário.
 * @param {string} message - A mensagem a ser exibida.
 * @param {string} type - O tipo de status ('success', 'error', 'loading').
 */
function showStatus(message, type) {
    const statusEl = document.getElementById('status');
    statusEl.textContent = message;
    statusEl.className = `status ${type}`;
    statusEl.style.display = 'block';
    
    if (type !== 'loading') {
        setTimeout(() => { statusEl.style.display = 'none'; }, 5000);
    }
}