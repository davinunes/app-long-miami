<?php
require_once 'auth.php';
requireLogin();

$podeAcaoRapida = isAdmin() || temPermissao('notificacao.acao_rapida');
$podeEditarDatas = isAdmin() || temPermissao('notificacao.editar_datas');
$podeListarLavradas = isAdmin() || temPermissao('notificacao.listar_lavradas');
$podeListarEnviadas = isAdmin() || temPermissao('notificacao.listar_enviadas');
$podeListarCobranca = isAdmin() || temPermissao('notificacao.listar_em_cobranca');
$podeJulgarRecurso = isAdmin() || temPermissao('notificacao.julgar_recurso');
$podeRegistrarRecurso = isAdmin() || temPermissao('notificacao.registrar_recurso');
$podeExcluir = isAdmin() || temPermissao('notificacao.excluir');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .notificacoes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 16px;
        }
        
        .notificacao-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .notificacao-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .notificacao-card.status-lavrada { border-left-color: #2196f3; }
        .notificacao-card.status-enviada { border-left-color: #4caf50; }
        .notificacao-card.status-ciente { border-left-color: #9c27b0; }
        .notificacao-card.status-cobranca { border-left-color: #ff9800; }
        .notificacao-card.status-encerrada { border-left-color: #9e9e9e; }
        
        .notificacao-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .notificacao-numero {
            font-weight: bold;
            font-size: 1.1rem;
            color: #333;
        }
        
        .notificacao-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-lavrada { background: #e3f2fd; color: #1976d2; }
        .status-enviada { background: #e8f5e9; color: #388e3c; }
        .status-ciente { background: #f3e5f5; color: #7b1fa2; }
        .status-cobranca { background: #fff3e0; color: #f57c00; }
        .status-encerrada { background: #eceff1; color: #546e7a; }
        
        .notificacao-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        
        .notificacao-info span {
            color: #666;
        }
        
        .notificacao-info strong {
            color: #333;
        }
        
        .notificacao-assunto {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .notificacao-acoes {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 600px) {
            .notificacoes-grid {
                grid-template-columns: 1fr;
            }
            
            .notificacao-card {
                padding: 14px;
            }
        }
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
            <div class="page-container">
                <div class="header">
                    <h1>Lista de Notificações</h1>
                    <p>Visualize e gerencie as notificações registradas.</p>
                </div>
                <div class="header-actions">
                    <a href="nova_not.php" class="btn-new">+ Criar Nova Notificação</a>
                </div>
                <div id="notifications-grid" class="notificacoes-grid"></div>
            </div>
        </div>
    </main>

    <!-- Modal Quick Edit -->
    <div id="modal-quick-edit" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4 id="modal-qe-titulo">Editar Notificação</h4>
            <input type="hidden" id="qe-id">
            
            <div class="row">
                <div class="col s12 m6">
                    <div class="input-field">
                        <input type="datetime-local" id="qe-data-envio">
                        <label for="qe-data-envio">Data de Envio</label>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="input-field">
                        <input type="datetime-local" id="qe-data-ciencia">
                        <label for="qe-data-ciencia">Data da Ciência</label>
                    </div>
                </div>
            </div>
            
            <div class="row qe-recurso-row">
                <div class="col s12">
                    <div class="input-field">
                        <select id="qe-recurso-status">
                            <option value="">Selecione...</option>
                            <option value="pendente">Recurso Pendente</option>
                            <option value="deferido">Recurso Deferido</option>
                            <option value="indeferido">Recurso Indeferido</option>
                        </select>
                        <label>Status do Recurso</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: space-between;">
            <button type="button" class="btn red" id="btn-qe-excluir" onclick="excluirNotificacao()">
                <i class="material-icons left">delete</i> Excluir
            </button>
            <div>
                <a href="#!" class="modal-close btn-flat">Cancelar</a>
                <button type="button" class="btn" id="btn-qe-salvar">Salvar</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/funcs.js?v=<?php echo time(); ?>"></script>
    <script>
        const PODE_ACAO_RAPIDA = <?php echo $podeAcaoRapida ? 'true' : 'false'; ?>;
        const PODE_EDITAR_DATAS = <?php echo $podeEditarDatas ? 'true' : 'false'; ?>;
        const PODE_LISTAR_LAVRADAS = <?php echo $podeListarLavradas ? 'true' : 'false'; ?>;
        const PODE_LISTAR_ENVIADAS = <?php echo $podeListarEnviadas ? 'true' : 'false'; ?>;
        const PODE_LISTAR_COBRANCA = <?php echo $podeListarCobranca ? 'true' : 'false'; ?>;
        const PODE_JULGAR_RECURSO = <?php echo $podeJulgarRecurso ? 'true' : 'false'; ?>;
        const PODE_EXCLUIR = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
        
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            $('.modal').modal();
            carregarListaNotificacoes();
            
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
            
            $('#btn-qe-salvar').on('click', salvarQuickEdit);
            
            // Mostrar/ocultar campo de recurso baseado na permissão
            if (!PODE_JULGAR_RECURSO && !EH_ADMIN_DEV) {
                $('.qe-recurso-row').hide();
            }
            
            // Mostrar/ocultar botão excluir baseado na permissão
            if (!PODE_EXCLUIR && !EH_ADMIN_DEV) {
                $('#btn-qe-excluir').hide();
            }
        });
        
        function fazerLogout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
