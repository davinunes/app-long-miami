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
    <div id="modal-quick-edit" class="modal">
        <div class="modal-content" style="padding-bottom: 20px;">
            <h4 id="modal-qe-titulo">Editar Notificação</h4>
            <input type="hidden" id="qe-id">
            
            <div style="margin-bottom: 25px;">
                <label style="font-size: 14px; color: #666; margin-bottom: 8px; display: block;">Data de Envio</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="datetime-local" id="qe-data-envio" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="button" class="btn-small blue" onclick="salvarCampoUnico('data_envio')">
                        <i class="material-icons" style="font-size: 16px;">save</i>
                    </button>
                </div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="font-size: 14px; color: #666; margin-bottom: 8px; display: block;">Data da Ciência</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="datetime-local" id="qe-data-ciencia" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="button" class="btn-small blue" onclick="salvarCampoUnico('data_ciencia')">
                        <i class="material-icons" style="font-size: 16px;">save</i>
                    </button>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <label style="font-size: 14px; color: #666; margin-bottom: 8px; display: block;">Status do Recurso</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <select id="qe-recurso-status" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; display: block;">
                        <option value="">Selecione...</option>
                        <option value="pendente">Recurso Pendente</option>
                        <option value="deferido">Recurso Deferido</option>
                        <option value="indeferido">Recurso Indeferido</option>
                    </select>
                    <button type="button" class="btn-small blue" onclick="salvarCampoUnico('recurso_status')">
                        <i class="material-icons" style="font-size: 16px;">save</i>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: space-between; padding: 15px 20px;">
            <button type="button" class="btn red" id="btn-qe-excluir" onclick="excluirNotificacao()">
                <i class="material-icons left">delete</i> Excluir
            </button>
            <a href="#!" class="modal-close btn-flat">Fechar</a>
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
