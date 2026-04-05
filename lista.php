<?php
require_once 'auth.php';
requireLogin();

$podeAcaoRapida = isAdmin() || temPermissao('notificacao.acao_rapida');
$podeListarLavradas = isAdmin() || temPermissao('notificacao.listar_lavradas');
$podeListarCobranca = isAdmin() || temPermissao('notificacao.listar_em_cobranca');
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
                <div class="table-container">
                    <div class="header-actions">
                        <a href="nova_not.php" class="btn-new">+ Criar Nova Notificação</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Unidade</th>
                                <th>Assunto</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Data de Emissão</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="notifications-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/funcs.js?v=<?php echo time(); ?>"></script>
    <script>
        const PODE_ACAO_RAPIDA = <?php echo $podeAcaoRapida ? 'true' : 'false'; ?>;
        const PODE_LISTAR_LAVRADAS = <?php echo $podeListarLavradas ? 'true' : 'false'; ?>;
        const PODE_LISTAR_COBRANCA = <?php echo $podeListarCobranca ? 'true' : 'false'; ?>;
        
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            carregarListaNotificacoes();
            
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
        });
        
        function fazerLogout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
