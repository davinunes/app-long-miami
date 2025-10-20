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

    <div id="login-container" class="container">
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

    <div id="dashboard-container" style="display: none;">
        <header>
            <nav class="white" role="navigation">
                <div class="nav-wrapper">
                    <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons" style="color: #333;">menu</i></a>
                    <a href="#!" class="brand-logo" style="color: #333;">Meu Painel</a>
                </div>
            </nav>

            <?php
                // Incluindo o menu a partir do nosso novo arquivo de componente
                include '_partials/menu.php';
            ?>
        </header>
        
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