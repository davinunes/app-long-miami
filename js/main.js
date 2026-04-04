let configDataGlobal = { grupos: [], papeis: [] };

async function inicializarGerenciadorUsuarios() {
    console.log('Inicializando gerenciador de usuários...');
    try {
        await M.AutoInit();
    } catch(e) {
        console.log('AutoInit não disponível');
    }
    
    await carregarConfiguracoesUsuarios();
    await carregarListaUsuarios();
    await carregarListaGrupos();
    setupEventListenersUsuarios();
}

async function carregarConfiguracoesUsuarios() {
    console.log('Carregando configurações...');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/config.php`);
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.message || 'Falha ao carregar configurações: ' + response.status);
        }
        configDataGlobal = await response.json();
        console.log('Configurações carregadas:', configDataGlobal);
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
    }
}

function setupEventListenersUsuarios() {
    console.log('Configurando event listeners...');
    
    $(document).on('click', '#btn-novo-usuario', function(e) {
        console.log('Clicou em novo usuário');
        e.preventDefault();
        abrirModalUsuario(null);
    });
    
    $(document).on('click', '#btn-gerenciar-grupos', function(e) {
        console.log('Clicou em gerenciar grupos');
        e.preventDefault();
        const modal = M.Modal.getInstance($('#modal-grupos'));
        if (modal) modal.open();
        else $('#modal-grupos').modal('open');
    });
    
    $(document).on('click', '#modal-salvar-usuario', salvarUsuarioModal);
    $(document).on('click', '#btn-salvar-grupo', salvarGrupoModal);
    $(document).on('click', '#btn-criar-grupo', criarGrupo);
    
    console.log('Event listeners configurados');
}

async function carregarListaUsuarios() {
    const tbody = $('#usuarios-table-body');
    tbody.html('<tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>');
    
    console.log('Carregando lista de usuários...');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php`);
        
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.message || 'Erro ao buscar usuários: ' + response.status);
        }
        
        const usuarios = await response.json();
        console.log('Usuários carregados:', usuarios);
        tbody.empty();

        if (usuarios.length === 0) {
            tbody.html('<tr><td colspan="4" style="text-align: center;">Nenhum usuário encontrado.</td></tr>');
            return;
        }

        usuarios.forEach(user => {
            const gruposDisplay = Array.isArray(user.grupos) && user.grupos.length > 0 
                ? user.grupos.map(g => `<span class="chip">${typeof g === 'string' ? g : g.nome}</span>`).join(' ') 
                : '<span style="color: #999;">-</span>';
            
            const row = `
                <tr>
                    <td>${user.nome}</td>
                    <td>${user.email}</td>
                    <td>${gruposDisplay}</td>
                    <td>
                        <button class="btn-floating btn-small waves-effect waves-light blue modal-trigger" data-id="${user.id}" onclick="abrirModalUsuario(${user.id})">
                            <i class="material-icons">edit</i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

    } catch (error) {
        console.error('Erro ao buscar usuários:', error);
        tbody.html(`<tr><td colspan="4" style="text-align: center; color: red;">Erro: ${error.message}</td></tr>`);
    }
}

async function abrirModalUsuario(id) {
    const modal = $('#modal-usuario');
    const form = $('#form-usuario');
    
    form[0].reset();
    popularSelectGrupos();
    
    if (id) {
        $('#modal-usuario-titulo').text('Editar Usuário');
        $('#usuario_id').val(id);
        $('#senha-helper-text').text('Deixe em branco para não alterar a senha.');
        $('#usuario_senha').prop('required', false);

        try {
            const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php?id=${id}`);
            if (!response.ok) throw new Error('Não foi possível carregar os dados do usuário.');
            
            const user = await response.json();
            $('#usuario_nome').val(user.nome);
            $('#usuario_email').val(user.email);
            
            if (user.grupos) {
                const grupoIds = user.grupos.map(g => String(g.id || g));
                $('#usuario_grupos').val(grupoIds);
            }
            
        } catch (error) {
            alert(error.message);
            return;
        }

    } else {
        $('#modal-usuario-titulo').text('Novo Usuário');
        $('#usuario_id').val('');
        $('#senha-helper-text').text('A senha é obrigatória para criar.');
        $('#usuario_senha').prop('required', true);
    }
    
    M.updateTextFields();
    $('select').formSelect();
    M.Modal.getInstance(modal).open();
}

function popularSelectGrupos() {
    const gruposSelect = $('#usuario_grupos');
    gruposSelect.html('<option value="" disabled>Selecione os grupos</option>');
    if (configDataGlobal.grupos) {
        configDataGlobal.grupos.forEach(g => {
            gruposSelect.append(`<option value="${g.id}">${g.nome}</option>`);
        });
    }
}

async function salvarUsuarioModal() {
    const id = $('#usuario_id').val();
    const nome = $('#usuario_nome').val();
    const email = $('#usuario_email').val();
    const senha = $('#usuario_senha').val();
    
    if (!nome || !email) {
        M.toast({html: 'Preencha nome e email.', classes: 'red'});
        return;
    }
    
    if (!id && !senha) {
        M.toast({html: 'A senha é obrigatória para criar usuário.', classes: 'red'});
        return;
    }
    
    const dados = {
        nome: nome,
        email: email,
        grupos: $('#usuario_grupos').val() || []
    };
    
    if (senha) {
        dados.senha = senha;
    }
    
    if (id) {
        dados.id = parseInt(id);
    }
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Erro ao salvar.');
        }

        M.toast({html: result.message, classes: 'green'});
        M.Modal.getInstance($('#modal-usuario')).close();
        carregarListaUsuarios();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

function carregarListaNotificacoes() {
    const tbody = document.getElementById('notifications-table-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>';

    fetch(`${API_BASE_URL_PHP}/notificacoes.php`)
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro na rede: ${response.statusText}`);
        }
        return response.json();
    })
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

async function fetchProximoNumero() {
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?proximo_numero=true`);
        if (!response.ok) throw new Error('Falha ao buscar o próximo número.');
        const data = await response.json();
        const numeroInput = document.getElementById('numero');
        if (numeroInput && data.proximo_numero) {
            numeroInput.value = data.proximo_numero;
        }
    } catch (error) {
        showStatus(error.message, 'error');
        console.error("Fetch next number error:", error);
    }
}

async function salvarNotificacao() {
    console.log("salvarNotificacao: INICIADA");
    const dados = getFormData(false);
    console.log("salvarNotificacao: Dados:", dados);

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
            showStatus(result.message, 'success');
            const novoId = result.id || dados.id;
            if (novoId) {
                setTimeout(() => { window.location.href = 'editar.php?id=' + novoId; }, 1500);
            } else {
                setTimeout(() => { window.location.href = 'lista.php'; }, 1500);
            }
        } else {
            showStatus(`Erro ao salvar: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão com a API PHP: ${error.message}`, 'error');
    }
}

async function inicializarFormularioEdicao() {
    const notificacaoId = NOTIFICACAO_ID;
    
    if (!notificacaoId) {
        document.getElementById('main-content').innerHTML = "<h1>Erro: ID da notificação não fornecido na URL.</h1>";
        console.error("ID não encontrado");
        return;
    }

    console.log(`Iniciando formulário de edição para o ID: ${notificacaoId}`);

    let imagensParaDeletar = [];

    window.marcarParaDeletar = function(imageId) {
        const previewItem = document.getElementById(`imagem-salva-${imageId}`);
        const jaMarcada = imagensParaDeletar.includes(imageId);

        if (jaMarcada) {
            imagensParaDeletar = imagensParaDeletar.filter(id => id !== imageId);
            previewItem.classList.remove('marcada-para-delecao');
        } else {
            imagensParaDeletar.push(imageId);
            previewItem.classList.add('marcada-para-delecao');
        }
        console.log("Imagens marcadas para deletar:", imagensParaDeletar);
    };

    window.atualizarNotificacaoGlobal = async function() {
        const dados = getFormData(false);
        dados.id = notificacaoId;
        dados.status_id = 1;
        dados.imagens_para_deletar = imagensParaDeletar;
        dados.imagens_ocorrencia_ativar = Array.from(window.imagensOcorrenciaAtivas || []);
        dados.imagens_ocorrencia_desativar = Array.from(window.imagensOcorrenciaInativas || []);

        showStatus('Atualizando notificação...', 'loading');

        try {
            const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const result = await response.json();
            if (response.ok) {
                showStatus(result.message, 'success');
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showStatus(`Erro: ${result.message}`, 'error');
            }
        } catch (error) {
            showStatus(`Erro de conexão: ${error.message}`, 'error');
        }
    };

    function preencherFormulario(data) {
        try {
            document.getElementById('notificacao_id').value = data.id;
            document.getElementById('numero').value = `${data.numero}/${data.ano}`;
            document.getElementById('unidade').value = data.unidade;
            document.getElementById('bloco').value = data.bloco || '';
            document.getElementById('url_recurso').value = data.url_recurso || '';
            document.getElementById('fundamentacao_legal').value = data.fundamentacao_legal || '';
            document.getElementById('data_emissao').value = data.data_emissao;
            document.getElementById('tipo_id').value = data.tipo_id;
            document.getElementById('assunto_id').value = data.assunto_id;
            
            if (data.ocorrencia_id) {
                document.getElementById('ocorrencia_id').value = data.ocorrencia_id;
                document.getElementById('ocorrencia_titulo').textContent = data.ocorrencia_titulo 
                    ? `Ocorrência #${data.ocorrencia_id}: ${data.ocorrencia_titulo}`
                    : `Ocorrência #${data.ocorrencia_id}`;
                document.getElementById('ver_ocorrencia_link').href = `ocorrencia_detalhe.php?id=${data.ocorrencia_id}`;
                document.getElementById('ocorrencia_info').style.display = 'block';
                document.getElementById('ocorrencia_busca_section').style.display = 'none';
                document.getElementById('evidencias_ocorrencia_section').style.display = 'none';
            } else {
                document.getElementById('ocorrencia_id').value = '';
                document.getElementById('ocorrencia_info').style.display = 'none';
                document.getElementById('ocorrencia_busca_section').style.display = 'block';
                document.getElementById('evidencias_ocorrencia_section').style.display = 'none';
            }
            
            setTimeout(function() {
                $('select').formSelect();
                toggleMultaField();
            }, 100);
            
            const fatosContainer = document.getElementById('fatos-container');
            fatosContainer.innerHTML = '';
            if (data.fatos && data.fatos.length > 0) {
                data.fatos.forEach(fatoDescricao => addFato(fatoDescricao));
            } else {
                addFato();
            }

            window.toggleImgOcorrencia = function(imageId) {
                const previewItem = document.getElementById(`img-ocorrencia-${imageId}`);
                const btn = previewItem.querySelector('.toggle-btn');
                
                if (previewItem.classList.contains('inativa')) {
                    window.imagensOcorrenciaAtivas.add(imageId);
                    window.imagensOcorrenciaInativas.delete(imageId);
                    previewItem.classList.remove('inativa');
                    btn.textContent = 'Desativar';
                } else {
                    window.imagensOcorrenciaAtivas.delete(imageId);
                    window.imagensOcorrenciaInativas.add(imageId);
                    previewItem.classList.add('inativa');
                    btn.textContent = 'Ativar';
                }
            };

            window.imagensOcorrenciaAtivas = new Set();
            window.imagensOcorrenciaInativas = new Set();

            const previewContainer = document.getElementById('preview-container');
            if (data.imagens && data.imagens.length > 0) {
                data.imagens.forEach(img => {
                    const imageUrl = img.ocorrencia_id 
                        ? `/${img.caminho_arquivo}` 
                        : `/uploads/imagens/${img.caminho_arquivo}`;
                    const item = document.createElement('div');
                    item.className = 'img-preview-item existing-image';
                    item.id = `img-ocorrencia-${img.id}`;
                    
                    if (img.ocorrencia_id) {
                        window.imagensOcorrenciaAtivas.add(img.id);
                        item.innerHTML = `
                            <img src="${imageUrl}" alt="" style="max-width: 150px; max-height: 150px; cursor: pointer;" onclick="window.open('${imageUrl}', '_blank')">
                            <span class="img-label">Evidência da Ocorrência</span>
                            <button type="button" class="toggle-btn" onclick="toggleImgOcorrencia(${img.id})" style="background: #27ae60; color: white; border: none; padding: 2px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">Desativar</button>
                        `;
                    } else {
                        item.innerHTML = `
                            <img src="${imageUrl}" alt="">
                            <span class="img-label">Salva</span>
                            <button type="button" class="remove-btn-existing" onclick="marcarParaDeletar(${img.id})">&times;</button>
                        `;
                    }
                    previewContainer.appendChild(item);
                });
            }

            toggleMultaField();
            
            if (data.valor_multa) {
                const multaInput = document.getElementById('valor_multa');
                if (multaInput) multaInput.value = data.valor_multa;
                const multaGroup = document.getElementById('valor_multa_group');
                if (multaGroup) multaGroup.classList.remove('hidden');
            }

            if (data.artigos && data.artigos.length > 0 && typeof setSelectedArticles === 'function') {
                setSelectedArticles(data.artigos);
            }
            
            console.log("Formulário preenchido com sucesso.");
        } catch (error) {
            console.error("Erro ao preencher o formulário:", error);
            showStatus("Ocorreu um erro ao exibir os dados no formulário.", "error");
        }
    }

    try {
        const btnSalvar = document.getElementById('btnSalvar');
        if (btnSalvar) {
            btnSalvar.textContent = '💾 Atualizar Notificação';
            btnSalvar.onclick = window.atualizarNotificacaoGlobal;
        }

        showStatus('Carregando dados da notificação...', 'loading');

        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${notificacaoId}`);
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Notificação não encontrada.');
        }
        const data = await response.json();

        await fetchInitialData();
        $('select').formSelect();
        preencherFormulario(data);
        showStatus('Dados carregados. Pronto para edição.', 'success');

    } catch (error) {
        console.error("Erro ao carregar dados:", error);
        showStatus(error.message, 'error');
    }
}

async function carregarDadosNotificacao() {
    showStatus('Carregando dados da notificação...', 'loading');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${NOTIFICACAO_ID}`);
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

async function atualizarNotificacao(id) {
    const dados = await getFormData(false);
    dados.id = id;
    dados.status_id = 1;
    
    // NOVO: Adiciona a lista de IDs a serem deletados no payload
    dados.imagens_para_deletar = imagensParaDeletar;

    showStatus('Atualizando notificação...', 'loading');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        if (response.ok) {
            showStatus(result.message, 'success');
            setTimeout(() => { window.location.href = `editar.php?id=${id}`; }, 1500);
        } else {
            showStatus(`Erro: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão: ${error.message}`, 'error');
    }
}

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

async function inicializarFormularioNotificacao() {
    console.log("🚀 Inicializando formulário de notificação...");

    // Passo 1: Popula os dropdowns. O 'await' garante que o código ESPERA
    // a conclusão desta etapa antes de continuar.
    await fetchInitialData(); 
    console.log("✅ Dropdowns (Tipos/Assuntos) populados.");

    // --- A CORREÇÃO MÁGICA ACONTECE AQUI ---
    // Passo 2: AGORA que os <select>s têm <option>s, inicializamos o Materialize.
    $('select').formSelect();
    console.log("✅ Componentes <select> do Materialize inicializados.");

    // Passo 3: Inicializa busca do regimento
    await inicializarBuscaRegimento();
    console.log("✅ Busca do regimento inicializada.");

    // Passo 4: O resto da lógica continua normalmente...
    const hash = window.location.hash;
    const queryStringIndex = hash.indexOf('?');
    let notificacaoId = null;

    if (queryStringIndex !== -1) {
        const urlParams = new URLSearchParams(hash.substring(queryStringIndex + 1));
        notificacaoId = urlParams.get('id');
    }

    if (notificacaoId) {
        console.log(`🎨 Configurando formulário para EDIÇÃO do ID: ${notificacaoId}`);
        await configurarModoEdicao(notificacaoId);
    } else {
        console.log(`✨ Configurando formulário para CRIAÇÃO de nova notificação.`);
        await configurarModoCriacao();
    }
}

async function configurarModoEdicao(id) {
    document.getElementById('btnSalvar').textContent = '💾 Atualizar Notificação';

    showStatus('Carregando dados para edição...', 'loading');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${id}`);
        
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.message || 'Falha ao buscar dados da notificação.');
        }
        
        const data = await response.json();
        console.log("📋 Dados da notificação recebidos:", data);
        console.log("📋 tipo_id:", data.tipo_id, "assunto_id:", data.assunto_id);
        
        preencherFormulario(data);
        showStatus('Pronto para edição.', 'success');
    } catch (error) {
        console.error('Erro ao carregar notificação:', error);
        showStatus('Erro: ' + error.message, 'error');
    }
}

async function configurarModoCriacao() {
    console.log("✨ Configurando formulário para CRIAÇÃO de nova notificação.");

    // Apenas define o texto do botão. O listener de clique será gerenciado pelo jwt.js
    document.getElementById('btnSalvar').textContent = '💾 Salvar Nova Notificação';

    // O resto da sua lógica de inicialização continua aqui (data, fato, número)
    document.getElementById('data_emissao').value = new Date().toISOString().split('T')[0];
    addFato(); 
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?proximo_numero=true`);
        const data = await response.json();
        if (data.proximo_numero) {
            document.getElementById('numero').value = data.proximo_numero;
        }
    } catch (error) {
        console.error("Erro ao buscar próximo número:", error);
        showStatus("Não foi possível obter o número da notificação.", "error");
    }
}

function urlParaBase64(url) {
    return new Promise((resolve, reject) => {
        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const reader = new FileReader();
                reader.onloadend = () => {
                    const base64String = reader.result.split(',')[1];
                    resolve(base64String);
                };
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            })
            .catch(reject);
    });
}

async function carregarListaGrupos() {
    const lista = $('#grupos-lista');
    lista.html('<li class="collection-item">Carregando...</li>');
    
    console.log('Carregando lista de grupos...');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/grupos.php`);
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.message || 'Erro ao buscar grupos: ' + response.status);
        }
        
        const grupos = await response.json();
        console.log('Grupos carregados:', grupos);
        lista.empty();
        
        if (grupos.length === 0) {
            lista.html('<li class="collection-item">Nenhum grupo encontrado.</li>');
            return;
        }
        
        grupos.forEach(g => {
            const PapeisHtml = g.papeis && g.papeis.length > 0 
                ? g.papeis.map(p => `<span class="chip">${p}</span>`).join(' ')
                : '<span style="color: #999;">Sem papéis</span>';
            
            lista.append(`
                <li class="collection-item">
                    <div>
                        <strong>${g.nome}</strong>
                        ${g.descricao ? `<p style="margin: 5px 0; color: #666;">${g.descricao}</p>` : ''}
                        <div style="margin-top: 5px;">${PapeisHtml}</div>
                    </div>
                    <div style="float: right;">
                        <button class="btn-small blue" onclick="editarGrupo(${g.id})"><i class="material-icons">edit</i></button>
                        <button class="btn-small red" onclick="deletarGrupo(${g.id})"><i class="material-icons">delete</i></button>
                    </div>
                </li>
            `);
        });
        
        popularFormularioNovoGrupo();
        
    } catch (error) {
        lista.html(`<li class="collection-item" style="color: red;">Erro: ${error.message}</li>`);
    }
}

function popularFormularioNovoGrupo() {
    const container = $('#novo-grupo-papeis');
    container.empty();
    
    if (!configDataGlobal.papeis || configDataGlobal.papeis.length === 0) return;
    
    container.html('<label style="margin-bottom: 10px;">Papéis do Grupo</label>');
    
    configDataGlobal.papeis.forEach(p => {
        container.append(`
            <p>
                <label>
                    <input type="checkbox" class="novo-grupo-papel" value="${p.slug}">
                    <span>${p.nome}</span>
                </label>
            </p>
        `);
    });
}

async function criarGrupo() {
    const nome = $('#novo_grupo_nome').val().trim();
    const desc = $('#novo_grupo_desc').val().trim();
    
    if (!nome) {
        M.toast({html: 'Informe o nome do grupo.', classes: 'red'});
        return;
    }
    
    const papeis = [];
    $('.novo-grupo-papel:checked').each(function() {
        papeis.push($(this).val());
    });
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/grupos.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                criar_grupo: true,
                nome: nome,
                descricao: desc,
                papeis: papeis
            })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        
        M.toast({html: result.message, classes: 'green'});
        $('#novo_grupo_nome').val('');
        $('#novo_grupo_desc').val('');
        $('.novo-grupo-papel').prop('checked', false);
        carregarListaGrupos();
        carregarConfiguracoesUsuarios();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function editarGrupo(id) {
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/grupos.php?id=${id}`);
        if (!response.ok) throw new Error('Erro ao buscar grupo.');
        
        const grupo = await response.json();
        
        $('#grupo_id').val(grupo.id);
        $('#grupo_nome').val(grupo.nome);
        $('#grupo_desc').val(grupo.descricao || '');
        
        $('#grupo-papeis-checkboxes').empty();
        if (configDataGlobal.papeis) {
            configDataGlobal.papeis.forEach(p => {
                const checked = grupo.papeis && grupo.papeis.includes(p.slug) ? 'checked' : '';
                $('#grupo-papeis-checkboxes').append(`
                    <p>
                        <label>
                            <input type="checkbox" class="grupo-papel-editar" value="${p.slug}" ${checked}>
                            <span>${p.nome}</span>
                        </label>
                    </p>
                `);
            });
        }
        
        M.updateTextFields();
        M.Modal.getInstance($('#modal-editar-grupo')).open();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function salvarGrupoModal() {
    const id = $('#grupo_id').val();
    const nome = $('#grupo_nome').val().trim();
    const desc = $('#grupo_desc').val().trim();
    
    if (!nome) {
        M.toast({html: 'Informe o nome do grupo.', classes: 'red'});
        return;
    }
    
    const papeis = [];
    $('.grupo-papel-editar:checked').each(function() {
        papeis.push($(this).val());
    });
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/grupos.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: parseInt(id),
                nome: nome,
                descricao: desc,
                papeis: papeis
            })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        
        M.toast({html: result.message, classes: 'green'});
        M.Modal.getInstance($('#modal-editar-grupo')).close();
        carregarListaGrupos();
        carregarConfiguracoesUsuarios();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function deletarGrupo(id) {
    if (!confirm('Tem certeza que deseja deletar este grupo?')) return;
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/grupos.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                deletar: true,
                id: id
            })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        
        M.toast({html: 'Grupo deletado.', classes: 'green'});
        carregarListaGrupos();
        carregarConfiguracoesUsuarios();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}