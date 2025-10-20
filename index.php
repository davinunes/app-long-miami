<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel do Conselho</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; background-color: #f5f5f5; }
        main { flex: 1 0 auto; }
        .login-card { margin-top: 50px; }
        .token-display { background-color: #263238; color: #fff; padding: 15px; border-radius: 5px; word-break: break-all; font-family: monospace; }
        .error-message { color: #d32f2f; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <main class="container">
        <div id="login-container">
            <div class="row">
                <div class="col s12 m8 offset-m2 l6 offset-l3">
                    <div class="card login-card">
                        <div class="card-content">
                            <span class="card-title">Acesso ao Painel</span>
                            <form id="login-form">
                                <div class="input-field">
                                    <i class="material-icons prefix">email</i>
                                    <input id="email" type="email" class="validate" value="admin@seusistema.com">
                                    <label for="email">Email</label>
                                </div>
                                <div class="input-field">
                                    <i class="material-icons prefix">lock</i>
                                    <input id="senha" type="password" class="validate" value="umaSenhaMuitoForte123!">
                                    <label for="senha">Senha</label>
                                </div>
                                <div id="login-error" class="error-message"></div>
                                <button type="submit" class="btn waves-effect waves-light right">Entrar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="logado-container" style="display: none;">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Autenticação bem-sucedida!</span>
                    <p>Você está logado. Abaixo estão os detalhes do seu token de acesso.</p>
                    
                    <h5>Access Token JWT:</h5>
                    <pre id="access-token-display" class="token-display"></pre>
                    
                    <h5>Payload do Token (Conteúdo Decodificado):</h5>
                    <pre id="payload-display" class="token-display"></pre>
                    
                    <p class="center-align">O <strong>Refresh Token</strong> foi armazenado em um cookie seguro (HttpOnly) e não é acessível via JavaScript.</p>
                </div>
                <div class="card-action">
                    <a href="#" id="logout-link">Sair (Logout)</a>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/jwt.js"></script> </body>
</html>