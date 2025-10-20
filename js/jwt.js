$(document).ready(function() {
    // Função para decodificar o payload do JWT (apenas para exibição)
    function decodeJwt(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch (e) {
            return { erro: "Não foi possível decodificar o token." };
        }
    }

    // Função para atualizar a UI quando o usuário está logado
    function mostrarTelaLogada() {
        const accessToken = localStorage.getItem('accessToken');
        if (!accessToken) return;

        $('#login-container').hide();
        $('#logado-container').show();

        const payload = decodeJwt(accessToken);

        $('#access-token-display').text(accessToken);
        $('#payload-display').text(JSON.stringify(payload, null, 2));
    }

    // Lida com o envio do formulário de login
    $('#login-form').on('submit', function(e) {
        e.preventDefault(); // Impede o recarregamento da página

        const email = $('#email').val();
        const senha = $('#senha').val();
        $('#login-error').text(''); // Limpa erros antigos

        $.ajax({
            url: 'login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ email: email, senha: senha }),
            success: function(response) {
                if (response.status === 'success') {
                    // ARMAZENA O TOKEN
                    localStorage.setItem('accessToken', response.access_token);
                    // Atualiza a tela
                    mostrarTelaLogada();
                }
            },
            error: function(jqXHR) {
                const errorMsg = jqXHR.responseJSON?.message || 'Erro desconhecido.';
                $('#login-error').text(errorMsg);
            }
        });
    });

    // Lida com o clique no link de logout
    $('#logout-link').on('click', function(e) {
        e.preventDefault();

        // Remove o token do localStorage
        localStorage.removeItem('accessToken');

        // Chama a API de logout para invalidar o refresh token no servidor
        $.ajax({
            url: 'logout.php',
            method: 'POST',
            success: function(response) {
                console.log(response.message);
            },
            complete: function() {
                // Independentemente do resultado, atualiza a UI
                $('#logado-container').hide();
                $('#login-container').show();
                $('#login-error').text('');
                $('#senha').val('');
            }
        });
    });

    // VERIFICA SE JÁ EXISTE UM TOKEN AO CARREGAR A PÁGINA
    if (localStorage.getItem('accessToken')) {
        // Aqui, em um sistema real, seria bom verificar se o token ainda é válido
        // antes de mostrar a tela logada. Para o lab, vamos apenas mostrar.
        mostrarTelaLogada();
    }
});