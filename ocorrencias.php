<?php
require_once 'auth.php';
requirePapel(['protocolar', 'diligente', 'promotor', 'admin', 'dev']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ocorrências - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
    </style>
</head>
<body>
    <header>
        <?php include '_partials/menu.php'; ?>
    </header>

    <a href="#" data-target="slide-out" class="sidenav-trigger mobile-menu-btn">
        <i class="material-icons">menu</i>
    </a>

    <main class="main-content">
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
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="ocorrencias-table-body">
                        <tr><td colspan="6" style="text-align: center;">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        const API_BASE_URL_PHP = window.location.origin + '/api';
        let ocorrenciasData = [];
        let unidadesSelecionadas = [];
        const isAdmin = <?php echo temPapel('admin') || temPapel('dev') ? 'true' : 'false'; ?>;

        $(document).ready(function() {
            $('.modal').modal();
            $('#btn-nova-ocorrencia').click(() => abrirModalOcorrencia());
            $('#form-ocorrencia').submit(function(e) {
                e.preventDefault();
                salvarOcorrencia();
            });
            $('#filtro-fase').change(carregarOcorrencias);
            carregarOcorrencias();
            
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
        });
        
        function fazerLogout() {
            window.location.href = 'logout.php';
        }

        async function carregarOcorrencias() {
            const tbody = $('#ocorrencias-table-body');
            tbody.html('<tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>');
            
            const fase = $('#filtro-fase').val();
            let url = API_BASE_URL_PHP + '/ocorrencias.php';
            if (fase) url += '?fase=' + fase;
            
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Erro ao carregar.');
                ocorrenciasData = await response.json();
                tbody.empty();
                
                if (ocorrenciasData.length === 0) {
                    tbody.html('<tr><td colspan="7" style="text-align: center;">Nenhuma ocorrência encontrada.</td></tr>');
                    return;
                }
                
                ocorrenciasData.forEach(o => {
                    const faseClass = 'fase-' + o.fase;
                    const faseLabel = o.fase.replace('_', ' ');
                    const unidades = o.unidades || '-';
                    const excluirBtn = isAdmin ? 
                        `<button class="btn-floating btn-small red" onclick="excluirOcorrencia(${o.id})" title="Excluir"><i class="material-icons">delete</i></button>` : '';
                    
                    tbody.append(`
                        <tr>
                            <td>${o.id}</td>
                            <td>${o.titulo}</td>
                            <td>${unidades}</td>
                            <td>${formatDate(o.data_fato)}</td>
                            <td><span class="fase-badge ${faseClass}">${faseLabel}</span></td>
                            <td>
                                <a href="ocorrencia_detalhe.php?id=${o.id}" class="btn-floating btn-small blue">
                                    <i class="material-icons">visibility</i>
                                </a>
                                ${excluirBtn}
                            </td>
                        </tr>
                    `);
                });
            } catch (error) {
                tbody.html('<tr><td colspan="7" style="color: red;">Erro: ' + error.message + '</td></tr>');
            }
        }

        async function excluirOcorrencia(id) {
            if (!confirm('Tem certeza que deseja excluir esta ocorrência? Esta ação não pode ser desfeita.')) return;
            
            try {
                const response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ excluir_ocorrencia: true, id: id })
                });
                
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                
                M.toast({html: result.message, classes: 'green'});
                carregarOcorrencias();
            } catch (error) {
                M.toast({html: error.message, classes: 'red'});
            }
        }

        function abrirModalOcorrencia(id) {
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
            
            container.html(unidadesSelecionadas.map((u, i) => 
                '<span class="unidade-chip">' + (u.bloco || '') + u.numero + ' <button onclick="removerUnidade(' + i + ')">×</button></span>'
            ).join(''));
        }

        async function salvarOcorrencia() {
            const id = $('#ocorrencia_id').val();
            const titulo = $('#ocorrencia_titulo').val().trim();
            const dataFato = $('#ocorrencia_data_fato').val();
            const descricao = $('#ocorrencia_descricao').val().trim();
            
            if (!titulo || !dataFato || !descricao) {
                M.toast({html: 'Preencha todos os campos.', classes: 'red'});
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
                const response = await fetch(API_BASE_URL_PHP + '/ocorrencias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
    </script>
</body>
</html>
