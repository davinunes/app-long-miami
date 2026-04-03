// js/editar.js - Lógica específica da página de EDIÇÃO

const API_BASE_URL_PHP = '/api';

document.addEventListener('DOMContentLoaded', async function() {
    console.log("✅ 1. Página de edição carregada. Iniciando script 'editar.js'.");

    try {
        // Configura o botão principal para a ação de ATUALIZAR
        document.getElementById('btnSalvar').textContent = '💾 Atualizar Notificação';
        document.getElementById('btnSalvar').onclick = atualizarNotificacao;
        console.log("✅ 2. Botão 'Atualizar' configurado.");

        // Adiciona os helpers de UI
        configurarCampoBloco();
        vincularCamposUnidadeBloco();
        console.log("✅ 3. Helpers de UI (bloco/unidade) configurados.");

        showStatus('Carregando...', 'loading');
        
        console.log("⏳ 4. Buscando dados iniciais (tipos, assuntos)...");
        await fetchInitialData();
        console.log("✅ 5. Dados iniciais (tipos, assuntos) carregados com sucesso.");

        console.log("⏳ 6. Buscando dados da notificação específica (ID: " + NOTIFICACAO_ID + ")...");
        await carregarDadosNotificacao();
        console.log("✅ 7. FIM: Função para carregar dados da notificação foi chamada com sucesso.");

    } catch (error) {
        // Se qualquer um dos passos acima falhar, este bloco será executado.
        console.error("❌ ERRO CRÍTICO no bloco de inicialização:", error);
        showStatus("Ocorreu um erro crítico ao iniciar a página. Verifique o console.", 'error');
    }
});

async function carregarDadosNotificacao() {
    showStatus('Carregando dados da notificação...', 'loading');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${NOTIFICACAO_ID}`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('accessToken')}` }
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Notificação não encontrada.');
        }
        
        const data = await response.json();

        // --- PONTO DE DEBUG 1 ---
        console.log("DADOS BRUTOS RECEBIDOS DA API:", data);

        preencherFormulario(data);
        showStatus('Dados da notificação carregados com sucesso.', 'success');
        
    } catch (error) {
        console.error("ERRO EM carregarDadosNotificacao:", error);
        showStatus(error.message, 'error');
        document.getElementById('btnSalvar').disabled = true;
    }
}

// MODIFICADO: A função de atualizar agora envia a lista de imagens a serem deletadas
async function atualizarNotificacao() {
    const dados = await getFormData(false);
    dados.id = NOTIFICACAO_ID;
    dados.status_id = 1;
    
    // NOVO: Adiciona a lista de IDs a serem deletados no payload
    dados.imagens_para_deletar = imagensParaDeletar;

    showStatus('Atualizando notificação...', 'loading');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        if (response.ok) {
            showStatus(result.message, 'success');
            setTimeout(() => window.location.href = 'lista.php', 1500);
        } else {
            showStatus(`Erro: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão: ${error.message}`, 'error');
    }
}

/**
 * NOVO: Marca ou desmarca uma imagem existente para deleção.
 * @param {number} imageId - O ID da imagem no banco de dados.
 */
function marcarParaDeletar(imageId) {
    const previewItem = document.getElementById(`imagem-salva-${imageId}`);
    const jaMarcada = imagensParaDeletar.includes(imageId);

    if (jaMarcada) {
        // Se já estava marcada, desmarca
        imagensParaDeletar = imagensParaDeletar.filter(id => id !== imageId);
        previewItem.classList.remove('marcada-para-delecao');
    } else {
        // Se não estava marcada, marca
        imagensParaDeletar.push(imageId);
        previewItem.classList.add('marcada-para-delecao');
    }
    
    console.log("Imagens marcadas para deletar:", imagensParaDeletar);
}

function preencherFormulario(data) {
    console.log("--- INICIANDO PREENCHIMENTO DO FORMULÁRIO ---");

    try {
        // --- PONTO DE DEBUG PARA CADA CAMPO PROBLEMÁTICO ---
        console.log(`Tentando preencher 'bloco' com o valor: "${data.bloco}"`);
        document.getElementById('bloco').value = data.bloco || ''; // Usamos || '' para evitar 'null'

        console.log(`Tentando preencher 'fundamentacao_legal' com o valor: "${data.fundamentacao_legal}"`);
        document.getElementById('fundamentacao_legal').value = data.fundamentacao_legal || '';

        console.log(`Tentando preencher 'data_emissao' com o valor: "${data.data_emissao}"`);
        document.getElementById('data_emissao').value = data.data_emissao;

        // Preenchendo os outros campos que já funcionavam
        document.getElementById('notificacao_id').value = data.id;
        document.getElementById('numero').value = `${data.numero}/${data.ano}`;
        document.getElementById('unidade').value = data.unidade;
        document.getElementById('url_recurso').value = data.url_recurso;
        document.getElementById('tipo_id').value = data.tipo_id;
        document.getElementById('assunto_id').value = data.assunto_id;

        // Lógica dos fatos
        const fatosContainer = document.getElementById('fatos-container');
        fatosContainer.innerHTML = '';
        if (data.fatos && data.fatos.length > 0) {
            data.fatos.forEach(fatoDescricao => addFato(fatoDescricao));
        } else {
            addFato();
        }

        // --- PONTO DE DEBUG PARA IMAGENS ---
        console.log("Dados das imagens recebidos:", data.imagens);
        const previewContainer = document.getElementById('preview-container');
		if (data.imagens && data.imagens.length > 0) {
			data.imagens.forEach(img => {
				const imageUrl = `/uploads/imagens/${img.caminho_arquivo}`;
				const item = document.createElement('div');
				item.className = 'img-preview-item existing-image';
				item.id = `imagem-salva-${img.id}`; // Adiciona um ID único ao elemento
				
				// ALTERAÇÃO AQUI: Adicionado data-caminho-arquivo="${img.caminho_arquivo}"
				item.innerHTML = `
					<img src="${imageUrl}" alt="${img.nome_original}" data-caminho-arquivo="${img.caminho_arquivo}">
					<small>Salva</small>
					<button type="button" class="remove-btn-existing" onclick="marcarParaDeletar(${img.id})">&times;</button>
				`;
				previewContainer.appendChild(item);
			});
		} else {
            console.log("Nenhuma imagem encontrada para esta notificação.");
        }

        toggleMultaField();
        console.log("--- PREENCHIMENTO DO FORMULÁRIO CONCLUÍDO ---");

    } catch (error) {
        console.error("❌ ERRO DENTRO DE preencherFormulario:", error);
        showStatus("Erro ao tentar preencher os campos do formulário.", "error");
    }
}


/**
 * NOVO: Função auxiliar para converter uma URL de imagem em uma string Base64.
 * @param {string} url - A URL da imagem a ser convertida.
 * @returns {Promise<string>} Uma Promise que resolve com a string Base64 (sem o prefixo).
 */
function urlParaBase64(url) {
    return new Promise((resolve, reject) => {
        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const reader = new FileReader();
                reader.onloadend = () => {
                    // Resultado é 'data:image/jpeg;base64, ...'
                    // Nós queremos apenas a parte depois da vírgula
                    const base64String = reader.result.split(',')[1];
                    resolve(base64String);
                };
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            })
            .catch(reject);
    });
}