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

    $(document).on('click', 'a.ajax-link', function(e) {
        e.preventDefault(); // Impede o navegador de seguir o link
        const href = $(this).attr('href');
        carregarConteudo(href);
    });
    
    $('#dashboard-container').on('click', '#logout-link', function(e) {
        e.preventDefault();
        fazerLogout();
    });

    window.onpopstate = function(event) {
        if (event.state && event.state.path) {
            carregarConteudo(event.state.path, false);
        }
    };

	// -----------------------------------------------------------------
    // --- NOVOS LISTENERS DELEGADOS PARA OS FORMULÁRIOS ---
    // -----------------------------------------------------------------

    // 1. Listener para o botão SALVAR (Criar OU Atualizar)
    // Ele escuta cliques no #main-content, mas só reage se o clique foi no #btnSalvar
    $('#main-content').on('click', '#btnSalvar', function() {
        const btnText = $(this).text();
        
        if (btnText.includes('Salvar Nova')) {
            console.log("Botão 'Salvar Nova' clicado.");
            salvarNotificacao(); // Chama a função global
        } else if (btnText.includes('Atualizar')) {
            console.log("Botão 'Atualizar' clicado.");
            const id = $('#notificacao_id').val(); // Pega o ID do campo hidden
            if(id) {
                atualizarNotificacao(id); // Chama a função global
            } else {
                console.error("Não foi possível atualizar: ID não encontrado no formulário.");
            }
        }
    });

    // 2. Listener para o campo UNIDADE (Corrigindo o bug do "A812")
    // Escuta 'input' no #main-content, mas só reage se foi no #unidade
    $('#main-content').on('input', '#unidade', function() {
        const unidadeInput = $(this);
        const blocoInput = $('#bloco');
        
        if (!blocoInput.length) return;

        let unidadeVal = unidadeInput.val().trim().toUpperCase();
        
        if (unidadeVal.length === 0) {
            // CASO 1: Se o usuário apagar tudo da unidade, limpa o bloco
            blocoInput.val('');
        } else {
            const primeiroChar = unidadeVal.charAt(0);
            
            // CASO 2: Se o primeiro caractere for uma letra
            if (isNaN(parseInt(primeiroChar))) {
                blocoInput.val(primeiroChar); // Define o bloco
                unidadeInput.val(unidadeVal.substring(1)); // Remove a letra da unidade
            } else {
                // CASO 3: O primeiro caractere é um número.
                // NÃO FAZEMOS NADA. Deixamos o bloco como está.
                // O "blocoInput.val('');" foi removido daqui.
            }
        }
    });

    // --- FIM DOS NOVOS LISTENERS ---
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