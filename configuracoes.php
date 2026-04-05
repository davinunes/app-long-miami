<?php
require_once 'auth.php';
requireLogin();

if (!isAdmin()) {
    header('Location: lista.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .config-section { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .config-section h4 { margin-top: 0; color: #333; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .config-row { display: flex; align-items: center; margin-bottom: 15px; gap: 15px; }
        .config-row label { min-width: 150px; font-weight: 500; }
        .config-row input, .config-row select { flex: 1; }
        .logo-preview { max-width: 200px; max-height: 100px; margin-top: 10px; }
        .sindico-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 10px; border-left: 4px solid #3498db; }
        .sindico-card.ativo { border-left-color: #27ae60; background: #e8f5e9; }
        .sindico-card.inativo { border-left-color: #95a5a6; opacity: 0.7; }
        .tabs-container { margin-bottom: 20px; }
    </style>
</head>
<body>
    <header>
        <?php include '_partials/menu.php'; ?>
    </header>

    <a href="#" class="custom-sidenav-toggle mobile-menu-btn">
        <i class="material-icons">menu</i>
    </a>

    <main class="main-content">
        <div class="container">
            <div class="header">
                <a href="lista.php" class="back-link"><i class="material-icons">arrow_back</i> Voltar</a>
                <h1>Configurações</h1>
            </div>

            <div class="tabs-container">
                <ul class="tabs" style="display: flex; gap: 10px; border-bottom: none;">
                    <li><a href="#tab-condominio" class="btn teal" onclick="showTab('condominio')">Condomínio</a></li>
                    <li><a href="#tab-sistema" class="btn grey" onclick="showTab('sistema')">Sistema</a></li>
                    <li><a href="#tab-sindicos" class="btn grey" onclick="showTab('sindicos')">Síndicos</a></li>
                </ul>
            </div>

            <div id="tab-condominio" class="config-section">
                <h4><i class="material-icons">business</i> Dados do Condomínio</h4>
                
                <div class="config-row">
                    <label for="condominio_nome">Nome do Condomínio:</label>
                    <input type="text" id="condominio_nome" placeholder="Nome do condomínio">
                </div>
                
                <div class="config-row">
                    <label for="condominio_cnpj">CNPJ:</label>
                    <input type="text" id="condominio_cnpj" placeholder="00.000.000/0000-00">
                </div>
                
                <div class="config-row">
                    <label for="condominio_logo">Logo:</label>
                    <div style="flex: 1;">
                        <input type="file" id="logo_file" accept="image/*">
                        <img id="logo_preview" class="logo-preview" src="" style="display: none;">
                    </div>
                </div>
                
                <button type="button" class="btn blue" onclick="salvarCondominio()">
                    <i class="material-icons">save</i> Salvar
                </button>
            </div>

            <div id="tab-sistema" class="config-section" style="display: none;">
                <h4><i class="material-icons">settings</i> Configurações do Sistema</h4>
                
                <div class="config-row">
                    <label for="ultimo_numero_notificacao">Último Número de Notificação:</label>
                    <input type="number" id="ultimo_numero_notificacao" placeholder="Ex: 100" min="0">
                    <button type="button" class="btn blue" onclick="salvarUltimoNumero()">
                        <i class="material-icons">save</i> Salvar
                    </button>
                </div>
                <p style="color: #666; font-size: 12px; margin-top: -10px;">Define o número inicial para geração de notificações. O próximo será este + 1.</p>
                
                <div class="config-row" style="margin-top: 20px;">
                    <label for="url_recurso_default">URL para Recurso (padrão):</label>
                    <input type="text" id="url_recurso_default" placeholder="URL para recursos">
                    <button type="button" class="btn blue" onclick="salvarUrlRecurso()">
                        <i class="material-icons">save</i> Salvar
                    </button>
                </div>
                
                <h5 style="margin-top: 30px;"><i class="material-icons">menu_book</i> Regimento Interno</h5>
                <p style="color: #666; margin-bottom: 15px;">Faça upload do arquivo JSON com o regimento interno do condomínio.</p>
                
                <div class="config-row">
                    <label for="regimento_file">Arquivo JSON:</label>
                    <div style="flex: 1;">
                        <input type="file" id="regimento_file" accept=".json">
                        <small style="color: #666;">Arquivo atual: <span id="regimento_status">Carregando...</span></small>
                    </div>
                </div>
                
                <button type="button" class="btn orange" onclick="uploadRegimento()">
                    <i class="material-icons">upload</i> Atualizar Regimento
                </button>

                <h5 style="margin-top: 30px;"><i class="material-icons">edit</i> Editor de Texto Avançado (TinyMCE)</h5>
                <p style="color: #666; margin-bottom: 15px;">Habilite o editor rich text para os campos de texto. Desativado usa textarea padrão.</p>
                
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="tinymce_notificacao_fatos" onchange="salvarTinyMCESetting('notificacao_fatos', this.checked)">
                        <span>Notificação - Fatos</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="tinymce_notificacao_fundamentacao" onchange="salvarTinyMCESetting('notificacao_fundamentacao', this.checked)">
                        <span>Notificação - Fundamentação Legal</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="tinymce_ocorrencia_mensagem" onchange="salvarTinyMCESetting('ocorrencia_mensagem', this.checked)">
                        <span>Ocorrência - Mensagens</span>
                    </label>
                </div>
            </div>

            <div id="tab-sindicos" class="config-section" style="display: none;">
                <h4>
                    <i class="material-icons">person</i> Síndicos
                    <button type="button" class="btn-small green right" onclick="abrirModalSindico()">
                        <i class="material-icons">add</i> Novo Síndico
                    </button>
                </h4>
                
                <div id="sindicos_lista"></div>
            </div>
        </div>
    </main>

    <div id="modal_sindico" class="modal">
        <div class="modal-content">
            <h4 id="modal_sindico_titulo">Novo Síndico</h4>
            <input type="hidden" id="sindico_id">
            <div class="input-field">
                <input type="text" id="sindico_nome" required>
                <label for="sindico_nome">Nome Completo *</label>
            </div>
            <div class="input-field">
                <input type="text" id="sindico_cpf" placeholder="000.000.000-00">
                <label for="sindico_cpf">CPF</label>
            </div>
            <div class="input-field">
                <input type="email" id="sindico_email">
                <label for="sindico_email">E-mail</label>
            </div>
            <div class="input-field">
                <input type="text" id="sindico_telefone" placeholder="(00) 00000-0000">
                <label for="sindico_telefone">Telefone</label>
            </div>
            <div class="input-field">
                <input type="date" id="sindico_data_inicio" required>
                <label for="sindico_data_inicio">Data de Início *</label>
            </div>
            <div class="input-field">
                <input type="date" id="sindico_data_fim">
                <label for="sindico_data_fim">Data de Término</label>
            </div>
            <div class="input-field">
                <textarea id="sindico_observacoes" class="materialize-textarea"></textarea>
                <label for="sindico_observacoes">Observações</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn grey modal-close">Cancelar</button>
            <button type="button" class="btn blue" onclick="salvarSindico()">Salvar</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        const API_BASE = window.location.origin + '/api';
        
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            $('.modal').modal();
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
            carregarConfiguracoes();
            carregarSindicos();
        });

        function showTab(tab) {
            document.querySelectorAll('.config-section').forEach(el => el.style.display = 'none');
            document.getElementById('tab-' + tab).style.display = 'block';
        }

        async function carregarConfiguracoes() {
            try {
                const response = await fetch(API_BASE + '/configuracoes.php');
                const configs = await response.json();
                
                configs.forEach(config => {
                    const el = document.getElementById(config.chave);
                    if (el) el.value = config.valor || '';
                    
                    if (config.chave === 'condominio_logo' && config.valor) {
                        document.getElementById('logo_preview').src = '/' + config.valor;
                        document.getElementById('logo_preview').style.display = 'block';
                    }
                    
                    if (config.chave === 'regimento_json') {
                        document.getElementById('regimento_status').textContent = config.valor ? 'Carregado' : 'Não configurado';
                    }
                    
                    // TinyMCE settings
                    if (config.chave.startsWith('tinymce_')) {
                        const checkbox = document.getElementById(config.chave);
                        if (checkbox) {
                            checkbox.checked = config.valor === '1';
                        }
                    }
                });
            } catch (error) {
                console.error('Erro ao carregar configurações:', error);
                M.toast({html: 'Erro ao carregar configurações', classes: 'red'});
            }
        }

        async function salvarCondominio() {
            const nome = document.getElementById('condominio_nome').value;
            const cnpj = document.getElementById('condominio_cnpj').value;
            
            try {
                let logoPath = null;
                const logoFile = document.getElementById('logo_file').files[0];
                if (logoFile) {
                    const formData = new FormData();
                    formData.append('action', 'upload_logo');
                    formData.append('logo', logoFile);
                    const logoResponse = await fetch(API_BASE + '/configuracoes.php', {
                        method: 'POST',
                        body: formData
                    });
                    const logoResult = await logoResponse.json();
                    if (logoResult.success) {
                        logoPath = logoResult.path;
                        document.getElementById('logo_preview').src = logoPath;
                        document.getElementById('logo_preview').style.display = 'block';
                    }
                }
                
                await Promise.all([
                    fetch(API_BASE + '/configuracoes.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'salvar_config', chave: 'condominio_nome', valor: nome})
                    }),
                    fetch(API_BASE + '/configuracoes.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'salvar_config', chave: 'condominio_cnpj', valor: cnpj})
                    })
                ]);
                
                M.toast({html: 'Configurações do condomínio salvas!', classes: 'green'});
            } catch (error) {
                console.error('Erro:', error);
                M.toast({html: 'Erro ao salvar configurações', classes: 'red'});
            }
        }

        async function uploadRegimento() {
            const file = document.getElementById('regimento_file').files[0];
            if (!file) {
                M.toast({html: 'Selecione um arquivo JSON', classes: 'orange'});
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'upload_regimento');
                formData.append('regimento', file);
                
                const response = await fetch(API_BASE + '/configuracoes.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('regimento_status').textContent = 'Carregado';
                    M.toast({html: 'Regimento atualizado com sucesso!', classes: 'green'});
                } else {
                    M.toast({html: result.error || 'Erro ao atualizar regimento', classes: 'red'});
                }
            } catch (error) {
                console.error('Erro:', error);
                M.toast({html: 'Erro ao enviar regimento', classes: 'red'});
            }
        }

        async function salvarUltimoNumero() {
            const numero = document.getElementById('ultimo_numero_notificacao').value;
            
            try {
                const response = await fetch(API_BASE + '/configuracoes.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'salvar_config', chave: 'ultimo_numero_notificacao', valor: numero})
                });
                const result = await response.json();
                
                if (result.success) {
                    M.toast({html: 'Número salvo! Próxima notificação será: ' + (parseInt(numero) + 1), classes: 'green'});
                } else {
                    M.toast({html: result.error || 'Erro ao salvar número', classes: 'red'});
                }
            } catch (error) {
                console.error('Erro:', error);
                M.toast({html: 'Erro ao salvar número', classes: 'red'});
            }
        }

        async function salvarUrlRecurso() {
            const url = document.getElementById('url_recurso_default').value;
            
            try {
                const response = await fetch(API_BASE + '/configuracoes.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'salvar_config', chave: 'url_recurso_default', valor: url})
                });
                const result = await response.json();
                
                if (result.success) {
                    M.toast({html: 'URL para recurso salva!', classes: 'green'});
                } else {
                    M.toast({html: result.error || 'Erro ao salvar URL', classes: 'red'});
                }
            } catch (error) {
                console.error('Erro:', error);
                M.toast({html: 'Erro ao salvar URL', classes: 'red'});
            }
        }

        async function salvarTinyMCESetting(chave, valor) {
            try {
                const response = await fetch(API_BASE + '/configuracoes.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'salvar_config', chave: 'tinymce_' + chave, valor: valor ? '1' : '0'})
                });
                const result = await response.json();
                
                if (result.success) {
                    M.toast({html: 'Configuração do editor salva!', classes: 'green'});
                } else {
                    M.toast({html: result.error || 'Erro ao salvar', classes: 'red'});
                }
            } catch (error) {
                console.error('Erro:', error);
                M.toast({html: 'Erro ao salvar configuração', classes: 'red'});
            }
        }

        async function carregarSindicos() {
            try {
                const response = await fetch(API_BASE + '/sindicos.php');
                const sindicos = await response.json();
                
                const container = document.getElementById('sindicos_lista');
                if (sindicos.length === 0) {
                    container.innerHTML = '<p style="color: #666;">Nenhum síndico cadastrado.</p>';
                    return;
                }
                
                container.innerHTML = sindicos.map(s => `
                    <div class="sindico-card ${s.ativo ? 'ativo' : 'inativo'}">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong>${s.nome}</strong>
                                ${s.ativo ? '<span class="badge green white-text" style="margin-left: 10px; padding: 2px 8px; border-radius: 10px; font-size: 11px;">Atual</span>' : ''}
                                <br>
                                <small style="color: #666;">
                                    Período: ${formatDate(s.data_inicio)} ${s.data_fim ? 'a ' + formatDate(s.data_fim) : ' - Atual'}
                                </small>
                                ${s.email ? '<br><small>E-mail: ' + s.email + '</small>' : ''}
                                ${s.telefone ? '<br><small>Tel: ' + s.telefone + '</small>' : ''}
                            </div>
                            <div>
                                ${!s.ativo ? '<button class="btn-small green" onclick="ativarSindico(' + s.id + ')"><i class="material-icons">check</i></button>' : ''}
                                <button class="btn-small blue" onclick="editarSindico(' + s.id + ', \'' + s.nome + '\', \'' + (s.cpf || '') + '\', \'' + (s.email || '') + '\', \'' + (s.telefone || '') + '\', \'' + s.data_inicio + '\', \'' + (s.data_fim || '') + '\', \'' + (s.observacoes || '') + '\')"><i class="material-icons">edit</i></button>
                                <button class="btn-small red" onclick="excluirSindico(' + s.id + ')"><i class="material-icons">delete</i></button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Erro ao carregar síndicos:', error);
            }
        }

        function abrirModalSindico() {
            document.getElementById('modal_sindico_titulo').textContent = 'Novo Síndico';
            document.getElementById('sindico_id').value = '';
            document.getElementById('sindico_nome').value = '';
            document.getElementById('sindico_cpf').value = '';
            document.getElementById('sindico_email').value = '';
            document.getElementById('sindico_telefone').value = '';
            document.getElementById('sindico_data_inicio').value = '';
            document.getElementById('sindico_data_fim').value = '';
            document.getElementById('sindico_observacoes').value = '';
            M.updateTextFields();
            M.modal.getInstance(document.getElementById('modal_sindico')).open();
        }

        function editarSindico(id, nome, cpf, email, telefone, data_inicio, data_fim, observacoes) {
            document.getElementById('modal_sindico_titulo').textContent = 'Editar Síndico';
            document.getElementById('sindico_id').value = id;
            document.getElementById('sindico_nome').value = nome;
            document.getElementById('sindico_cpf').value = cpf;
            document.getElementById('sindico_email').value = email;
            document.getElementById('sindico_telefone').value = telefone;
            document.getElementById('sindico_data_inicio').value = data_inicio;
            document.getElementById('sindico_data_fim').value = data_fim;
            document.getElementById('sindico_observacoes').value = observacoes;
            M.updateTextFields();
            M.modal.getInstance(document.getElementById('modal_sindico')).open();
        }

        async function salvarSindico() {
            const id = document.getElementById('sindico_id').value;
            const dados = {
                nome: document.getElementById('sindico_nome').value,
                cpf: document.getElementById('sindico_cpf').value,
                email: document.getElementById('sindico_email').value,
                telefone: document.getElementById('sindico_telefone').value,
                data_inicio: document.getElementById('sindico_data_inicio').value,
                data_fim: document.getElementById('sindico_data_fim').value || null,
                observacoes: document.getElementById('sindico_observacoes').value
            };
            
            if (!dados.nome || !dados.data_inicio) {
                M.toast({html: 'Preencha os campos obrigatórios', classes: 'orange'});
                return;
            }
            
            try {
                const method = id ? 'PUT' : 'POST';
                const url = id ? API_BASE + '/sindicos.php' : API_BASE + '/sindicos.php';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(dados)
                });
                const result = await response.json();
                
                if (result.success) {
                    M.toast({html: result.message, classes: 'green'});
                    M.modal.getInstance(document.getElementById('modal_sindico')).close();
                    carregarSindicos();
                } else {
                    M.toast({html: result.error || 'Erro ao salvar', classes: 'red'});
                }
            } catch (error) {
                console.error('Erro:', error);
                M.toast({html: 'Erro ao salvar síndico', classes: 'red'});
            }
        }

        async function ativarSindico(id) {
            if (!confirm('Ativar este síndico como o atual? Os demais serão desativados.')) return;
            
            try {
                const response = await fetch(API_BASE + '/sindicos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id, ativar: true})
                });
                const result = await response.json();
                
                if (result.success) {
                    M.toast({html: result.message, classes: 'green'});
                    carregarSindicos();
                } else {
                    M.toast({html: result.error || 'Erro', classes: 'red'});
                }
            } catch (error) {
                M.toast({html: 'Erro ao ativar síndico', classes: 'red'});
            }
        }

        async function excluirSindico(id) {
            if (!confirm('Excluir este síndico?')) return;
            
            try {
                const response = await fetch(API_BASE + '/sindicos.php?id=' + id, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    M.toast({html: result.message, classes: 'green'});
                    carregarSindicos();
                } else {
                    M.toast({html: result.error || 'Erro', classes: 'red'});
                }
            } catch (error) {
                M.toast({html: 'Erro ao excluir síndico', classes: 'red'});
            }
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }
    </script>
</body>
</html>
