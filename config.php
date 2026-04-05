<?php
// Configurações de Conexão com o Banco de Dados MariaDB
// As variáveis de ambiente são lidas do Docker ou sistema operacional

define('DB_HOST', getenv('DB_HOSTNAME') ?: getenv('DB_HOST') ?: '172.24.100.30');
define('DB_NAME', getenv('DB_DATABASE') ?: 'app_db');
define('DB_USER', getenv('DB_USERNAME') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'dinovatech2005');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

define('UPLOADS_PATH', __DIR__ . '/uploads/imagens/');

define('JWT_SECRET_KEY', getenv('APP_MASTER_KEY') ?: 'dinovatech');
define('JWT_ALGORITHM', 'HS256');

function getDbConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Erro de conexão com banco: ' . $e->getMessage());
        return null;
    }
}
?>
