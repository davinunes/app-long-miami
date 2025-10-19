<?php
// Configurações de Conexão com o Banco de Dados MariaDB

// Constantes para as credenciais
define('DB_HOST', '172.24.100.30');    // Geralmente 'localhost' ou o IP do seu servidor de banco de dados
define('DB_USER', 'root');  // Substitua pelo seu usuário do MariaDB
define('DB_PASS', 'dinovatech2005');    // Substitua pela sua senha
define('DB_NAME', 'app_db');       // O nome do seu banco de dados

// Constante para o caminho base de uploads
define('UPLOADS_PATH', __DIR__ . '/uploads/imagens/');



// ATENÇÃO: Em um ambiente de produção, esta chave NUNCA deve estar no código.
// Use variáveis de ambiente (.env) para armazená-la de forma segura.
// Para este exemplo, vamos defini-la aqui.
define('JWT_SECRET_KEY', 'dinovatech');
define('JWT_ALGORITHM', 'HS256');


/**
 * Função para criar uma nova conexão com o banco de dados usando PDO.
 * PDO é a forma moderna e segura de se conectar a bancos de dados em PHP.
 * * @return PDO|null Retorna um objeto PDO em caso de sucesso ou null em caso de falha.
 */
function getDbConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna resultados como arrays associativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepares nativos do banco de dados
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Em um ambiente de produção, você deveria logar este erro em vez de exibi-lo.
        // http_response_code(500); // Internal Server Error
        // die('Erro de conexão com o banco de dados: ' . $e->getMessage());
        return null;
    }
}
?>