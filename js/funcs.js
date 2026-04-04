// js/funcs.js - Funções compartilhadas (sem JWT)
// As APIs agora usam sessões PHP diretamente

const API_BASE_URL_PHP = window.location.origin + '/api';

let imageStore = [];
let currentPdfUrl = null;

function showStatus(message, type) {
    const statusEl = document.getElementById('status');
    if (!statusEl) return;
    statusEl.textContent = message;
    statusEl.className = `status ${type}`;
    statusEl.style.display = 'block';
    if (type !== 'loading') {
        setTimeout(() => { statusEl.style.display = 'none'; }, 5000);
    }
}

function addFato(valor = '') {
    const container = document.getElementById('fatos-container');
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'fato-item';
    div.innerHTML = `<textarea placeholder="Descreva o fato...">${valor}</textarea>
                     <button type="button" class="remove-fato" onclick="removeFato(this)">×</button>`;
    container.appendChild(div);
}

function removeFato(button) {
    button.parentElement.remove();
}

function toggleMultaField() {
    const tipoSelect = document.getElementById('tipo_id');
    if (!tipoSelect) return;
    const selectedOption = tipoSelect.options[tipoSelect.selectedIndex];
    const valorMultaGroup = document.getElementById('valor_multa_group');
    if (selectedOption && selectedOption.text.toLowerCase().includes('multa')) {
        valorMultaGroup.classList.remove('hidden');
    } else {
        valorMultaGroup.classList.add('hidden');
        const multaInput = document.getElementById('valor_multa');
        if (multaInput) multaInput.value = '';
    }
}

function handleFiles(input, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    if (!previewContainer) return;
    document.querySelectorAll('.new-image-preview').forEach(el => el.remove());
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
        };
        reader.readAsDataURL(file);
    }
}

function createImagePreview(imageDataUrl, fileName, container) {
    const item = document.createElement('div');
    item.className = 'img-preview-item new-image-preview';
    item.setAttribute('data-filename', fileName);
    item.innerHTML = `
        <img src="${imageDataUrl}" alt="Preview">
        <button type="button" class="remove-img" onclick="removeImage('${fileName}')">&times;</button>
    `;
    container.appendChild(item);
}

function removeImage(fileName) {
    imageStore = imageStore.filter(img => img.name !== fileName);
    const container = document.getElementById('preview-container');
    const itemToRemove = container.querySelector(`[data-filename="${fileName}"]`);
    if (itemToRemove) container.removeChild(itemToRemove);
}

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
        if (!response.ok) throw new Error('Falha ao carregar configurações.');
        
        const configData = await response.json();
        
        const tipoSelect = document.getElementById('tipo_id');
        if (tipoSelect) {
            tipoSelect.innerHTML = '';
            configData.tipos.forEach(tipo => {
                tipoSelect.innerHTML += `<option value="${tipo.id}">${tipo.nome}</option>`;
            });
        }

        const assuntoSelect = document.getElementById('assunto_id');
        if (assuntoSelect) {
            assuntoSelect.innerHTML = '';
            configData.assuntos.forEach(assunto => {
                assuntoSelect.innerHTML += `<option value="${assunto.id}">${assunto.descricao}</option>`;
            });
        }
        
        return configData;
    } catch (error) {
        showStatus(error.message, 'error');
        console.error("Fetch initial data error:", error);
        return null;
    }
}

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
    
    unidadeInput.addEventListener('input', function() {
        const valor = this.value.trim().toUpperCase();
        if (valor.length > 0) {
            const primeiroChar = valor.charAt(0);
            if (isNaN(parseInt(primeiroChar))) {
                blocoInput.value = primeiroChar;
            } else {
                blocoInput.value = '';
            }
        } else {
            blocoInput.value = '';
        }
    });
}

function baixarPDF() {
    if (currentPdfUrl) {
        const link = document.createElement('a');
        link.href = currentPdfUrl;
        link.download = `notificacao_${document.getElementById('numero')?.value.replace('/', '-') || 'preview'}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function carregarListaNotificacoes() {
    const tbody = document.getElementById('notifications-table-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>';

    fetch(`${API_BASE_URL_PHP}/notificacoes.php`)
    .then(response => response.json())
    .then(data => {
        tbody.innerHTML = '';
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Nenhuma notificação encontrada.</td></tr>';
            return;
        }

        data.forEach(n => {
            const dataEmissao = new Date(n.data_emissao + 'T00:00:00');
            const dataFormatada = dataEmissao.toLocaleDateString('pt-BR');

            const row = `
                <tr>
                    <td>${n.numero}/${n.ano}</td>
                    <td>${n.bloco ? n.bloco : ''}${n.unidade}</td>
                    <td>${n.assunto}</td>
                    <td>${n.tipo}</td>
                    <td>${n.status}</td>
                    <td>${dataFormatada}</td>
                    <td>
                        <a href="editar.php?id=${n.id}" class="action-btn">Detalhes / Editar</a>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(error => {
        console.error('Erro ao buscar notificações:', error);
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: red;">Erro: ${error.message}</td></tr>`;
    });
}

function fazerLogout() {
    window.location.href = 'logout.php';
}

async function gerarPDF() {
    showStatus('Gerando preview do PDF...', 'loading');
    
    try {
        const dados = getFormData(true);
        if (!dados.numero || !dados.unidade) {
            showStatus('Preencha pelo menos Número e Unidade para gerar o preview.', 'error');
            return;
        }
        
        const response = await fetch(`${API_BASE_URL_PHP}/gerar_pdf.php`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dados)
        });
        
        if (response.ok) {
            const pdfBlob = await response.blob();
            currentPdfUrl = URL.createObjectURL(pdfBlob);
            document.getElementById('pdfPlaceholder').style.display = 'none';
            const pdfViewer = document.getElementById('pdfViewer');
            if (pdfViewer) {
                pdfViewer.src = currentPdfUrl;
                pdfViewer.style.display = 'block';
            }
            const btnDownload = document.getElementById('btnDownload');
            if (btnDownload) btnDownload.style.display = 'block';
            showStatus('Preview gerado com sucesso!', 'success');
        } else {
            const errorData = await response.json().catch(() => ({}));
            showStatus(`Erro ao gerar PDF: ${errorData.error || errorData.message || 'Recurso em desenvolvimento'}`, 'error');
        }
    } catch (error) {
        showStatus(`Preview PDF em desenvolvimento. Salve a notificação primeiro.`, 'warning');
    }
}

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
        texto_descritivo: document.getElementById('texto_descritivo') ? document.getElementById('texto_descritivo').value : '',
        fatos: Array.from(document.querySelectorAll('#fatos-container textarea')).map(input => input.value).filter(Boolean),
        fotos_fatos: imageStore
    };

    if (forPDF) {
        dados.tipo_notificacao = selectedTipoOption.text;
        dados.tipo_penalidade = selectedTipoOption.text.toUpperCase();
        dados.assunto = selectedAssuntoOption.text;
        dados.url_recurso = document.getElementById('url_recurso').value;
        if (selectedTipoOption.text.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
        dados.fotos_fatos = imageStore.map(img => img.b64);
    } else {
        dados.tipo_id = parseInt(selectedTipoOption.value);
        dados.assunto_id = parseInt(selectedAssuntoOption.value);
        dados.url_recurso = document.getElementById('url_recurso').value;
        dados.cidade_emissao = "Taguatinga/DF";
        dados.prazo_recurso = 5;
        if (selectedTipoOption.text.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
    }
    
    return dados;
}
