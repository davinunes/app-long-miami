<?php
require_once 'auth.php';

// Se já está logado, redireciona
if (estaLogado()) {
    header('Location: dashboard_content.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $error = 'Preencha email e senha.';
    } else {
        $result = login($email, $senha);
        if ($result['success']) {
            header('Location: dashboard_content.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - App Long Miami</title>
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
                <h1>App Long Miami</h1>
                <p>Sistema de gestão de notificações condominiais.</p>
            </div>
            <div class="login-features">
                <div class="feature-item">
                    <i class="material-icons">check_circle</i>
                    <span>Interface moderna e intuitiva</span>
                </div>
                <div class="feature-item">
                    <i class="material-icons">check_circle</i>
                    <span>Gestão de ocorrências e notificações</span>
                </div>
                <div class="feature-item">
                    <i class="material-icons">check_circle</i>
                    <span>Controle de acessos por papéis</span>
                </div>
            </div>
        </div>
        <div class="login-right">
            <div class="login-form-container">
                <div class="login-header">
                    <h2>Bem-vindo</h2>
                    <p>Entre com suas credenciais para acessar</p>
                </div>
                
                <?php if ($error): ?>
                <div class="error-message" style="display: block; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php">
                    <div class="input-field">
                        <i class="material-icons prefix">email</i>
                        <input id="email" name="email" type="email" class="validate" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <label for="email">Email</label>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">lock</i>
                        <input id="senha" name="senha" type="password" class="validate" required>
                        <label for="senha">Senha</label>
                    </div>
                    <button type="submit" class="btn waves-effect waves-light login-btn">
                        <span>Entrar</span>
                        <i class="material-icons right">arrow_forward</i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
