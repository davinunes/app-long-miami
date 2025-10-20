$(document).ready(function() {

    // ----------------------------------------------------------------
    // FUNÇÕES DE UI E DE APOIO
    // ----------------------------------------------------------------

    // Decodifica o token para pegar informações (como nome de usuário)
    function decodeJwt(token) {
        try {
            return JSON.parse(atob(token.split('.')[1]));
        } catch (e) {
            return null;
        }
    }

    // Mostra a tela de login e esconde o painel
    function mostrarTelaLogin() {
        $('#dashboard-container').hide();
        $('#login-container').show();
    }

    // Mostra o painel, esconde o login, e inicializa componentes Materialize
    function mostrarTelaDashboard() {
        $('#login-container').hide();
        $('#dashboard-container').show();
        $('.sidenav').sidenav(); // Inicializa o menu lateral

        const payload = decodeJwt(localStorage.getItem('accessToken'));
        if (payload && payload.data) {
            $('#user-name').text(payload.data.nome || 'Usuário');
            $('#user-email').text(payload.data.email || payload.iss);
        }
    }
    
    // Faz o logout, limpa o token e redireciona
    function fazerLogout() {
        localStorage.removeItem('accessToken');
        $.post('logout.php').always(() => {
            window.location.replace('index.php');
        });
    }

    // ----------------------------------------------------------------
    // FUNÇÃO PRINCIPAL DE CARREGAMENTO DE CONTEÚDO (O CORAÇÃO DA SPA)
    // ----------------------------------------------------------------

    function carregarConteudo(url, pushState = true) {
        const accessToken = localStorage.getItem('accessToken');
        if (!accessToken) {
            fazerLogout();
            return;
        }

        // Mostra um feedback visual de carregamento
        $('#main-content').html('<div class="progress"><div class="indeterminate"></div></div>');

        $.ajax({
            url: url,
            method: 'GET',
            beforeSend: xhr => xhr.setRequestHeader('Authorization', `Bearer ${accessToken}`),
            success: responseHtml => {
                $('#main-content').html(responseHtml);

                // Se a URL na barra de endereços precisar ser atualizada
                if (pushState) {
                    window.location.hash = url;
                }
            },
            error: jqXHR => {
                if (jqXHR.status === 401) {
                    alert('Sua sessão expirou. Por favor, faça login novamente.');
                    fazerLogout();
                } else {
                    $('#main-content').html(`<div class='container center-align'><h4>Erro ao carregar</h4><p>Não foi possível carregar o conteúdo de: ${url}</p></div>`);
                }
            }
        });
    }

    // ----------------------------------------------------------------
    // EVENTOS (QUEM CHAMA AS FUNÇÕES)
    // ----------------------------------------------------------------

    // Evento de submit do formulário de login
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        const email = $('#email').val();
        const senha = $('#senha').val();
        
        $.ajax({
            url: 'login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ email, senha }),
            success: response => {
                localStorage.setItem('accessToken', response.access_token);
                roteamentoInicial(); // Inicia a aplicação logada
            },
            error: jqXHR => {
                $('#login-error').text(jqXHR.responseJSON?.message || 'Erro de conexão.');
            }
        });
    });

    // **NOVO EVENT HANDLER** - Intercepta cliques SOMENTE nos links com a classe 'ajax-link'
    $(document).on('click', 'a.ajax-link', function(e) {
        e.preventDefault(); // Impede o navegador de seguir o link
        const href = $(this).attr('href');
        carregarConteudo(href);
    });
    
    // Evento de clique no botão de logout
    $('#dashboard-container').on('click', '#logout-link', function(e) {
        e.preventDefault();
        fazerLogout();
    });

    // Lida com os botões de voltar/avançar do navegador
    window.onpopstate = function(event) {
        if (event.state && event.state.path) {
            carregarConteudo(event.state.path, false);
        }
    };

    // ----------------------------------------------------------------
    // INICIALIZAÇÃO DA APLICAÇÃO
    // ----------------------------------------------------------------

	function roteamentoInicial() {
		if (localStorage.getItem('accessToken')) {
			mostrarTelaDashboard();

			// Lógica ajustada para ler o HASH
			let path = window.location.hash.substring(1); // Pega o que vem depois do '#'
			if (!path) {
				path = 'dashboard_content.php'; // Página padrão
			}
			carregarConteudo(path, false); // Passamos false para não mexer no histórico
		} else {
			mostrarTelaLogin();
		}
	}

    roteamentoInicial();
});