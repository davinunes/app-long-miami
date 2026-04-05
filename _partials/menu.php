<ul id="slide-out" class="sidenav">
    <li>
        <div class="user-view">
            <div class="sidenav-header">
                <i class="material-icons">dashboard</i>
            </div>
            <a href="#!user"><span id="user-name" class="white-text name">Usuário</span></a>
            <a href="#!email"><span id="user-email" class="white-text email">email@exemplo.com</span></a>
        </div>
    </li>
    
    <li><a class="waves-effect ajax-link" href="dashboard_content.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
    
    <?php if (isAdmin() || temAlgumaPermissao(['notificacao.listar', 'notificacao.ver', 'notificacao.criar', 'notificacao.acao_rapida', 'notificacao.listar_lavradas', 'notificacao.listar_enviadas', 'notificacao.listar_em_cobranca', 'notificacao.editar_datas'])): ?>
    <li><a class="waves-effect ajax-link" href="lista.php"><i class="material-icons">notifications</i>Notificações</a></li>
    <?php endif; ?>
    
    <?php if (isAdmin() || temPermissao('ocorrencia.listar') || temAlgumaPermissao(['ocorrencia.ver_detalhes', 'ocorrencia.criar'])): ?>
    <li><a class="waves-effect ajax-link" href="ocorrencias.php"><i class="material-icons">report_problem</i>Ocorrências</a></li>
    <?php endif; ?>
    
    <?php if (isAdmin() || temAlgumaPermissao(['usuario.listar', 'usuario.ver_detalhes', 'usuario.editar', 'usuario.criar'])): ?>
    <li><a class="waves-effect ajax-link" href="usuarios.php"><i class="material-icons">people</i>Usuários</a></li>
    <?php endif; ?>
     
    <?php if (isAdmin()): ?>
    <li><div class="divider"></div></li>
    <li><a class="waves-effect ajax-link" href="configuracoes.php"><i class="material-icons">settings</i>Configurações</a></li>
    <?php endif; ?>
    
    <li><div class="divider"></div></li>
    
    <li><a class="waves-effect" href="logout.php"><i class="material-icons">exit_to_app</i>Sair</a></li>
</ul>

<div class="sidenav-overlay"></div>

<script>
if (typeof fazerLogout === 'undefined') {
    function fazerLogout() {
        window.location.href = 'logout.php';
    }
}

// Lógica de Permissões no JS
const API_BASE_URL_PHP = window.location.origin + '/api';
const PERMISSOES_USUARIO = <?php echo json_encode(getPermissoesUsuario()); ?>;
const USUARIO_LOGADO_ID = <?php echo (int)getUsuarioId(); ?>;
const EH_ADMIN_DEV = <?php echo isAdmin() ? 'true' : 'false'; ?>;

function temPermissao(permissao) {
    if (EH_ADMIN_DEV) return true;
    return PERMISSOES_USUARIO.includes(permissao);
}

function temAlgumaPermissao(permissoesArray) {
    if (EH_ADMIN_DEV) return true;
    return permissoesArray.some(p => PERMISSOES_USUARIO.includes(p));
}

(function() {
    function closeSidenav() {
        const sidenav = document.querySelector('.sidenav');
        const overlay = document.querySelector('.sidenav-overlay');
        if (sidenav) sidenav.classList.remove('sidenav-open');
        if (overlay) overlay.classList.remove('active');
    }

    function openSidenav() {
        const sidenav = document.querySelector('.sidenav');
        const overlay = document.querySelector('.sidenav-overlay');
        if (sidenav) sidenav.classList.add('sidenav-open');
        if (overlay) overlay.classList.add('active');
    }

    document.addEventListener('click', function(e) {
        // 1. Clique no botão hambúrguer
        const toggleBtn = e.target.closest('.custom-sidenav-toggle');
        if (toggleBtn) {
            e.preventDefault();
            const sidenav = document.querySelector('.sidenav');
            if (sidenav && sidenav.classList.contains('sidenav-open')) {
                closeSidenav();
            } else {
                openSidenav();
            }
            return;
        }
        
        // 2. Clique no overlay para fechar
        if (e.target.classList.contains('sidenav-overlay')) {
            closeSidenav();
            return;
        }

        // 3. Clique em um link do menu em telas pequenas
        if (e.target.closest('.sidenav li a') && window.innerWidth <= 992) {
            closeSidenav();
        }
    });
})();
</script>