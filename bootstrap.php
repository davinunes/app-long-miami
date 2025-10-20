<?php
// Define uma constante para o caminho raiz da aplicação. Isso é extremamente útil.
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// 1. Carrega o arquivo de configuração principal
require_once APP_ROOT . '/config.php';

// 2. Carrega todas as classes da biblioteca JWT
require_once APP_ROOT . '/lib/jwt_loader.php';

// 3. Importa as classes JWT para o escopo global, facilitando o uso
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
?>