// js/funcs.js - Funções compartilhadas por todo o sistema

// --- Bloco de Variáveis Globais ---
let imageStore = [];
let currentPdfUrl = null;
let configData = {};
let imagensParaDeletar = [];

const API_BASE_URL_PYTHON = `${window.location.protocol}//${window.location.hostname}:5000`;
const API_BASE_URL_PHP = window.location.origin + '/api';

// --- Bloco de Funções Auxiliares de UI (do antigo helpers.js) ---

function configurarCampoBloco() {
    const blocoInput = document.getElementById('bloco');
    if (!blocoInput) return;
    blocoInput.addEventListener('input', function(e) {
        let valor = e.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
        e.target.value = valor.substring(0, 1);
    });
}

function vincularCamposUnidadeBloco() {
    const unidadeInput = document.getElementById('unidade');
    const blocoInput = document.getElementById('bloco');
    if (!unidadeInput || !blocoInput) return;
    unidadeInput.addEventListener('input', function(e) {
        const valorAtual = e.target.value;
        const matchLetra = valorAtual.match(/[a-zA-Z]/);
        const letra = matchLetra ? matchLetra[0].toUpperCase() : '';
        const matchNumeros = valorAtual.match(/\d/g);
        const numeros = matchNumeros ? matchNumeros.join('') : '';
        blocoInput.value = letra;
        e.target.value = numeros;
    });
}

// --- Bloco de Funções Comuns ---
function urlParaBase64(url) {
    return new Promise((resolve, reject) => {
        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result.split(',')[1]);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            })
            .catch(reject);
    });
}

async function fetchInitialData() {
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/config.php`);
        if (!response.ok) throw new Error('Falha ao carregar configurações do servidor.');
        
        configData = await response.json();
        
        const tipoSelect = document.getElementById('tipo_id');
        tipoSelect.innerHTML = '';
        configData.tipos.forEach(tipo => {
            tipoSelect.innerHTML += `<option value="${tipo.id}">${tipo.nome}</option>`;
        });

        const assuntoSelect = document.getElementById('assunto_id');
        assuntoSelect.innerHTML = '';
        configData.assuntos.forEach(assunto => {
            assuntoSelect.innerHTML += `<option value="${assunto.id}">${assunto.descricao}</option>`;
        });
    } catch (error) {
        showStatus(error.message, 'error');
        console.error("Fetch initial data error:", error);
    }
}

/**
 * VERSÃO CORRETA E DEFINITIVA DA getFormData
 */
async function getFormData(forPDF = false) {
    console.log("--- 2. Função 'getFormData' iniciada. ---");
    // ... (o início da função é o mesmo)
    const tipoSelect = document.getElementById('tipo_id');
    const assuntoSelect = document.getElementById('assunto_id');
    const selectedTipoOption = tipoSelect.options[tipoSelect.selectedIndex];
    const selectedAssuntoOption = assuntoSelect.options[assuntoSelect.selectedIndex];

    const dados = {
        numero: document.getElementById('numero').value,
        unidade: document.getElementById('unidade').value,
        bloco: document.getElementById('bloco').value,
        data_emissao: document.getElementById('data_emissao').value,
        fundamentacao_legal: document.getElementById('fundamentacao_legal').value,
        fatos: Array.from(document.querySelectorAll('#fatos-container input')).map(input => input.value).filter(Boolean)
    };

    if (forPDF) {
        dados.fotos_fatos = [];
        const novasImagensB64 = imageStore.map(img => img.b64);
        
        const imagensExistentes = document.querySelectorAll('.img-preview-item.existing-image img');
        console.log(`--- 3. Encontradas ${imagensExistentes.length} imagens existentes. Iniciando conversão para Base64... ---`);
        
        const promessasImagensExistentes = Array.from(imagensExistentes).map(img => urlParaBase64(img.src));
        
        const existentesImagensB64 = await Promise.all(promessasImagensExistentes);
        dados.fotos_fatos = existentesImagensB64.concat(novasImagensB64);
        
        // ... (o resto da lógica if forPDF é a mesma)
        dados.tipo_notificacao = selectedTipoOption ? selectedTipoOption.text : '';
        dados.tipo_penalidade = selectedTipoOption ? selectedTipoOption.text.toUpperCase() : '';
        dados.assunto = selectedAssuntoOption ? selectedAssuntoOption.text : '';
        dados.url_recurso = document.getElementById('url_recurso').value;
        if (selectedTipoOption && selectedTipoOption.text.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
    } else {
        // ... (lógica para PHP)
        dados.fotos_fatos = imageStore;
        dados.tipo_id = selectedTipoOption ? parseInt(selectedTipoOption.value) : null;
        dados.assunto_id = selectedAssuntoOption ? parseInt(assuntoSelect.value) : null;
    }
    
    return dados;
}

async function gerarPDF() {
    console.log("--- 1. Função 'gerarPDF' iniciada. ---");
    
    try {
        const dados = await getFormData(true);
        console.log("--- 4. 'getFormData' concluído com sucesso. Dados recebidos:", dados);

        if (!dados.numero || !dados.unidade) {
            showStatus('Preencha pelo menos Número e Unidade para gerar o preview.', 'error');
            console.log("--- X. ERRO: Campos obrigatórios não preenchidos. ---");
            return;
        }

        showStatus('Gerando preview do PDF...', 'loading');
        
        console.log(`--- 5. PREPARANDO CHAMADA PARA A API PYTHON com ${dados.fotos_fatos.length} imagem(ns). ---`);
        
        const response = await fetch(`${API_BASE_URL_PYTHON}/gerar_documento`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });

        console.log("--- 6. Resposta da API Python recebida. ---", response);

        if (response.ok) {
            // ... (resto da lógica de sucesso)
            const pdfBlob = await response.blob();
            const currentPdfUrl = URL.createObjectURL(pdfBlob);
            document.getElementById('pdfPlaceholder').style.display = 'none';
            const pdfViewer = document.getElementById('pdfViewer');
            pdfViewer.src = currentPdfUrl;
            pdfViewer.style.display = 'block';
            document.getElementById('btnDownload').style.display = 'block';
            showStatus('Preview gerado com sucesso!', 'success');
        } else {
            // ... (resto da lógica de erro)
            const errorData = await response.json();
            showStatus(`Erro ao gerar PDF: ${errorData.error}`, 'error');
        }

    } catch (error) {
        console.error("--- X. ERRO CRÍTICO DENTRO DE 'gerarPDF': ---", error);
        showStatus(`Ocorreu um erro de script: ${error.message}`, 'error');
    }
}

function addFato(valor = '') {
    const container = document.getElementById('fatos-container');
    const div = document.createElement('div');
    div.className = 'fato-item';
    div.innerHTML = `<input type="text" value="${valor}" placeholder="Descreva o fato..."><button type="button" class="remove-fato" onclick="removeFato(this)">×</button>`;
    container.appendChild(div);
}

function removeFato(button) {
    button.parentElement.remove();
}

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

function createImagePreview(imageDataUrl, fileName, container) {
    const item = document.createElement('div');
    item.className = 'img-preview-item';
    item.setAttribute('data-filename', fileName);
    item.innerHTML = `<img src="${imageDataUrl}" alt="Preview"><button type="button" class="remove-img" onclick="removeImage('${fileName}')">&times;</button>`;
    container.appendChild(item);
}

function removeImage(fileName) {
    imageStore = imageStore.filter(img => img.name !== fileName);
    const container = document.getElementById('preview-container');
    const itemToRemove = container.querySelector(`[data-filename="${fileName}"]`);
    if (itemToRemove) container.removeChild(itemToRemove);
}

function showStatus(message, type) {
    const statusEl = document.getElementById('status');
    statusEl.textContent = message;
    statusEl.className = `status ${type}`;
    statusEl.style.display = 'block';
    if (type !== 'loading') {
        setTimeout(() => { statusEl.style.display = 'none'; }, 5000);
    }
}

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


function vincularCamposUnidadeBloco() {
    const unidadeInput = document.getElementById('unidade');
    const blocoInput = document.getElementById('bloco');

    if (!unidadeInput || !blocoInput) return; // Não faz nada se os campos não existirem

    unidadeInput.addEventListener('input', function() {
        const valor = this.value.trim().toUpperCase();
        if (valor.length > 0) {
            // Pega o primeiro caractere
            const primeiroChar = valor.charAt(0);
            // Verifica se o primeiro caractere é uma letra (não é um número)
            if (isNaN(parseInt(primeiroChar))) {
                blocoInput.value = primeiroChar;
            } else {
                blocoInput.value = ''; // Limpa se começar com número
            }
        } else {
            blocoInput.value = ''; // Limpa se o campo estiver vazio
        }
    });
}

/**
 * Pega o JWT do localStorage, decodifica o payload e o retorna como um objeto.
 * Retorna null se não houver token ou se ele for inválido.
 */
function getJwtPayload() {
    const token = localStorage.getItem('accessToken');
    if (!token) {
        return null;
    }
    try {
        // O token é dividido em 3 partes. O payload é a segunda.
        const payloadBase64 = token.split('.')[1];
        const jsonPayload = atob(payloadBase64);
        return JSON.parse(jsonPayload);
    } catch (e) {
        console.error("Erro ao decodificar JWT:", e);
        return null;
    }
}

