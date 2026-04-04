<?php include 'header.php'; ?>

<div class="container">
    <div class="header">
        <h1>Ocorrências</h1>
        <p>Gerencie as ocorrências registradas no sistema.</p>
    </div>
    
    <div class="table-container">
        <div class="header-actions">
            <button class="btn-new modal-trigger" id="btn-nova-ocorrencia">+ Nova Ocorrência</button>
            <select id="filtro-fase" class="browser-default" style="margin-left: 10px; padding: 8px; border-radius: 4px;">
                <option value="">Todas as fases</option>
                <option value="nova">Nova</option>
                <option value="em_analise">Em Análise</option>
                <option value="recusada">Recusada</option>
                <option value="homologada">Homologada</option>
            </select>
        </div>
        
        <table class="striped highlight">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Unidades</th>
                    <th>Data Fato</th>
                    <th>Fase</th>
                    <th>Evidências</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="ocorrencias-table-body">
                <tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nova/Editar Ocorrência -->
<div id="modal-ocorrencia" class="modal modal-fixed-footer">
    <form id="form-ocorrencia">
        <div class="modal-content">
            <h4 id="modal-ocorrencia-titulo">Nova Ocorrência</h4>
            <input type="hidden" id="ocorrencia_id">
            
            <div class="row">
                <div class="input-field col s12">
                    <input id="ocorrencia_titulo" type="text" required>
                    <label for="ocorrencia_titulo">Título</label>
                </div>
            </div>
            
            <div class="row">
                <div class="input-field col s12 m6">
                    <input id="ocorrencia_data_fato" type="date" required>
                    <label for="ocorrencia_data_fato">Data do Fato</label>
                </div>
            </div>
            
            <div class="row">
                <div class="input-field col s12">
                    <textarea id="ocorrencia_descricao" class="materialize-textarea" required></textarea>
                    <label for="ocorrencia_descricao">Descrição do Fato</label>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12">
                    <label>Unidades Envolvidas</label>
                    <div id="unidades-selecionadas" style="margin: 10px 0;"></div>
                    <div class="row">
                        <div class="input-field col s3">
                            <select id="unidade_bloco" class="browser-default">
                                <option value="">Bloco</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                        <div class="input-field col s5">
                            <input id="unidade_numero" type="text" placeholder="Número">
                        </div>
                        <div class="input-field col s4">
                            <button type="button" class="btn" onclick="adicionarUnidade()">+ Adicionar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
            <button type="submit" class="waves-effect waves-green btn">Salvar</button>
        </div>
    </form>
</div>

<!-- Modal Detalhes da Ocorrência -->
<div id="modal-detalhes" class="modal modal-fixed-footer" style="width: 90%; max-width: 1000px;">
    <div class="modal-content">
        <h4 id="detalhes-titulo">Detalhes da Ocorrência</h4>
        
        <div class="row">
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title" id="detalhes-titulo-card">-</span>
                        <p id="detalhes-descricao">-</p>
                        <hr>
                        <p><strong>Data do Fato:</strong> <span id="detalhes-data-fato">-</span></p>
                        <p><strong>Unidades:</strong> <span id="detalhes-unidades">-</span></p>
                        <p><strong>Fase:</strong> <span id="detalhes-fase">-</span></p>
                        <p><strong>Criado por:</strong> <span id="detalhes-autor">-</span></p>
                    </div>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card">
                    <div class="card-content">
                        <h6>Mudar Fase</h6>
                        <select id="nova-fase" class="browser-default" style="margin-bottom: 10px;">
                            <option value="nova">Nova</option>
                            <option value="em_analise">Em Análise</option>
                            <option value="recusada">Recusada</option>
                            <option value="homologada">Homologada</option>
                        </select>
                        <input type="text" id="fase-observacao" placeholder="Observação (opcional)">
                        <button class="btn waves-effect waves-light" onclick="mudarFase()">Alterar Fase</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col s12">
                <ul class="tabs">
                    <li class="tab col s3"><a href="#tab-mensagens" class="active">Mensagens</a></li>
                    <li class="tab col s3"><a href="#tab-anexos">Anexos</a></li>
                    <li class="tab col s3"><a href="#tab-historico">Histórico</a></li>
                </ul>
            </div>
            <div id="tab-mensagens" class="col s12" style="padding: 20px 0;">
                <div id="mensagens-container" style="max-height: 300px; overflow-y: auto; margin-bottom: 15px;"></div>
                <div class="row">
                    <div class="input-field col s8">
                        <input id="nova-mensagem" type="text" placeholder="Digite uma mensagem...">
                    </div>
                    <div class="col s4">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" id="msg-eh-evidencia">
                            <span>É evidência</span>
                        </label>
                    </div>
                </div>
                <button class="btn waves-effect waves-light" onclick="enviarMensagem()">Enviar</button>
            </div>
            <div id="tab-anexos" class="col s12" style="padding: 20px 0;">
                <div id="anexos-container"></div>
                <div class="file-field input-field" style="margin-top: 20px;">
                    <div class="btn">
                        <span>Anexar</span>
                        <input type="file" id="anexo-file" accept="image/*,.pdf,.doc,.docx">
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Selecione um arquivo">
                    </div>
                </div>
                <button class="btn waves-effect waves-light" onclick="uploadAnexo()">Upload</button>
            </div>
            <div id="tab-historico" class="col s12" style="padding: 20px 0;">
                <ul id="historico-container" class="collection"></ul>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Fechar</a>
    </div>
</div>

<style>
.fase-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.fase-nova { background: #2196F3; color: white; }
.fase-em_analise { background: #FF9800; color: white; }
.fase-recusada { background: #F44336; color: white; }
.fase-homologada { background: #4CAF50; color: white; }

.unidade-chip {
    display: inline-block;
    background: #e0e0e0;
    padding: 4px 10px;
    border-radius: 16px;
    margin: 2px;
    font-size: 12px;
}
.unidade-chip button {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    margin-left: 5px;
    font-size: 14px;
}

.mensagem-item {
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #f5f5f5;
}
.mensagem-item.evidencia {
    background: #fff3e0;
    border-left: 4px solid #FF9800;
}
.mensagem-item .autor {
    font-weight: bold;
    font-size: 12px;
    color: #666;
}
.mensagem-item .hora {
    font-size: 10px;
    color: #999;
    float: right;
}
.mensagem-item.evidencia::before {
    content: "EVIDÊNCIA";
    font-size: 10px;
    background: #FF9800;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    margin-right: 5px;
}

.anexo-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 10px;
}
.anexo-item i {
    margin-right: 10px;
    font-size: 24px;
}
</style>

<script>
let ocorrenciasData = [];
let unidadesSelecionadas = [];
let ocorrenciaAtualId = null;

$(document).ready(function() {
    $('.modal').modal();
    $('.tabs').tabs();
    $('#btn-nova-ocorrencia').click(() => abrirModalOcorrencia());
    $('#form-ocorrencia').submit(function(e) {
        e.preventDefault();
        salvarOcorrencia();
    });
    $('#filtro-fase').change(carregarOcorrencias);
    $('#nova-mensagem').keypress(function(e) {
        if (e.which === 13) enviarMensagem();
    });
    carregarOcorrencias();
});

async function carregarOcorrencias() {
    const tbody = $('#ocorrencias-table-body');
    tbody.html('<tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>');
    
    const fase = $('#filtro-fase').val();
    let url = `${API_BASE_URL_PHP}/ocorrencias.php`;
    if (fase) url += `?fase=${fase}`;
    
    try {
        const response = await fetch(url, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('accessToken')}` }
        });
        if (!response.ok) throw new Error('Erro ao carregar ocorrências.');
        
        ocorrenciasData = await response.json();
        tbody.empty();
        
        if (ocorrenciasData.length === 0) {
            tbody.html('<tr><td colspan="7" style="text-align: center;">Nenhuma ocorrência encontrada.</td></tr>');
            return;
        }
        
        ocorrenciasData.forEach(o => {
            const faseClass = `fase-${o.fase}`;
            const faseLabel = o.fase.replace('_', ' ');
            const unidades = o.unidades || '-';
            
            tbody.append(`
                <tr>
                    <td>${o.id}</td>
                    <td>${o.titulo}</td>
                    <td>${unidades}</td>
                    <td>${formatDate(o.data_fato)}</td>
                    <td><span class="fase-badge ${faseClass}">${faseLabel}</span></td>
                    <td>${o.total_evidencias || 0}</td>
                    <td>
                        <button class="btn-floating btn-small blue" onclick="abrirDetalhes(${o.id})">
                            <i class="material-icons">visibility</i>
                        </button>
                    </td>
                </tr>
            `);
        });
        
    } catch (error) {
        tbody.html(`<tr><td colspan="7" style="color: red;">Erro: ${error.message}</td></tr>`);
    }
}

function abrirModalOcorrencia(id = null) {
    const modal = $('#modal-ocorrencia');
    $('#form-ocorrencia')[0].reset();
    unidadesSelecionadas = [];
    renderUnidadesSelecionadas();
    
    if (id) {
        $('#modal-ocorrencia-titulo').text('Editar Ocorrência');
        $('#ocorrencia_id').val(id);
        const occ = ocorrenciasData.find(o => o.id === id);
        if (occ) {
            $('#ocorrencia_titulo').val(occ.titulo);
            $('#ocorrencia_data_fato').val(occ.data_fato);
            $('#ocorrencia_descricao').val(occ.descricao_fato);
            if (occ.unidades && occ.unidades.length) {
                unidadesSelecionadas = occ.unidades.map(u => ({
                    bloco: u.unidade_bloco || '',
                    numero: u.unidade_numero || u
                }));
                renderUnidadesSelecionadas();
            }
        }
    } else {
        $('#modal-ocorrencia-titulo').text('Nova Ocorrência');
        $('#ocorrencia_id').val('');
        $('#ocorrencia_data_fato').val(new Date().toISOString().split('T')[0]);
    }
    
    M.updateTextFields();
    M.Modal.getInstance(modal).open();
}

function adicionarUnidade() {
    const bloco = $('#unidade_bloco').val();
    const numero = $('#unidade_numero').val().trim();
    
    if (!numero) {
        M.toast({html: 'Informe o número da unidade.', classes: 'red'});
        return;
    }
    
    unidadesSelecionadas.push({ bloco, numero });
    $('#unidade_numero').val('');
    $('#unidade_bloco').val('');
    renderUnidadesSelecionadas();
}

function removerUnidade(index) {
    unidadesSelecionadas.splice(index, 1);
    renderUnidadesSelecionadas();
}

function renderUnidadesSelecionadas() {
    const container = $('#unidades-selecionadas');
    if (unidadesSelecionadas.length === 0) {
        container.html('<span style="color: #999;">Nenhuma unidade adicionada</span>');
        return;
    }
    
    container.html(unidadesSelecionadas.map((u, i) => `
        <span class="unidade-chip">
            ${u.bloco || ''}${u.numero}
            <button onclick="removerUnidade(${i})">×</button>
        </span>
    `).join(''));
}

async function salvarOcorrencia() {
    const id = $('#ocorrencia_id').val();
    const titulo = $('#ocorrencia_titulo').val().trim();
    const dataFato = $('#ocorrencia_data_fato').val();
    const descricao = $('#ocorrencia_descricao').val().trim();
    
    if (!titulo || !dataFato || !descricao) {
        M.toast({html: 'Preencha todos os campos obrigatórios.', classes: 'red'});
        return;
    }
    
    const dados = {
        titulo,
        data_fato: dataFato,
        descricao_fato: descricao,
        unidades: unidadesSelecionadas.map(u => ({ bloco: u.bloco, numero: u.numero }))
    };
    
    if (id) dados.id = parseInt(id);
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            },
            body: JSON.stringify(dados)
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        
        M.toast({html: result.message, classes: 'green'});
        M.Modal.getInstance($('#modal-ocorrencia')).close();
        carregarOcorrencias();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function abrirDetalhes(id) {
    ocorrenciaAtualId = id;
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('accessToken')}` }
        });
        if (!response.ok) throw new Error('Erro ao carregar ocorrência.');
        
        const occ = await response.json();
        
        $('#detalhes-titulo').text(`Ocorrência #${occ.id}`);
        $('#detalhes-titulo-card').text(occ.titulo);
        $('#detalhes-descricao').text(occ.descricao_fato);
        $('#detalhes-data-fato').text(formatDate(occ.data_fato));
        $('#detalhes-unidades').text(occ.unidades?.map(u => `${u.unidade_bloco || ''}${u.unidade_numero}`).join(', ') || '-');
        $('#detalhes-fase').html(`<span class="fase-badge fase-${occ.fase}">${occ.fase.replace('_', ' ')}</span>`);
        $('#detalhes-autor').text(occ.autor_nome || '-');
        $('#nova-fase').val(occ.fase);
        
        renderMensagens(occ.mensagens || []);
        renderAnexos(occ.anexos || []);
        renderHistorico(occ.fase_log || []);
        
        M.Tabs.getInstance($('.tabs')[0]).select('tab-mensagens');
        M.Modal.getInstance($('#modal-detalhes')).open();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

function renderMensagens(mensagens) {
    const container = $('#mensagens-container');
    if (mensagens.length === 0) {
        container.html('<p style="color: #999; text-align: center;">Nenhuma mensagem.</p>');
        return;
    }
    
    container.html(mensagens.map(m => `
        <div class="mensagem-item ${m.eh_evidencia ? 'evidencia' : ''}">
            <span class="autor">${m.autor_nome}</span>
            <span class="hora">${formatDateTime(m.created_at)}</span>
            <p style="margin: 5px 0;">${m.mensagem}</p>
            ${m.anexo_url ? `<a href="${m.anexo_url}" target="_blank">Ver anexo</a>` : ''}
        </div>
    `).join(''));
    container.scrollTop(container[0].scrollHeight);
}

function renderAnexos(anexos) {
    const container = $('#anexos-container');
    if (anexos.length === 0) {
        container.html('<p style="color: #999; text-align: center;">Nenhum anexo.</p>');
        return;
    }
    
    container.html(anexos.map(a => `
        <div class="anexo-item">
            <i class="material-icons">${getIconeTipo(a.tipo)}</i>
            <div>
                <a href="${a.url}" target="_blank">${a.nome_original}</a>
                <small style="display: block; color: #999;">${formatFileSize(a.tamanho_bytes)}</small>
            </div>
        </div>
    `).join(''));
}

function renderHistorico(logs) {
    const container = $('#historico-container');
    if (logs.length === 0) {
        container.html('<li class="collection-item">Nenhum histórico.</li>');
        return;
    }
    
    container.html(logs.map(l => `
        <li class="collection-item">
            <strong>${l.fase_anterior || 'Início'}</strong> → <strong>${l.fase_nova}</strong>
            ${l.observacao ? `<p>${l.observacao}</p>` : ''}
            <small style="color: #999;">${formatDateTime(l.created_at)}</small>
        </li>
    `).join(''));
}

async function enviarMensagem() {
    const mensagem = $('#nova-mensagem').val().trim();
    if (!mensagem) return;
    
    const ehEvidencia = $('#msg-eh-evidencia').is(':checked');
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            },
            body: JSON.stringify({
                ocorrencia_id: ocorrenciaAtualId,
                mensagem,
                eh_evidencia: ehEvidencia
            })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        
        $('#nova-mensagem').val('');
        $('#msg-eh-evidencia').prop('checked', false);
        abrirDetalhes(ocorrenciaAtualId);
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

async function uploadAnexo() {
    const input = document.getElementById('anexo-file');
    if (!input.files[0]) {
        M.toast({html: 'Selecione um arquivo.', classes: 'red'});
        return;
    }
    
    const file = input.files[0];
    const reader = new FileReader();
    
    reader.onload = async function(e) {
        const dados = {
            ocorrencia_id: ocorrenciaAtualId,
            tipo: file.type.startsWith('image/') ? 'imagem' : 'documento',
            nome_original: file.name,
            dados: e.target.result.split(',')[1],
            mime_type: file.type,
            tamanho_bytes: file.size
        };
        
        try {
            const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php?upload=1`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
                },
                body: JSON.stringify(dados)
            });
            
            const result = await response.json();
            if (!response.ok) throw new Error(result.message);
            
            M.toast({html: 'Anexo enviado!', classes: 'green'});
            input.value = '';
            abrirDetalhes(ocorrenciaAtualId);
            
        } catch (error) {
            M.toast({html: error.message, classes: 'red'});
        }
    };
    
    reader.readAsDataURL(file);
}

async function mudarFase() {
    const novaFase = $('#nova-fase').val();
    const observacao = $('#fase-observacao').val().trim();
    
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/ocorrencias.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
            },
            body: JSON.stringify({
                mudar_fase: true,
                id: ocorrenciaAtualId,
                nova_fase: novaFase,
                observacao
            })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        
        M.toast({html: result.message, classes: 'green'});
        $('#fase-observacao').val('');
        abrirDetalhes(ocorrenciaAtualId);
        carregarOcorrencias();
        
    } catch (error) {
        M.toast({html: error.message, classes: 'red'});
    }
}

function formatDate(data) {
    if (!data) return '-';
    const d = new Date(data + 'T00:00:00');
    return d.toLocaleDateString('pt-BR');
}

function formatDateTime(data) {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

function formatFileSize(bytes) {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function getIconeTipo(tipo) {
    const icones = {
        imagem: 'image',
        video: 'movie',
        audio: 'audiotrack',
        documento: 'description',
        link: 'link'
    };
    return icones[tipo] || 'attach_file';
}
</script>

<?php include 'footer.php'; ?>
