<?php
// Este arquivo substitui o 'vendor/autoload.php' do Composer.
// Ele carrega manualmente todos os arquivos necessários da biblioteca JWT na ordem correta.

require_once __DIR__ . '/jwt/Exception.php';
require_once __DIR__ . '/jwt/ExpiredException.php';
require_once __DIR__ . '/jwt/SignatureInvalidException.php';
require_once __DIR__ . '/jwt/BeforeValidException.php';
require_once __DIR__ . '/jwt/Key.php';
require_once __DIR__ . '/jwt/JWT.php';
?>