let configDataGlobal = { grupos: [], permissoes: [], permissoesPorModulo: {} };

async function inicializarGerenciadorUsuarios() {
    console.log('Inicializando gerenciador de usuários...');
    try {
        await M.AutoInit();
        $('.modal').modal(); // Inicialização explícita para garantir
    } catch(e) {
        console.log('AutoInit não disponível');
    }
    
    await carregarConfiguracoesUsuarios();
    
    // Controlar visibilidade baseada em permissões
    controlarVisibilidadeBotoes();
    
    // Se não tem permissões de gerenciamento, só carrega usuários se tiver permissão de listar
    if (temAlgumaPermissao(['usuario.listar', 'usuario.ver_detalhes', 'admin', 'dev']) || EH_ADMIN_DEV) {
        await carregarListaUsuarios();
    }
    
    // Se tem permissão de gerenciar grupos
    if (temAlgumaPermissao(['grupo.listar', 'grupo.criar', 'grupo.editar', 'grupo.excluir']) || EH_ADMIN_DEV) {
        await carregarListaGrupos();
    }
    
    setupEventListenersUsuarios();
    setupEventListenersMinhaConta();
}

function controlarVisibilidadeBotoes() {
    // Botão Novo Usuário
    if (temPermissao('usuario.criar') || EH_ADMIN_DEV) {
        $('#btn-novo-usuario').show();
    }
    
    // Botão Gerenciar Grupos
    if (temAlgumaPermissao(['grupo.listar', 'grupo.criar', 'grupo.editar']) || EH_ADMIN_DEV) {
        $('#btn-gerenciar-grupos').show();
    }
    
    // Seção de usuários (se tem alguma permissão)
    if (temAlgumaPermissao(['usuario.listar', 'usuario.ver_detalhes', 'usuario.editar', 'usuario.criar']) || EH_ADMIN_DEV) {
        $('#usuarios-section').show();
    }
    
    // Se não tem nenhuma permissão, esconde tudo exceto "Minha Conta"
    if (!EH_ADMIN_DEV && PERMISSOES_USUARIO.length === 0) {
        $('#page-description').text('Visualize e gerencie suas informações pessoais.');
    }
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
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
    }
}

function setupEventListenersUsuarios() {
    $(document).on('click', '#btn-novo-usuario', function(e) {
        e.preventDefault();
        abrirModalUsuario(null);
    });
    
    $(document).on('click', '#btn-gerenciar-grupos', function(e) {
        e.preventDefault();
        $('#modal-grupos').modal('open');
    });
    
    $(document).on('click', '#btn-novo-grupo', function(e) {
        e.preventDefault();
        const section = $('#novo-grupo-section');
        if (section.is(':hidden')) {
            section.show();
            $(this).html('<i class="material-icons">remove</i> Cancelar');
            $(this).removeClass('green').addClass('red');
            
            // Preencher permissões se ainda não foi feito
            if ($('#novo-grupo-permissoes').is(':empty')) {
                renderizarPermissoesNovoGrupo();
            }
        } else {
            section.hide();
            $(this).html('<i class="material-icons">add</i> Novo Grupo');
            $(this).removeClass('red').addClass('green');
        }
    });
    
    $(document).on('click', '#modal-salvar-usuario', salvarUsuarioModal);
    $(document).on('click', '#btn-salvar-grupo', salvarGrupoModal);
    $(document).on('click', '#btn-criar-grupo', criarGrupo);
}

function setupEventListenersMinhaConta() {
    $(document).on('click', '#btn-minha-conta', function(e) {
        e.preventDefault();
        $('#modal-minha-conta').modal('open');
    });
    $(document).on('click', '#btn-salvar-minha-conta', salvarMinhaConta);
}

async function carregarListaUsuarios() {
    const tbody = $('#usuarios-table-body');
    tbody.html('<tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php`);
        const usuarios = await response.json();
        tbody.empty();

        if (usuarios.length === 0) {
            tbody.html('<tr><td colspan="4" style="text-align: center;">Nenhum usuário encontrado.</td></tr>');
            return;
        }

        usuarios.forEach(user => {
            const gruposDisplay = Array.isArray(user.grupos) && user.grupos.length > 0 
                ? user.grupos.map(g => `<span class="chip">${typeof g === 'string' ? g : g.nome}</span>`).join(' ') 
                : '<span style="color: #999;">-</span>';
            
            const podeEditar = EH_ADMIN_DEV || temPermissao('usuario.editar') || parseInt(user.id) === USUARIO_LOGADO_ID;
            let acoesHtml = podeEditar ? `
                <button class="btn-floating btn-small waves-effect waves-light blue" onclick="abrirModalUsuario(${user.id})">
                    <i class="material-icons">edit</i>
                </button>` : '<span style="color: #999; font-size: 12px;">Sem ação</span>';
            
            tbody.append(`
                <tr data-id="${user.id}">
                    <td>${user.nome}</td>
                    <td>${user.email}</td>
                    <td>${gruposDisplay}</td>
                    <td>${acoesHtml}</td>
                </tr>
            `);
        });
    } catch (error) {
        tbody.html(`<tr><td colspan="4" style="text-align: center; color: red;">Erro: ${error.message}</td></tr>`);
    }
}

async function abrirModalUsuario(id) {
    const form = $('#form-usuario');
    form[0].reset();
    
    const gruposSelect = $('#usuario_grupos');
    gruposSelect.html('<option value="" disabled>Selecione os grupos</option>');
    if (configDataGlobal.grupos) {
        configDataGlobal.grupos.forEach(g => {
            gruposSelect.append(`<option value="${g.id}">${g.nome}</option>`);
        });
    }
    
    if (id) {
        $('#modal-usuario-titulo').text('Editar Usuário');
        $('#usuario_id').val(id);
        $('#usuario_senha').prop('required', false);
        try {
            const res = await fetch(`${API_BASE_URL_PHP}/usuarios.php?id=${id}`);
            const user = await res.json();
            $('#usuario_nome').val(user.nome);
            $('#usuario_email').val(user.email);
            if (user.grupos) {
                $('#usuario_grupos').val(user.grupos.map(g => String(g.id || g)));
            }
        } catch (e) {}
    } else {
        $('#modal-usuario-titulo').text('Novo Usuário');
        $('#usuario_id').val('');
        $('#usuario_senha').prop('required', true);
    }
    
    M.updateTextFields();
    $('select').formSelect();
    $('#modal-usuario').modal('open');
}

async function salvarUsuarioModal() {
    const id = $('#usuario_id').val();
    const dados = {
        nome: $('#usuario_nome').val(),
        email: $('#usuario_email').val(),
        grupos: $('#usuario_grupos').val() || []
    };
    if ($('#usuario_senha').val()) dados.senha = $('#usuario_senha').val();
    if (id) dados.id = parseInt(id);
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        if (response.ok) {
            M.toast({html: 'Usuário salvo!', classes: 'green'});
            $('#modal-usuario').modal('close');
            carregarListaUsuarios();
        }
    } catch (e) {
        M.toast({html: 'Erro ao salvar', classes: 'red'});
    }
}

async function salvarMinhaConta() {
    const dados = {
        id: USUARIO_LOGADO_ID,
        nome: $('#minha_nome').val(),
        email: $('#minha_email').val(),
        senha_atual: $('#minha_senha_atual').val()
    };
    if ($('#minha_senha_nova').val()) dados.senha = $('#minha_senha_nova').val();
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        if (response.ok) {
            M.toast({html: 'Dados atualizados!', classes: 'green'});
            $('#modal-minha-conta').modal('close');
            $('#user-name').text(dados.nome);
        }
    } catch (e) {
        M.toast({html: 'Erro ao salvar', classes: 'red'});
    }
}

// -----------------------------------------------------
// NOTIFICAÇÕES
// -----------------------------------------------------

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
                    <td><span class="status-badge-small status-${n.status_slug || 'rascunho'}">${n.status || 'Rascunho'}</span></td>
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

let imagensParaDeletar = [];

function marcarParaDeletar(imageId) {
    const previewItem = document.getElementById(`img_${imageId}`);
    if (!previewItem) return;
    
    const jaMarcada = imagensParaDeletar.includes(imageId);
    if (jaMarcada) {
        imagensParaDeletar = imagensParaDeletar.filter(id => id !== imageId);
        previewItem.classList.remove('marcada-para-delecao');
    } else {
        imagensParaDeletar.push(imageId);
        previewItem.classList.add('marcada-para-delecao');
    }
}

async function salvarNotificacao() {
    console.log("salvarNotificacao: INICIADA");
    const dados = getFormData(false);
    
    if (typeof NOTIFICACAO_ID !== 'undefined' && NOTIFICACAO_ID) {
        dados.id = NOTIFICACAO_ID;
        dados.imagens_para_deletar = imagensParaDeletar;
        if (typeof notificationData !== 'undefined' && notificationData) {
            dados.status_id = notificationData.status_id;
        }
    }

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
            const novoId = result.id || (typeof NOTIFICACAO_ID !== 'undefined' ? NOTIFICACAO_ID : null);
            setTimeout(() => { 
                if (novoId) window.location.href = 'editar.php?id=' + novoId;
                else window.location.href = 'lista.php';
            }, 1500);
        } else {
            showStatus(`Erro ao salvar: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão: ${error.message}`, 'error');
    }
}

// -----------------------------------------------------
// CONFIGURAÇÕES / GRUPOS
// -----------------------------------------------------

async function carregarListaGrupos() {
    const lista = $('#grupos-lista');
    lista.html('<li class="collection-item">Carregando...</li>');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/grupos.php`);
        const grupos = await response.json();
        lista.empty();
        grupos.forEach(g => {
            lista.append(`
                <li class="collection-item">
                    <div>
                        <strong>${g.nome}</strong>
                        <div style="float: right;">
                            <button class="btn-small blue" onclick="editarGrupo(${g.id})"><i class="material-icons">edit</i></button>
                            <button class="btn-small red" onclick="deletarGrupo(${g.id})"><i class="material-icons">delete</i></button>
                        </div>
                    </div>
                </li>
            `);
        });
    } catch (error) {
        lista.html(`<li class="collection-item" style="color: red;">Erro: ${error.message}</li>`);
    }
}

async function editarGrupo(id) {
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/grupos.php?id=${id}`);
        const grupo = await res.json();
        
        console.log('Grupo carregado:', grupo);
        console.log('Permissões do grupo:', grupo.permissoes);
        console.log('configDataGlobal.permissoesPorModulo:', configDataGlobal.permissoesPorModulo);
        
        $('#grupo_id').val(grupo.id);
        $('#grupo_nome').val(grupo.nome);
        $('#grupo_desc').val(grupo.descricao);
        
        const container = $('#grupo-permissoes-container');
        container.empty();
        
        Object.keys(configDataGlobal.permissoesPorModulo).forEach(modulo => {
            const permissoes = configDataGlobal.permissoesPorModulo[modulo];
            const moduloHtml = $(`
                <div class="modulo-permissoes" data-modulo="${modulo}">
                    <h6>${modulo} <span class="select-all-modulo" data-modulo="${modulo}" style="font-size:12px; cursor:pointer; color:blue;">Selecionar todos</span></h6>
                    <div class="permissao-lista">
                        ${permissoes.map(p => {
                            const temPerm = grupo.permissoes && grupo.permissoes.includes(p.slug);
                            console.log(`Permissão ${p.slug}: tem=${temPerm}, grupoPermissoes=${JSON.stringify(grupo.permissoes)}`);
                            const checked = temPerm ? 'checked' : '';
                            return `<p><label><input type="checkbox" class="grupo-permissao-editar" value="${p.id}" ${checked}> <span>${p.nome}</span></label></p>`;
                        }).join('')}
                    </div>
                </div>
            `);
            container.append(moduloHtml);
        });
        
        M.updateTextFields();
        $('#modal-editar-grupo').modal('open');
    } catch (e) { console.error('Erro ao editar grupo:', e); }
}

async function salvarGrupoModal() {
    const permissoes = [];
    $('.grupo-permissao-editar:checked').each(function() { permissoes.push(parseInt($(this).val())); });
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/grupos.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: parseInt($('#grupo_id').val()),
                nome: $('#grupo_nome').val(),
                descricao: $('#grupo_desc').val(),
                permissoes: permissoes
            })
        });
        if (res.ok) {
            M.toast({html: 'Grupo salvo!', classes: 'green'});
            $('#modal-editar-grupo').modal('close');
            carregarListaGrupos();
        }
    } catch (e) {}
}

async function deletarGrupo(id) {
    if (!confirm('Deseja deletar o grupo?')) return;
    await fetch(`${API_BASE_URL_PHP}/grupos.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ deletar: true, id })
    });
    carregarListaGrupos();
}

async function criarGrupo() {
    const nome = $('#novo_grupo_nome').val().trim();
    const descricao = $('#novo_grupo_desc').val().trim();
    
    if (!nome) {
        M.toast({html: 'Digite o nome do grupo', classes: 'red'});
        return;
    }
    
    const permissoes = [];
    $('.novo-grupo-permissao:checked').each(function() {
        permissoes.push(parseInt($(this).val()));
    });
    
    try {
        const res = await fetch(`${API_BASE_URL_PHP}/grupos.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ criar_grupo: true, nome: nome, descricao: descricao, permissoes: permissoes })
        });
        
        if (res.ok) {
            M.toast({html: 'Grupo criado!', classes: 'green'});
            $('#novo_grupo_nome').val('');
            $('#novo_grupo_desc').val('');
            $('.novo-grupo-permissao').prop('checked', false);
            $('#novo-grupo-section').hide();
            $('#btn-novo-grupo').html('<i class="material-icons">add</i> Novo Grupo');
            $('#btn-novo-grupo').removeClass('red').addClass('green');
            carregarListaGrupos();
        } else {
            const err = await res.json();
            M.toast({html: 'Erro: ' + (err.message || 'Falha ao criar'), classes: 'red'});
        }
    } catch (e) {
        M.toast({html: 'Erro de conexão', classes: 'red'});
    }
}

function renderizarPermissoesNovoGrupo() {
    const container = $('#novo-grupo-permissoes');
    container.empty();
    
    Object.keys(configDataGlobal.permissoesPorModulo).forEach(modulo => {
        const permissoes = configDataGlobal.permissoesPorModulo[modulo];
        const moduloHtml = $(`
            <div class="modulo-permissoes" style="margin-bottom: 15px;">
                <h6 style="margin-bottom: 5px;">${modulo}</h6>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    ${permissoes.map(p => `
                        <label style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #f5f5f5; border-radius: 4px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" class="novo-grupo-permissao" value="${p.id}">
                            <span>${p.nome}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `);
        container.append(moduloHtml);
    });
}