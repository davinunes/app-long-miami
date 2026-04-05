// js/funcs.js - Funções compartilhadas (sem JWT)
// As APIs agora usam sessões PHP diretamente
// API_BASE_URL_PHP é definido pelo menu.php

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

function autoExpand(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
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
    const tipoCombo = document.getElementById('tipo_combo');
    if (!tipoCombo) return;
    const valorMultaGroup = document.getElementById('valor_multa_group');
    if (tipoCombo.value.toLowerCase().includes('multa')) {
        valorMultaGroup.classList.remove('hidden');
    } else {
        valorMultaGroup.classList.add('hidden');
        const multaInput = document.getElementById('valor_multa');
        if (multaInput) multaInput.value = '';
    }
}

async function resizeImage(file, maxPixels = 600, quality = 0.8) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
            const width = img.width;
            const height = img.height;
            
            // Se já é menor que o máximo, retorna original
            if (width <= maxPixels && height <= maxPixels) {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
                return;
            }
            
            // Calcular novas dimensões mantendo proporção
            let newWidth, newHeight;
            if (width > height) {
                newWidth = maxPixels;
                newHeight = Math.round((height / width) * maxPixels);
            } else {
                newHeight = maxPixels;
                newWidth = Math.round((width / height) * maxPixels);
            }
            
            // Criar canvas e redimensionar
            const canvas = document.createElement('canvas');
            canvas.width = newWidth;
            canvas.height = newHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, newWidth, newHeight);
            
            // Exportar como JPEG (mais leve) ou manter PNG se for o caso
            const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
            const dataUrl = canvas.toDataURL(mimeType, quality);
            
            console.log(`Imagem redimensionada: ${width}x${height} -> ${newWidth}x${newHeight} (${Math.round(dataUrl.length / 1024)}KB)`);
            resolve(dataUrl);
        };
        img.onerror = reject;
        img.src = URL.createObjectURL(file);
    });
}

async function handleFiles(input, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    if (!previewContainer) return;
    document.querySelectorAll('.new-image-preview').forEach(el => el.remove());
    imageStore = [];
    const files = input.files;
    
    for (const file of files) {
        if (!file.type.startsWith('image/')) continue;
        
        try {
            const resizedDataUrl = await resizeImage(file);
            const base64String = resizedDataUrl.split(',')[1];
            const fileData = { name: file.name, b64: base64String };
            imageStore.push(fileData);
            createImagePreview(resizedDataUrl, file.name, previewContainer);
        } catch (err) {
            console.error('Erro ao redimensionar imagem:', err);
            // Fallback: usar original
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
        
        // Carregar tipos no datalist (combobox)
        const tipoDatalist = document.getElementById('tipo_datalist');
        const tipoCombo = document.getElementById('tipo_combo');
        const tipoIdInput = document.getElementById('tipo_id');
        if (tipoDatalist) {
            tipoDatalist.innerHTML = '';
            configData.tipos.forEach(tipo => {
                tipoDatalist.innerHTML += `<option value="${tipo.nome}" data-id="${tipo.id}">`;
            });
        }
        
        // Eventos do combobox de tipos
        if (tipoCombo) {
            tipoCombo.addEventListener('input', function() {
                const valor = this.value.trim();
                const options = tipoDatalist ? tipoDatalist.querySelectorAll('option') : [];
                const match = Array.from(options).find(opt => opt.value.toLowerCase() === valor.toLowerCase());
                
                if (match) {
                    tipoIdInput.value = match.dataset.id;
                    this.classList.remove('new-value');
                    document.getElementById('btn_criar_tipo').style.display = 'none';
                    toggleMultaField();
                } else if (valor.length > 0) {
                    tipoIdInput.value = '';
                    this.classList.add('new-value');
                    document.getElementById('btn_criar_tipo').style.display = 'block';
                    toggleMultaField();
                } else {
                    tipoIdInput.value = '';
                    this.classList.remove('new-value');
                    document.getElementById('btn_criar_tipo').style.display = 'none';
                }
            });
            
            tipoCombo.addEventListener('blur', function() {
                if (this.classList.contains('new-value') && this.value.trim()) {
                    document.getElementById('btn_criar_tipo').style.display = 'block';
                }
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
    const grid = document.getElementById('notifications-grid');
    if (!grid) return;

    grid.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">Carregando...</div>';

    fetch(`${API_BASE_URL_PHP}/notificacoes.php`)
    .then(response => response.json())
    .then(data => {
        if (!data || data.length === 0) {
            grid.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">Nenhuma notificação encontrada.</div>';
            return;
        }

        const podeAcaoRapida = typeof PODE_ACAO_RAPIDA !== 'undefined' ? PODE_ACAO_RAPIDA : false;
        const podeListarLavradas = typeof PODE_LISTAR_ENVIADAS !== 'undefined' ? PODE_LISTAR_ENVIADAS : false;
        const podeListarCobranca = typeof PODE_LISTAR_COBRANCA !== 'undefined' ? PODE_LISTAR_COBRANCA : false;

        let count = 0;
        let cardsHtml = '';
        
        data.forEach(n => {
            const statusSlug = (n.status_slug || '').toLowerCase();
            const dataEmissao = new Date(n.data_emissao + 'T00:00:00');
            const dataFormatada = dataEmissao.toLocaleDateString('pt-BR');

            let acoesHtml = `<a href="editar.php?id=${n.id}" class="btn-small blue">Detalhes</a>`;
            
            if (podeAcaoRapida || EH_ADMIN_DEV) {
                if (statusSlug === 'lavrada') {
                    acoesHtml += ` <button class="btn-small green" onclick="acaoRapidaNotificacao(${n.id}, 'enviada')">Enviar</button>`;
                }
                if (statusSlug === 'ciente') {
                    const podeCobrar = n.pode_ir_cobranca;
                    const motivoBloqueio = n.motivo_bloqueio_cobranca;
                    
                    if (podeCobrar) {
                        acoesHtml += ` <button class="btn-small orange" onclick="acaoRapidaNotificacao(${n.id}, 'cobranca')">Cobrar</button>`;
                    } else {
                        acoesHtml += ` <button class="btn-small orange" style="opacity: 0.5;" onclick="mostrarBloqueioCobranca(${n.id}, '${motivoBloqueio || 'Regra de negócio não atendida'}')" title="${motivoBloqueio || 'Regra de negócio não atendida'}">Cobrar</button>`;
                    }
                }
                if (statusSlug === 'cobranca') {
                    acoesHtml += ` <button class="btn-small red" onclick="acaoRapidaNotificacao(${n.id}, 'encerrada')">Encerrar</button>`;
                }
            }
            
            if ((podeAcaoRapida && (typeof PODE_EDITAR_DATAS !== 'undefined' ? PODE_EDITAR_DATAS : false)) || EH_ADMIN_DEV) {
                acoesHtml += ` <button class="btn-small purple" onclick="abrirQuickEdit(${n.id}, '${n.numero}/${n.ano}')" title="Editar datas"><i class="material-icons" style="font-size: 14px;">edit</i></button>`;
            }
            
            // Botão excluir só para rascunhos
            if (statusSlug === 'rascunho' && ((typeof PODE_EXCLUIR !== 'undefined' && PODE_EXCLUIR) || EH_ADMIN_DEV)) {
                acoesHtml += ` <button class="btn-small red" onclick="excluirNotificacaoCard(${n.id}, '${n.numero}/${n.ano}')" title="Excluir"><i class="material-icons" style="font-size: 14px;">delete</i></button>`;
            }

            cardsHtml += `
                <div class="notificacao-card status-${statusSlug}">
                    <div class="notificacao-header">
                        <span class="notificacao-numero">#${n.numero}/${n.ano}</span>
                        <span class="notificacao-status status-${statusSlug}">${n.status}</span>
                    </div>
                    <div class="notificacao-info">
                        <div><span>Unidade:</span> <strong>${n.bloco || ''}${n.unidade}</strong></div>
                        <div><span>Tipo:</span> <strong>${n.tipo}</strong></div>
                        <div><span>Data:</span> <strong>${dataFormatada}</strong></div>
                    </div>
                    <div class="notificacao-assunto">${n.assunto}</div>
                    <div class="notificacao-acoes">
                        ${acoesHtml}
                    </div>
                </div>
            `;
            count++;
        });

        if (count === 0) {
            grid.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">Nenhuma notificação disponível.</div>';
        } else {
            grid.innerHTML = cardsHtml;
        }
    })
    .catch(error => {
        console.error('Erro ao buscar notificações:', error);
        grid.innerHTML = `<div style="text-align: center; padding: 40px; color: red;">Erro ao carregar notificações</div>`;
    });
}

async function acaoRapidaNotificacao(id, novoStatus, forcar = false) {
    if (novoStatus === 'cobranca' && !forcar) {
        // Verificar se pode ir para cobrança (vai ser validado no servidor também)
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${id}`);
        if (res.ok) {
            const n = await res.json();
            if (!n.pode_ir_cobranca && n.motivo_bloqueio_cobranca) {
                if (EH_ADMIN_DEV) {
                    if (confirm(`ATENÇÃO: ${n.motivo_bloqueio_cobranca}\n\nDeseja FORÇAR a mudança para cobrança?`)) {
                        acaoRapidaNotificacao(id, novoStatus, true);
                    }
                } else {
                    M.toast({html: n.motivo_bloqueio_cobranca, classes: 'orange', timeout: 5000});
                }
                return;
            }
        }
    }
    
    if (!confirm(`Alterar status para "${novoStatus}"?`)) return;
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mudar_fase: true, id: id, nova_fase: novoStatus, forcar: forcar })
        });
        
        if (res.ok) {
            M.toast({html: 'Status atualizado!', classes: 'green'});
            carregarListaNotificacoes();
        } else {
            const err = await res.json();
            M.toast({html: 'Erro: ' + (err.message || 'Falha'), classes: 'red'});
        }
    } catch (e) {
        M.toast({html: 'Erro de conexão', classes: 'red'});
    }
}

function mostrarBloqueioCobranca(id, mensagem) {
    if (EH_ADMIN_DEV) {
        if (confirm(`ATENÇÃO: ${mensagem}\n\nDeseja FORÇAR a mudança para cobrança?`)) {
            acaoRapidaNotificacao(id, 'cobranca', true);
        }
    } else {
        M.toast({html: mensagem, classes: 'orange', timeout: 5000});
    }
}

function fazerLogout() {
    window.location.href = 'logout.php';
}

async function gerarPDF() {
    const apiBase = typeof API_BASE_URL_PHP !== 'undefined' ? API_BASE_URL_PHP : window.location.origin + '/api';
    showStatus('Gerando preview do PDF...', 'loading');
    
    try {
        const dados = getFormData(true);
        if (!dados.numero || !dados.unidade) {
            showStatus('Preencha pelo menos Número e Unidade para gerar o preview.', 'error');
            return;
        }
        
        dados.fotos_fatos = await getExistingImagesForPDF();
        
        const response = await fetch(`${apiBase}/gerar_pdf.php`, {
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

async function getExistingImagesForPDF() {
    const existingImages = [];
    const previewContainer = document.getElementById('preview-container');
    if (!previewContainer) return existingImages;
    
    const imgElements = previewContainer.querySelectorAll('.existing-image:not(.marcada-para-delecao):not(.inativa) img');
    for (const img of imgElements) {
        const src = img.src;
        if (src && !src.startsWith('data:')) {
            try {
                const response = await fetch(src);
                if (response.ok) {
                    const blob = await response.blob();
                    const reader = new FileReader();
                    const base64 = await new Promise((resolve) => {
                        reader.onloadend = () => resolve(reader.result.split(',')[1]);
                        reader.readAsDataURL(blob);
                    });
                    existingImages.push(base64);
                }
            } catch (e) {
                console.warn('Erro ao carregar imagem existente:', src, e);
            }
        }
    }
    
    if (imageStore && imageStore.length > 0) {
        existingImages.push(...imageStore.map(img => img.b64));
    }
    
    return existingImages;
}

function getFormData(forPDF = false) {
    const tipoCombo = document.getElementById('tipo_combo');
    const tipoIdInput = document.getElementById('tipo_id');
    const tipoDatalist = document.getElementById('tipo_datalist');
    
    const tipoNome = tipoCombo ? tipoCombo.value : '';
    const tipoId = tipoIdInput ? parseInt(tipoIdInput.value) : null;

    const assuntoSelect = document.getElementById('assunto_id');
    const selectedAssuntoOption = assuntoSelect.options[assuntoSelect.selectedIndex];

    const ocorrenciaIdInput = document.getElementById('ocorrencia_id');
    const ocorrenciaId = ocorrenciaIdInput ? ocorrenciaIdInput.value : null;

    const dados = {
        numero: document.getElementById('numero').value,
        unidade: document.getElementById('unidade').value,
        bloco: document.getElementById('bloco').value,
        data_emissao: document.getElementById('data_emissao').value,
        fundamentacao_legal: document.getElementById('fundamentacao_legal').value,
        texto_descritivo: document.getElementById('texto_descritivo') ? document.getElementById('texto_descritivo').value : '',
        fatos: Array.from(document.querySelectorAll('#fatos-container textarea')).map(input => input.value).filter(Boolean),
        fotos_fatos: imageStore,
        artigos: typeof getSelectedArticles === 'function' ? getSelectedArticles() : []
    };

    if (ocorrenciaId) {
        dados.ocorrencia_id = parseInt(ocorrenciaId);
    }

    if (forPDF) {
        dados.tipo_notificacao = tipoNome;
        dados.tipo_penalidade = tipoNome.toUpperCase();
        dados.assunto = selectedAssuntoOption.text;
        dados.url_recurso = document.getElementById('url_recurso').value;
        if (tipoNome.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
        dados.fotos_fatos = imageStore.map(img => img.b64);
    } else {
        dados.tipo_id = tipoId;
        dados.tipo_nome = tipoNome;
        dados.assunto_id = parseInt(selectedAssuntoOption.value);
        dados.url_recurso = document.getElementById('url_recurso').value;
        dados.cidade_emissao = "Taguatinga/DF";
        dados.prazo_recurso = 5;
        if (tipoNome.toLowerCase().includes('multa')) {
            dados.valor_multa = document.getElementById('valor_multa').value;
        }
    }
    
    return dados;
}

document.addEventListener('DOMContentLoaded', function() {
    const fundamentacao = document.getElementById('fundamentacao_legal');
    if (fundamentacao) {
        autoExpand(fundamentacao);
    }
});

function abrirQuickEdit(id, numero) {
    if (typeof M === 'undefined') return;
    const modal = document.getElementById('modal-quick-edit');
    if (!modal) return;
    
    document.getElementById('qe-id').value = id;
    document.getElementById('qe-data-envio').value = '';
    document.getElementById('qe-data-ciencia').value = '';
    const select = document.getElementById('qe-recurso-status');
    if (select) {
        select.value = '';
        if (typeof M !== 'undefined') M.FormSelect.init(select);
    }
    
    const titulo = document.getElementById('modal-qe-titulo');
    if (titulo) titulo.textContent = 'Editar #' + numero;
    
    if (typeof M !== 'undefined') M.updateTextFields();
    const instance = M.Modal.getInstance(modal);
    if (instance) instance.open();
}

async function salvarQuickEdit() {
    if (typeof M === 'undefined') return;
    const id = document.getElementById('qe-id').value;
    const dataEnvio = document.getElementById('qe-data-envio').value;
    const dataCiencia = document.getElementById('qe-data-ciencia').value;
    
    const podeEditarRecurso = (typeof PODE_JULGAR_RECURSO !== 'undefined' && PODE_JULGAR_RECURSO) || EH_ADMIN_DEV;
    const recursoStatus = podeEditarRecurso ? document.getElementById('qe-recurso-status').value : '';
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                quick_edit: true,
                id: parseInt(id),
                data_envio: dataEnvio || null,
                data_ciencia: dataCiencia || null,
                recurso_status: recursoStatus || null
            })
        });
        
        if (res.ok) {
            M.toast({html: 'Atualizado com sucesso!', classes: 'green'});
            const modal = document.getElementById('modal-quick-edit');
            if (modal) {
                const instance = M.Modal.getInstance(modal);
                if (instance) instance.close();
            }
            if (typeof carregarListaNotificacoes === 'function') carregarListaNotificacoes();
        } else {
            const err = await res.json();
            M.toast({html: 'Erro: ' + (err.message || 'Falha'), classes: 'red'});
        }
    } catch (e) {
        M.toast({html: 'Erro de conexão', classes: 'red'});
    }
}

async function sincronizarEvidencias() {
    if (typeof NOTIFICACAO_ID === 'undefined' || !NOTIFICACAO_ID) {
        if (typeof M !== 'undefined') {
            M.toast({html: 'Salve a notificação primeiro para sincronizar as evidências.', classes: 'orange', timeout: 4000});
        }
        return;
    }
    
    if (typeof M !== 'undefined') {
        M.toast({html: 'Sincronizando evidências...', classes: 'blue', timeout: 2000});
    }
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'sincronizar_evidencias',
                notificacao_id: NOTIFICACAO_ID,
                ocorrencia_id: OCORRENCIA_ID
            })
        });
        
        const data = await res.json();
        
        if (res.ok) {
            // Adicionar evidências de texto como fatos
            if (data.text_evidencias && data.text_evidencias.length > 0) {
                data.text_evidencias.forEach(texto => {
                    addFato(texto);
                });
                if (typeof M !== 'undefined') {
                    M.toast({html: `${data.text_evidencias.length} evidência(s) de texto adicionada(s) como fato.`, classes: 'green'});
                }
            } else {
                if (typeof M !== 'undefined') {
                    M.toast({html: data.message || 'Evidências sincronizadas!', classes: 'green'});
                }
            }
            
            if (typeof carregarImagens === 'function') {
                carregarImagens();
            }
        } else {
            if (typeof M !== 'undefined') {
                M.toast({html: 'Erro: ' + (data.message || 'Falha'), classes: 'red'});
            }
        }
    } catch (e) {
        if (typeof M !== 'undefined') {
            M.toast({html: 'Erro de conexão', classes: 'red'});
        }
    }
}

async function criarNovoTipo() {
    const tipoCombo = document.getElementById('tipo_combo');
    const tipoDatalist = document.getElementById('tipo_datalist');
    const tipoIdInput = document.getElementById('tipo_id');
    const novoNome = tipoCombo.value.trim();
    
    if (!novoNome) {
        M.toast({html: 'Digite um nome para o tipo.', classes: 'orange'});
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'criar_tipo',
                nome: novoNome
            })
        });
        
        const data = await res.json();
        
        if (res.ok) {
            // Adicionar ao datalist
            const option = document.createElement('option');
            option.value = novoNome;
            option.dataset.id = data.id;
            tipoDatalist.appendChild(option);
            
            // Selecionar o novo tipo
            tipoIdInput.value = data.id;
            tipoCombo.classList.remove('new-value');
            document.getElementById('btn_criar_tipo').style.display = 'none';
            
            M.toast({html: `Tipo "${novoNome}" criado!`, classes: 'green'});
            toggleMultaField();
        } else {
            M.toast({html: 'Erro: ' + (data.message || 'Falha'), classes: 'red'});
        }
    } catch (e) {
        M.toast({html: 'Erro de conexão', classes: 'red'});
    }
}

async function excluirNotificacao() {
    const podeExcluir = (typeof PODE_EXCLUIR !== 'undefined' && PODE_EXCLUIR) || EH_ADMIN_DEV;
    if (!podeExcluir) {
        M.toast({html: 'Você não tem permissão para excluir.', classes: 'red'});
        return;
    }
    
    const id = document.getElementById('qe-id').value;
    if (!id) return;
    
    if (!confirm('Tem certeza que deseja excluir esta notificação? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'excluir',
                id: parseInt(id)
            })
        });
        
        if (res.ok) {
            M.toast({html: 'Notificação excluída!', classes: 'green'});
            const modal = document.getElementById('modal-quick-edit');
            if (modal) {
                const instance = M.Modal.getInstance(modal);
                if (instance) instance.close();
            }
            if (typeof carregarListaNotificacoes === 'function') carregarListaNotificacoes();
        } else {
            const err = await res.json();
            M.toast({html: 'Erro: ' + (err.message || 'Falha'), classes: 'red'});
        }
    } catch (e) {
        M.toast({html: 'Erro de conexão', classes: 'red'});
    }
}

async function excluirNotificacaoCard(id, numero) {
    const podeExcluir = (typeof PODE_EXCLUIR !== 'undefined' && PODE_EXCLUIR) || EH_ADMIN_DEV;
    if (!podeExcluir) {
        M.toast({html: 'Você não tem permissão para excluir.', classes: 'red'});
        return;
    }
    
    if (!confirm(`Excluir notificação #${numero}? Esta ação não pode ser desfeita.`)) {
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'excluir',
                id: id
            })
        });
        
        if (res.ok) {
            M.toast({html: 'Notificação excluída!', classes: 'green'});
            carregarListaNotificacoes();
        } else {
            const err = await res.json();
            M.toast({html: 'Erro: ' + (err.message || 'Falha'), classes: 'red'});
        }
    } catch (e) {
        M.toast({html: 'Erro de conexão', classes: 'red'});
    }
}
