<?php
require_once 'auth.php';
requireLogin();

$usuario = getUsuario();
$papeis = getPapeisUsuario();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-card {
            padding: 20px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .dashboard-card h5 {
            margin-top: 0;
            color: #333;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #1976D2;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
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
                <h1>Bem-vindo, <?php echo htmlspecialchars($usuario['nome']); ?>!</h1>
                <p>
                    <?php echo htmlspecialchars($usuario['role'] ?? 'sem role'); ?> • 
                    <?php echo count($usuario['permissoes'] ?? []); ?> permissões
                </p>
                <p>
                    <button onclick="$('#modal-minha-conta').modal('open')" class="btn-small blue">
                        <i class="material-icons left">person</i> Minha Conta
                    </button>
                    <a href="debug_permissoes.php" target="_blank" class="btn-small red" style="margin-left: 10px;">
                        [Debug Permissões]
                    </a>
                </p>
            </div>
            
            <div class="row">
                <?php if (temAlgumaPermissao(['notificacao.listar', 'notificacao.ver_detalhes', 'notificacao.criar']) || isAdmin()): ?>
                <div class="col s12 m4">
                    <div class="dashboard-card">
                        <h5><i class="material-icons left">notifications</i> Notificações</h5>
                        <p>Gerencie notificações e multas do condomínio.</p>
                        <a href="lista.php" class="btn blue">Acessar</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (temAlgumaPermissao(['ocorrencia.listar', 'ocorrencia.ver_detalhes', 'ocorrencia.criar']) || isAdmin()): ?>
                <div class="col s12 m4">
                    <div class="dashboard-card">
                        <h5><i class="material-icons left">report_problem</i> Ocorrências</h5>
                        <p>Registre e acompanhe ocorrências.</p>
                        <a href="ocorrencias.php" class="btn blue">Acessar</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                <div class="col s12 m4">
                    <div class="dashboard-card">
                        <h5><i class="material-icons left">people</i> Usuários</h5>
                        <p>Gerencie usuários e grupos.</p>
                        <a href="usuarios.php" class="btn blue">Acessar</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <h5><i class="material-icons left">info</i> Informações da Conta</h5>
                <table>
                    <tr>
                        <td><strong>ID:</strong></td>
                        <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nome:</strong></td>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td><?php echo htmlspecialchars($usuario['role'] ?? 'sem role'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Permissões:</strong></td>
                        <td><?php echo count($usuario['permissoes'] ?? []); ?></td>
                    </tr>
                    <tr>
                        <td><strong>É Admin/Dev:</strong></td>
                        <td><?php echo isAdmin() ? '✅ Sim' : '❌ Não'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Login em:</strong></td>
                        <td><?php echo htmlspecialchars($usuario['login_at']); ?></td>
                    </tr>
                </table>
                <h6 style="margin-top: 15px;">Suas Permissões:</h6>
                <div style="max-height: 200px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;">
                    <?php 
                    $perms = $usuario['permissoes'] ?? [];
                    if (empty($perms)) {
                        echo '<span style="color: red;">⚠️ Nenhuma permissão!</span>';
                    } else {
                        echo implode(', ', $perms);
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        const API_BASE_URL_PHP = window.location.origin + '/api';
        const USUARIO_ID = <?php echo $usuario['id']; ?>;
        
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            $('.modal').modal();
            
            $('#user-name').text('<?php echo htmlspecialchars($usuario['nome']); ?>');
            $('#user-email').text('<?php echo htmlspecialchars($usuario['email']); ?>');
        });
        
        function fazerLogout() {
            window.location.href = 'logout.php';
        }
        
        async function salvarMinhaConta() {
            const nome = $('#minha_nome').val().trim();
            const email = $('#minha_email').val().trim();
            const senhaNova = $('#minha_senha_nova').val();
            const senhaAtual = $('#minha_senha_atual').val();
            
            if (!nome || !email) {
                M.toast({html: 'Preencha nome e email.', classes: 'red'});
                return;
            }
            
            if (!senhaAtual) {
                M.toast({html: 'Informe a senha atual para salvar.', classes: 'red'});
                return;
            }
            
            const dados = {
                id: USUARIO_ID,
                nome: nome,
                email: email,
                senha_atual: senhaAtual
            };
            
            if (senhaNova) {
                dados.senha = senhaNova;
            }
            
            try {
                const response = await fetch(API_BASE_URL_PHP + '/usuarios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });
                
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || result.error);
                
                M.toast({html: 'Conta atualizada com sucesso!', classes: 'green'});
                M.Modal.getInstance($('#modal-minha-conta')).close();
                
                if (senhaNova) {
                    setTimeout(() => {
                        if (confirm('Senha alterada! Faça login novamente.')) {
                            window.location.href = 'logout.php';
                        }
                    }, 1000);
                } else {
                    location.reload();
                }
            } catch (error) {
                M.toast({html: error.message, classes: 'red'});
            }
        }
    </script>
    
    <!-- Modal Minha Conta -->
    <div id="modal-minha-conta" class="modal">
        <form id="form-minha-conta" onsubmit="return false;">
            <div class="modal-content">
                <h4>Minha Conta</h4>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="minha_nome" type="text" class="validate" required value="<?php echo htmlspecialchars($usuario['nome']); ?>">
                        <label for="minha_nome">Nome</label>
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="minha_email" type="email" class="validate" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
                        <label for="minha_email">Email</label>
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="minha_senha_nova" type="password">
                        <label for="minha_senha_nova">Nova Senha</label>
                        <span class="helper-text">Deixe em branco para manter a senha atual.</span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="minha_senha_atual" type="password" required>
                        <label for="minha_senha_atual">Senha Atual (obrigatório)</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
                <button type="button" class="waves-effect waves-green btn" onclick="salvarMinhaConta()">Salvar</button>
            </div>
        </form>
    </div>
</body>
</html>
