<ul id="slide-out" class="sidenav">
    <li>
        <div class="user-view">
            <div class="sidenav-header">
                <i class="material-icons">dashboard</i>
                <button class="sidenav-collapse-btn" onclick="toggleSidenav()" title="Recolher menu">
                    <i class="material-icons">chevron_left</i>
                </button>
            </div>
            <a href="#!user"><span id="user-name" class="white-text name">Usuário</span></a>
            <a href="#!email"><span id="user-email" class="white-text email">email@exemplo.com</span></a>
        </div>
    </li>
    
    <li><a class="waves-effect ajax-link" href="dashboard_content.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
    
    <?php if (isAdmin() || temPermissao('notificacao.listar') || temAlgumaPermissao(['notificacao.ver', 'notificacao.criar'])): ?>
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

<script>
if (typeof fazerLogout === 'undefined') {
    function fazerLogout() {
        window.location.href = 'logout.php';
    }
}

function toggleSidenav() {
    const sidenav = document.querySelector('.sidenav');
    const mainContent = document.getElementById('main-content') || document.querySelector('main.main-content');
    const btn = document.querySelector('.sidenav-collapse-btn i');
    
    sidenav.classList.toggle('sidenav-collapsed');
    mainContent.classList.toggle('sidenav-collapsed');
    
    if (sidenav.classList.contains('sidenav-collapsed')) {
        sidenav.style.transform = 'translateX(-280px)';
        btn.textContent = 'chevron_right';
        if (mainContent) mainContent.style.marginLeft = '0';
    } else {
        sidenav.style.transform = 'translateX(0)';
        btn.textContent = 'chevron_left';
        if (mainContent) mainContent.style.marginLeft = '280px';
    }
}
</script>