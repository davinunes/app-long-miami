<?php
/**
 * SCRIPT DO APOCALIPSE - DESTRUIR E RECRIAR TUDO
 * 
 * AVISO: Este script IRÁ DESTRUIR todos os dados do banco e limpar uploads!
 * Use com EXTREMA CAUTELA!
 * 
 * Uso: 
 *   - Navegador: acessar diretamente (com confirmação)
 *   - CLI: php apocalipse.php
 */

require_once __DIR__ . '/config.php';

define('SCRIPT_TOKEN', 'APOCALIPSE-2026');

$isWeb = php_sapi_name() !== 'cli';
$confirmado = false;

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚠️ Script do Apocalipse</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #eee; }
        .danger { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #28a745; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .warning { background: #ffc107; color: #333; padding: 20px; border-radius: 8px; margin: 20px 0; }
        pre { background: #16213e; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 12px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; }
        .btn:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        h1 { color: #e94560; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <h1>☢️ Script do Apocalipse</h1>
';
    
    // Verificar token
    if (isset($_GET['token']) && $_GET['token'] === SCRIPT_TOKEN) {
        $confirmado = true;
    } elseif (isset($_POST['confirmar'])) {
        if ($_POST['token'] === SCRIPT_TOKEN) {
            $confirmado = true;
        } else {
            echo '<div class="warning">Token inválido!</div>';
        }
    }
    
    if (!$confirmado) {
        echo '<div class="danger">
            <h2>⚠️ AVISO: OPERAÇÃO DESTRUTIVA!</h2>
            <p>Este script IRÁ:</p>
            <ul>
                <li>Destruir e recriar o banco de dados</li>
                <li>Recriar todas as tabelas (estrutura do migrate_zero)</li>
                <li>Limpar TODOS os arquivos de uploads</li>
                <li><strong>MANTER: regimento.json e storage/estrutura.php</strong></li>
            </ul>
            <p><strong>Você tem certeza absoluta?</strong></p>
        </div>';
        
        echo '<form method="POST">
            <input type="hidden" name="token" value="' . SCRIPT_TOKEN . '">
            <p>Para confirmar, digite: <strong>SIM, DESTRUIR TUDO</strong></p>
            <input type="text" name="confirmar" placeholder="Digite a confirmação..." style="width: 100%; padding: 10px; margin: 10px 0;">
            <button type="submit" class="btn">EXECUTAR APOCALIPSE</button>
            <a href="dashboard_content.php" class="btn btn-secondary">Cancelar</a>
        </form>';
        echo '</body></html>';
        exit;
    }
    
    echo '<div class="warning"><h2>⏳ Executando...</h2><pre>';
    ob_flush();
    flush();
} else {
    // CLI mode
    echo "===========================================\n";
    echo "   ⚠️  SCRIPT DO APOCALIPSE  ⚠️\n";
    echo "===========================================\n\n";
    
    if ($argc < 2 || $argv[1] !== '--force') {
        echo "Este script IRÁ:\n";
        echo "1. Destruir e recriar o banco de dados\n";
        echo "2. Recriar todas as tabelas (estrutura do migrate_zero)\n";
        echo "3. Limpar TODOS os arquivos de uploads\n";
        echo "4. MANTER: regimento.json e storage/estrutura.php\n\n";
        echo "Use: php apocalipse.php --force\n";
        echo "Ou: php apocalipse.php\n (com confirmação)\n";
        exit;
    }
    
    if ($argc < 3 || $argv[2] !== 'SIMDESTRUI') {
        echo "Digite 'php apocalipse.php --force SIMDESTRUI' para confirmar.\n";
        exit(1);
    }
    
    echo "\n>>> INICIANDO DESTRUIÇÃO...\n\n";
}

$dbname = DB_NAME;
$output = function($msg, $type = 'info') use ($isWeb) {
    if ($isWeb) {
        echo htmlspecialchars($msg) . "<br>\n";
    } else {
        echo $msg . "\n";
    }
};

// ===========================================
// PARTE 1: DESTRUIR E RECRIAR BANCO DE DADOS
// ===========================================

$output("[1/3] Conectando ao MySQL (sem banco)...");

try {
    $dsnSemDb = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdoMaster = new PDO($dsnSemDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $output("    ✓ Conectado!");
} catch (PDOException $e) {
    die("ERRO: " . $e->getMessage() . "\n");
}

$output("[2/3] Destruindo banco '{$dbname}'...");
$pdoMaster->exec("DROP DATABASE IF EXISTS `{$dbname}`");
$output("    ✓ Banco destruído.");

$output("[3/3] Recriando banco '{$dbname}'...");
$pdoMaster->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$output("    ✓ Banco criado.");

$pdo = getDbConnection();
if (!$pdo) {
    die("ERRO: Não foi possível reconectar ao banco.\n");
}

$estruturaFile = __DIR__ . '/storage/estrutura.sql';
if (!file_exists($estruturaFile)) {
    die("ERRO: Arquivo storage/migrate_zero.sql não encontrado!\n");
}

$output("\n>>> Recriando tabelas...");
$sql = file_get_contents($estruturaFile);

$sql = preg_replace('/SET FOREIGN_KEY_CHECKS = 0;/', '', $sql);
$sql = preg_replace('/SET FOREIGN_KEY_CHECKS = 1;/', '', $sql);
$sql = preg_replace('/SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";/', '', $sql);
$sql = preg_replace('/SET time_zone = "\+00:00";/', '', $sql);

$statements = array_filter(array_map('trim', explode(';', $sql)));
$countTables = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;
    if (stripos($statement, 'DROP TABLE') !== false || stripos($statement, 'CREATE TABLE') !== false) {
        try {
            $pdo->exec($statement);
            if (stripos($statement, 'CREATE TABLE') !== false) $countTables++;
        } catch (PDOException $e) {
            $output("    AVISO: " . $e->getMessage());
        }
    }
}
$output("    ✓ {$countTables} tabelas recriadas!\n");

// ===========================================
// PARTE 2: LIMPAR ARQUIVOS DE UPLOADS
// ===========================================

$output("[2/3] Limpando arquivos de uploads...");

$dirsParaLimpar = [
    __DIR__ . '/uploads',
];

$totalRemovidos = 0;
foreach ($dirsParaLimpar as $dir) {
    if (!is_dir($dir)) {
        $output("    - {$dir} não existe, pulando.");
        continue;
    }
    
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    
    $count = 0;
    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
            $count++;
        }
    }
    $totalRemovidos += $count;
    $output("    - {$count} arquivo(s) removido(s) de uploads/");
}

// Recriar estrutura de uploads
if (!is_dir(__DIR__ . '/uploads/imagens')) {
    mkdir(__DIR__ . '/uploads/imagens', 0755, true);
    $output("    - Estrutura uploads/ recriada");
}

// ===========================================
// PARTE 3: OUTROS DIRETÓRIOS
// ===========================================

$output("\n[3/3] Verificando outros diretórios...");

$outrosDirs = [
    __DIR__ . '/evidencias',
    __DIR__ . '/anexos',
    __DIR__ . '/fotos',
    __DIR__ . '/images',
    __DIR__ . '/temp',
];

foreach ($outrosDirs as $dir) {
    if (is_dir($dir)) {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        
        $count = 0;
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
                $count++;
            }
        }
        if ($count > 0) {
            $totalRemovidos += $count;
            $output("    - {$count} arquivo(s) removido(s) de " . basename($dir));
        }
    }
}

// ===========================================
// RESULTADO FINAL
// ===========================================

if ($isWeb) {
    echo '</pre></div>';
    echo '<div class="success">
        <h2>✅ APOCALIPSE CONCLUÍDO!</h2>
        <ul>
            <li>Banco: destruído e recriado</li>
            <li>Tabelas: ' . $countTables . ' recriadas</li>
            <li>Arquivos: ' . $totalRemovidos . ' removidos</li>
            <li>Mantidos: regimento.json, storage/estrutura.php</li>
        </ul>
        <p><strong>Não esqueça de rodar os migrates para recriar dados iniciais!</strong></p>
    </div>';
    echo '<a href="dashboard_content.php" class="btn btn-secondary">Voltar ao Dashboard</a>';
    echo '</body></html>';
} else {
    echo "\n===========================================\n";
    echo "   ✅ APOCALIPSE CONCLUÍDO! ✅\n";
    echo "===========================================\n\n";
    echo "Resumo:\n";
    echo "  - Banco: destruído e recriado\n";
    echo "  - Tabelas: {$countTables} recriadas\n";
    echo "  - Arquivos: {$totalRemovidos} removidos\n";
    echo "  - Mantidos: regimento.json, storage/estrutura.php\n\n";
    echo "Lembre-se de rodar os migrates para recriar dados iniciais!\n";
}
?>
