function inicializarGerenciadorUsuarios() {
    // 1. Inicializa o componente Modal do Materialize
    $('.modal').modal();
    
    // 2. Carrega a lista de usuários na tabela
    carregarListaUsuarios();
}

async function carregarListaUsuarios() {
    const tbody = $('#usuarios-table-body');
    tbody.html('<tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('accessToken')}` }
        });
        
        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.message || 'Erro ao buscar usuários.');
        }

        const usuarios = await response.json();
        tbody.empty(); // Limpa o "Carregando..."

        if (usuarios.length === 0) {
            tbody.html('<tr><td colspan="4" style="text-align: center;">Nenhum usuário encontrado.</td></tr>');
            return;
        }

        usuarios.forEach(user => {
            const row = `
                <tr>
                    <td>${user.nome}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>
                        <a href="#modal-usuario" class="btn-floating btn-small waves-effect waves-light blue btn-editar-usuario modal-trigger" data-id="${user.id}">
                            <i class="material-icons">edit</i>
                        </a>
                        </td>
                </tr>
            `;
            tbody.append(row);
        });

    } catch (error) {
        tbody.html(`<tr><td colspan="4" style="text-align: center; color: red;">${error.message}</td></tr>`);
    }
}

async function abrirModalUsuario(id) {
    const modal = $('#modal-usuario');
    const form = $('#form-usuario');
    
    form[0].reset(); // Limpa o formulário
    
    if (id) {
        // --- MODO EDIÇÃO ---
        $('#modal-usuario-titulo').text('Editar Usuário');
        $('#usuario_id').val(id); // Define o ID oculto
        $('#senha-helper-text').text('Deixe em branco para não alterar a senha.');
        $('#usuario_senha').prop('required', false); // Senha não é obrigatória na edição

        // Busca os dados do usuário específico
        try {
            const response = await fetch(`${API_BASE_URL_PHP}/usuarios.php?id=${id}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('accessToken')}` }
            });
            if (!response.ok) throw new Error('Não foi possível carregar os dados do usuário.');
            
            const user = await response.json();
            $('#usuario_nome').val(user.nome);
            $('#usuario_email').val(user.email);
            $('#usuario_role').val(user.role);
            
            M.updateTextFields(); // Atualiza os labels flutuantes do Materialize
            $('select').formSelect(); // Re-inicializa o select com o valor correto
            
        } catch (error) {
            alert(error.message);
            return;
        }

    } else {
        // --- MODO CRIAÇÃO ---
        $('#modal-usuario-titulo').text('Novo Usuário');
        $('#usuario_id').val(''); // Garante que o ID oculto está vazio
        $('#senha-helper-text').text('A senha é obrigatória para criar.');
        $('#usuario_senha').prop('required', true); // Senha é obrigatória na criação
    }
    
    M.updateTextFields();
    $('select').formSelect();
    
    // Abre o modal
    M.Modal.getInstance(modal).open();
}

async function salvarUsuarioModal() {
    const id = $('#usuario_id').val();
    const dados = {
        nome: $('#usuario_nome').val(),
        email: $('#usuario_email').val(),
        role: $('#usuario_role').val(),
        senha: $('#usuario_senha').val()
    };
    
    let url = `${API_BASE_URL_PHP}/usuarios.php`;
    
    if (id) {
        dados.id = id; // Adiciona o ID para a API saber que é um UPDATE
    }
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            },
            body: JSON.stringify(dados)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Erro ao salvar.');
        }

        alert(result.message); // Exibe "Usuário salvo com sucesso!"
        M.Modal.getInstance($('#modal-usuario')).close(); // Fecha o modal
        carregarListaUsuarios(); // Recarrega a tabela
        
    } catch (error) {
        alert(error.message); // Exibe o erro (ex: "Este email já existe")
    }
}

function carregarListaNotificacoes() {
    const tbody = document.getElementById('notifications-table-body');
    // Se o elemento da tabela não existir na página, não faz nada.
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>';

    const apiEndpoint = '/api/notificacoes.php';
    const accessToken = localStorage.getItem('accessToken');

    fetch(apiEndpoint, {
        headers: {
            'Authorization': `Bearer ${accessToken}`
        }
    })
    .then(response => {
        if (response.status === 401) {
            alert('Sessão expirada. Faça o login novamente.');
            window.location.replace('index.php');
            throw new Error('Sessão expirada.');
        }
        if (!response.ok) {
            throw new Error(`Erro na rede: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        tbody.innerHTML = ''; // Limpa a mensagem "Carregando..."
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Nenhuma notificação encontrada.</td></tr>';
            return;
        }

        data.forEach(n => {
            const dataEmissao = new Date(n.data_emissao + 'T00:00:00');
            const dataFormatada = dataEmissao.toLocaleDateString('pt-BR');

            // Adicionamos a classe 'ajax-link' para que a navegação continue via AJAX
            const row = `
                <tr>
                    <td>${n.numero}/${n.ano}</td>
                    <td>${n.bloco ? n.bloco : ''}${n.unidade}</td>
                    <td>${n.assunto}</td>
                    <td>${n.tipo}</td>
                    <td>${n.status}</td>
                    <td>${dataFormatada}</td>
                    <td>
                        <a href="editar.php?id=${n.id}" class="action-btn ajax-link">Detalhes / Editar</a>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(error => {
        console.error('Erro ao buscar notificações:', error);
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center;">Erro ao carregar dados: ${error.message}</td></tr>`;
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
    console.log("--- salvarNotificacao: INICIADA ---");
    const dados = await getFormData(false);
    console.log("--- salvarNotificacao: Dados recebidos do getFormData:", dados);

    // --- LOGS DA VALIDAÇÃO ---
    console.log("salvarNotificacao: Validando dados.numero:", dados.numero, `(!dados.numero = ${!dados.numero})`);
    console.log("salvarNotificacao: Validando dados.unidade:", dados.unidade, `(!dados.unidade = ${!dados.unidade})`);
    console.log("salvarNotificacao: Validando dados.assunto_id:", dados.assunto_id, `(!dados.assunto_id = ${!dados.assunto_id})`);
    // --- FIM DOS LOGS ---

    if (!dados.numero || !dados.unidade || !dados.assunto_id) {
        showStatus('Preencha os campos obrigatórios: Número, Unidade e Assunto.', 'error');
        console.error("--- salvarNotificacao: FALHA NA VALIDAÇÃO ---");
        return; 
    }

    console.log("--- salvarNotificacao: Validação APROVADA. Enviando para a API... ---");
    showStatus('Salvando notificação...', 'loading');
    
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
            setTimeout(() => carregarConteudo('lista.php'), 1500);
        } else {
            showStatus(`Erro ao salvar: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão com a API PHP: ${error.message}`, 'error');
    }
}

async function inicializarFormularioEdicao() {
    // --- PARTE 1: LER O ID DA NOTIFICAÇÃO DA URL HASH ---
    const hash = window.location.hash;
    const queryStringIndex = hash.indexOf('?');
    let notificacaoId = null;

    if (queryStringIndex !== -1) {
        const queryString = hash.substring(queryStringIndex + 1);
        const urlParams = new URLSearchParams(queryString);
        notificacaoId = urlParams.get('id');
    }

    if (!notificacaoId) {
        document.getElementById('main-content').innerHTML = "<h1>Erro: ID da notificação não fornecido na URL.</h1>";
        console.error("Não foi possível encontrar o 'id' na URL hash:", hash);
        return;
    }

    console.log(`✅ Iniciando formulário de edição para o ID: ${notificacaoId}`);

    // --- PARTE 2: LÓGICA PORTADA DO ANTIGO 'editar.js' ---

    // Variável para guardar IDs de imagens a serem deletadas
    let imagensParaDeletar = [];

    // Função para preencher o formulário com os dados da API
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
            
            // Lógica dos fatos
            const fatosContainer = document.getElementById('fatos-container');
            fatosContainer.innerHTML = ''; // Limpa antes de adicionar
            if (data.fatos && data.fatos.length > 0) {
                data.fatos.forEach(fatoDescricao => addFato(fatoDescricao));
            } else {
                addFato(); // Adiciona um campo vazio se não houver fatos
            }

            // Lógica das imagens existentes
            const previewContainer = document.getElementById('preview-container');
            if (data.imagens && data.imagens.length > 0) {
                data.imagens.forEach(img => {
                    const imageUrl = `/uploads/imagens/${img.caminho_arquivo}`;
                    const item = document.createElement('div');
                    item.className = 'img-preview-item existing-image';
                    item.id = `imagem-salva-${img.id}`;
                    item.innerHTML = `
                        <img src="${imageUrl}" alt="${img.nome_original}">
                        <small>Salva</small>
                        <button type="button" class="remove-btn-existing" onclick="marcarParaDeletar(${img.id})">&times;</button>
                    `;
                    previewContainer.appendChild(item);
                });
            }

            // Força a atualização do campo de multa, caso seja necessário
            toggleMultaField();
            console.log("✅ Formulário preenchido com sucesso.");
        } catch (error) {
            console.error("❌ Erro ao preencher o formulário:", error);
            showStatus("Ocorreu um erro ao exibir os dados no formulário.", "error");
        }
    }

    // Função para marcar/desmarcar imagens para deleção
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
    }

    // Função para enviar os dados atualizados para a API
    async function atualizarNotificacao() {
        const dados = getFormData(false); // getFormData deve estar em funcs.js
        dados.id = notificacaoId;
        dados.status_id = 1; // Você pode querer tornar isso dinâmico
        dados.imagens_para_deletar = imagensParaDeletar;

        showStatus('Atualizando notificação...', 'loading');

        try {
            const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
                method: 'POST', // O seu backend usa POST para criar e atualizar
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const result = await response.json();
            if (response.ok) {
                showStatus(result.message, 'success');
                // Após o sucesso, simula um clique no link da lista para voltar
                setTimeout(() => document.querySelector('a[href="lista.php"]').click(), 1500);
            } else {
                showStatus(`Erro: ${result.message}`, 'error');
            }
        } catch (error) {
            showStatus(`Erro de conexão: ${error.message}`, 'error');
        }
    }

    // --- PARTE 3: EXECUÇÃO E CONFIGURAÇÃO INICIAL ---

    try {
        // Configura o botão principal para a ação de ATUALIZAR
        const btnSalvar = document.getElementById('btnSalvar');
        btnSalvar.textContent = '💾 Atualizar Notificação';
        btnSalvar.onclick = atualizarNotificacao;

        // Mostra um status inicial
        showStatus('Carregando dados da notificação...', 'loading');

        // Busca os dados da notificação específica
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${notificacaoId}`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Notificação não encontrada.');
        }
        const data = await response.json();

        // Finalmente, preenche o formulário com os dados recebidos
        await fetchInitialData(); // Garante que os selects de tipo/assunto estejam prontos
        preencherFormulario(data);
        showStatus('Dados carregados. Pronto para edição.', 'success');

    } catch (error) {
        console.error("❌ Erro crítico ao carregar dados da notificação:", error);
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
            // setTimeout(() => carregarConteudo('lista.php'), 1500);
			setTimeout(() => carregarConteudo(`editar.php?id=${id}`), 1500);
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

    // Passo 3: O resto da lógica continua normalmente...
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
    // Apenas define o texto do botão. O listener de clique será gerenciado pelo jwt.js
    document.getElementById('btnSalvar').textContent = '💾 Atualizar Notificação';

    // O resto da função (fetch, preencherFormulario, etc.) continua igual...
    showStatus('Carregando dados para edição...', 'loading');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?id=${id}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            }
        });
        if (!response.ok) throw new Error('Falha ao buscar dados da notificação.');
        const data = await response.json();
        preencherFormulario(data);
        showStatus('Pronto para edição.', 'success');
    } catch (error) {
        showStatus(error.message, 'error');
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
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?proximo_numero=true`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            }
        });
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