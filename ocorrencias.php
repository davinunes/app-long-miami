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

$(document).ready(function() {
    $('.modal').modal();
    $('#btn-nova-ocorrencia').click(() => abrirModalOcorrencia());
    $('#form-ocorrencia').submit(function(e) {
        e.preventDefault();
        salvarOcorrencia();
    });
    $('#filtro-fase').change(carregarOcorrencias);
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
                        <a href="ocorrencia_detalhe.php?id=${o.id}" class="btn-floating btn-small blue">
                            <i class="material-icons">visibility</i>
                        </a>
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
