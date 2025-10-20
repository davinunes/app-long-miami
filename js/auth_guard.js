(function() {
    // Pega o token diretamente do localStorage.
    const accessToken = localStorage.getItem('accessToken');

    // Verifica se estamos na página de login. Se estivermos, não faz nada,
    // pois é aqui que o usuário deve estar se não tiver token.
    // A verificação `window.location.pathname.endsWith('index.php')` pode precisar
    // de ajuste dependendo da sua estrutura de URL.
    const isLoginPage = window.location.pathname.endsWith('/') || window.location.pathname.endsWith('index.php');

    if (!accessToken && !isLoginPage) {
        // Se NÃO há token E NÃO estamos na página de login,
        // o acesso é indevido. Redireciona para o login.
        // Usamos replace() para que o usuário não possa usar o botão "voltar" do navegador
        // para acessar a página protegida novamente.
        window.location.replace('index.php');
    }
})();