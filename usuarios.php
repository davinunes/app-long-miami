<?php
require_once 'auth.php';
requireLogin();

$usuarioLogado = getUsuario();
$permissoesUsuario = getPermissoesUsuario();
$isAdminOrDev = in_array('dev', $usuarioLogado['papeis'] ?? []) || in_array('admin', $usuarioLogado['papeis'] ?? []) || $usuarioLogado['role'] === 'dev' || $usuarioLogado['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - App Long Miami</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chip {
            display: inline-block;
            padding: 0 8px;
            height: 24px;
            font-size: 12px;
            line-height: 24px;
            border-radius: 16px;
            background-color: #e0e0e0;
            margin: 2px;
        }
        .usuario-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .usuario-info-card h4 {
            margin: 0 0 10px 0;
        }
        .permissao-badge {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin: 2px;
            display: inline-block;
        }
        .no-permissions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            <div class="header">
                <h1>Gerenciar Usuários</h1>
                <p id="page-description">Gerencie os usuários e grupos do sistema.</p>
            </div>

            <!-- Card de Informações do Usuário -->
            <div class="usuario-info-card" id="usuario-info-card">
                <h4 id="info-nome"><?php echo htmlspecialchars($usuarioLogado['nome']); ?></h4>
                <p id="info-email"><?php echo htmlspecialchars($usuarioLogado['email']); ?></p>
                <div id="info-permissoes">
                    <?php 
                    if ($isAdminOrDev) {
                        echo '<span class="permissao-badge" style="background: #27ae60;">Administrador</span>';
                    } else {
                        $permCount = count($permissoesUsuario);
                        echo '<span class="permissao-badge">' . $permCount . ' permissão(ões)</span>';
                        if ($permCount > 0 && $permCount <= 5) {
                            foreach (array_slice($permissoesUsuario, 0, 5) as $p) {
                                echo '<span class="permissao-badge" title="' . htmlspecialchars($p) . '">' . htmlspecialchars(substr($p, 0, 20)) . '</span>';
                            }
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Aviso para usuários sem permissões -->
            <?php if (!$isAdminOrDev && count($permissoesUsuario) === 0): ?>
            <div class="no-permissions">
                <h5><i class="material-icons">info</i> Você possui acesso limitado</h5>
                <p>Você não possui permissões de gerenciamento. Utilize a seção "Minha Conta" abaixo para editar seus dados e alterar sua senha.</p>
            </div>
            <?php endif; ?>

            <!-- Botões de ação -->
            <div class="table-container">
                <div class="header-actions" id="header-actions">
                    <button class="btn-new modal-trigger" id="btn-novo-usuario" style="display: none;">+ Novo Usuário</button>
                    <button class="btn-new modal-trigger" id="btn-gerenciar-grupos" style="display: none;">⚙ Gerenciar Grupos</button>
                    <button class="btn modal-trigger" id="btn-minha-conta">👤 Minha Conta</button>
                </div>
                
                <div id="usuarios-section" style="display: none;">
                    <table class="striped highlight">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Grupos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="usuarios-table-body">
                            <tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Usuário (Criação/Edição) -->
    <div id="modal-usuario" class="modal modal-fixed-footer">
        <form id="form-usuario" onsubmit="return false;">
            <div class="modal-content">
                <h4 id="modal-usuario-titulo">Novo Usuário</h4>
                <input type="hidden" id="usuario_id">
                
                <div class="row">
                    <div class="input-field col s12 m6">
                        <input id="usuario_nome" type="text" class="validate" required>
                        <label for="usuario_nome">Nome Completo</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="usuario_email" type="email" class="validate" required>
                        <label for="usuario_email">Email</label>
                    </div>
                </div>
                
                <div class="row" id="usuario-grupos-row">
                    <div class="input-field col s12">
                        <select id="usuario_grupos" multiple>
                            <option value="" disabled>Selecione os grupos</option>
                        </select>
                        <label>Grupos</label>
                        <span class="helper-text">Grupos aos quais o usuário pertence</span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="usuario_senha" type="password">
                        <label for="usuario_senha">Senha</label>
                        <span class="helper-text" id="senha-helper-text">Para criar, a senha é obrigatória.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
                <button type="submit" class="waves-effect waves-green btn" id="modal-salvar-usuario">Salvar</button>
            </div>
        </form>
    </div>

    <!-- Modal Minha Conta -->
    <div id="modal-minha-conta" class="modal">
        <form id="form-minha-conta" onsubmit="return false;">
            <div class="modal-content">
                <h4>Minha Conta</h4>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="minha_nome" type="text" class="validate" required value="<?php echo htmlspecialchars($usuarioLogado['nome']); ?>">
                        <label for="minha_nome">Nome</label>
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-field col s12">
                        <input id="minha_email" type="email" class="validate" required value="<?php echo htmlspecialchars($usuarioLogado['email']); ?>">
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
                <button type="submit" class="waves-effect waves-green btn" id="btn-salvar-minha-conta">Salvar</button>
            </div>
        </form>
    </div>

    <!-- Modal de Grupos -->
    <div id="modal-grupos" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4>Gerenciar Grupos</h4>
            
            <ul class="collection" id="grupos-lista">
                <li class="collection-item">Carregando...</li>
            </ul>
            
            <div class="section" id="novo-grupo-section" style="display: none;">
                <h5>Criar Novo Grupo</h5>
                <div class="row">
                    <div class="input-field col s12 m6">
                        <input id="novo_grupo_nome" type="text" placeholder="Nome do grupo">
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="novo_grupo_desc" type="text" placeholder="Descrição (opcional)">
                    </div>
                </div>
                <div class="row">
                    <div class="col s12" id="novo-grupo-permissoes"></div>
                </div>
                <button class="btn waves-effect waves-light" id="btn-criar-grupo">Criar Grupo</button>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Fechar</a>
        </div>
    </div>

    <!-- Modal de Edição de Grupo -->
    <div id="modal-editar-grupo" class="modal modal-fixed-footer">
        <form id="form-grupo" onsubmit="return false;">
            <div class="modal-content" style="max-height: 70vh; overflow-y: auto;">
                <h4 id="modal-grupo-titulo">Editar Grupo</h4>
                <input type="hidden" id="grupo_id">
                
                <div class="row">
                    <div class="input-field col s12 m6">
                        <input id="grupo_nome" type="text" required>
                        <label for="grupo_nome">Nome</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input id="grupo_desc" type="text">
                        <label for="grupo_desc">Descrição</label>
                    </div>
                </div>
                
                <div class="row" id="grupo-permissoes-row">
                    <div class="col s12">
                        <label>Permissões do Grupo</label>
                        <div id="grupo-permissoes-container" class="permissoes-grid"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
                <button type="button" class="waves-effect waves-green btn" id="btn-salvar-grupo">Salvar Grupo</button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        const API_BASE_URL_PHP = window.location.origin + '/api';
        
        // Permissões do usuário logado (injetadas pelo PHP)
        const USUARIO_LOGADO_ID = <?php echo $usuarioLogado['id']; ?>;
        const USUARIO_LOGADO_ROLE = '<?php echo $usuarioLogado['role']; ?>';
        const EH_ADMIN_DEV = <?php echo $isAdminOrDev ? 'true' : 'false'; ?>;
        const PERMISSOES_USUARIO = <?php echo json_encode($permissoesUsuario); ?>;
        
        // Função auxiliar para verificar permissão
        function temPermissao(perm) {
            if (EH_ADMIN_DEV) return true;
            return PERMISSOES_USUARIO.includes(perm);
        }
        
        function temAlgumaPermissao(perms) {
            if (EH_ADMIN_DEV) return true;
            return perms.some(p => PERMISSOES_USUARIO.includes(p));
        }
    </script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        $(document).ready(function() {
            $('.sidenav').sidenav({edge: 'left'});
            
            $('#user-name').text('<?php echo htmlspecialchars(getUsuarioNome()); ?>');
            $('#user-email').text('<?php echo htmlspecialchars(getUsuarioEmail()); ?>');
            
            inicializarGerenciadorUsuarios();
        });
        
        function fazerLogout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
