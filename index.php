<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css"> 
</head>
<body>

    <div id="login-container">
        <div class="login-wrapper">
            <div class="login-left">
                <div class="login-brand">
                    <div class="brand-icon">
                        <i class="material-icons">dashboard</i>
                    </div>
                    <h1>Meu Painel</h1>
                    <p>Gerencie suas notificações de forma simples e eficiente.</p>
                </div>
                <div class="login-features">
                    <div class="feature-item">
                        <i class="material-icons">check_circle</i>
                        <span>Segurança avançada com JWT</span>
                    </div>
                    <div class="feature-item">
                        <i class="material-icons">check_circle</i>
                        <span>Interface moderna e intuitiva</span>
                    </div>
                    <div class="feature-item">
                        <i class="material-icons">check_circle</i>
                        <span>Gerenciamento de usuários</span>
                    </div>
                </div>
            </div>
            <div class="login-right">
                <div class="login-form-container">
                    <div class="login-header">
                        <h2>Bem-vindo de volta</h2>
                        <p>Entre com suas credenciais para acessar</p>
                    </div>
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
                        <button type="submit" class="btn waves-effect waves-light login-btn">
                            <span>Entrar</span>
                            <i class="material-icons right">arrow_forward</i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="dashboard-container" style="display: none;">
        <header>
            <?php
                include '_partials/menu.php';
            ?>
        </header>
        
        <a href="#" data-target="slide-out" class="sidenav-trigger mobile-menu-btn">
            <i class="material-icons">menu</i>
        </a>
        
			<main id="main-content" class="main-content">
            </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/jwt.js"></script>
	<script src="js/helpers.js"></script>
	<script src="js/funcs.js"></script> 
	<script src="js/main.js"></script> 
</body>
</html>