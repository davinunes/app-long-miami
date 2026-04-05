<?php
/**
 * SCRIPT DO APOCALIPSE - DESTRUIR E RECRIAR TUDO
 * 
 * AVISO: Este script IRÁ DESTRUIR todos os dados do banco e limpar uploads!
 * Use com EXTREMA CAUTELA!
 */

require_once __DIR__ . '/config.php';

echo "===========================================\n";
echo "   ⚠️  SCRIPT DO APOCALIPSE  ⚠️\n";
echo "===========================================\n\n";

echo "Este script IRÁ:\n";
echo "1. Destruir e recriar o banco de dados\n";
echo "2. Recriar todas as tabelas (estrutura do migrate_zero)\n";
echo "3. Limpar TODOS os arquivos de uploads\n";
echo "4. MANTER: regimento.json e storage/estrutura.php\n\n";

echo "Digite 'SIM, DESTRUIR TUDO' para confirmar: ";
$confirmacao = trim(fgets(STDIN));

if ($confirmacao !== 'SIM, DESTRUIR TUDO') {
    echo "\nOperação cancelada pelo usuário.\n";
    exit(1);
}

echo "\n>>> INICIANDO DESTRUição...\n\n";

// ===========================================
// PARTE 1: DESTRUIR E RECRIAR BANCO DE DADOS
// ===========================================

echo "[1/3] Conectando ao MySQL (sem banco)...\n";

$dsnSemDb = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
$pdoMaster = new PDO($dsnSemDb, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "[2/3] Destruindo banco '{$dbname}'...\n";
$pdoMaster->exec("DROP DATABASE IF EXISTS `{$dbname}`");
echo "    - Banco destruído.\n";

echo "[3/3] Recriando banco '{$dbname}'...\n";
$pdoMaster->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "    - Banco criado.\n";

$pdo = getDbConnection();
if (!$pdo) {
    die("ERRO: Não foi possível reconectar ao banco.\n");
}

// Executar migrate_zero.sql
$estruturaFile = __DIR__ . '/storage/migrate_zero.sql';
if (!file_exists($estruturaFile)) {
    die("ERRO: Arquivo storage/migrate_zero.sql não encontrado!\n");
}

echo "\n>>> Recriando tabelas...\n";
$sql = file_get_contents($estruturaFile);

$sql = preg_replace('/SET FOREIGN_KEY_CHECKS = 0;/', '', $sql);
$sql = preg_replace('/SET FOREIGN_KEY_CHECKS = 1;/', '', $sql);
$sql = preg_replace('/SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";/', '', $sql);
$sql = preg_replace('/SET time_zone = "\+00:00";/', '', $sql);

$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;
    if (stripos($statement, 'DROP TABLE') !== false || stripos($statement, 'CREATE TABLE') !== false) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            echo "    AVISO: " . $e->getMessage() . "\n";
        }
    }
}

echo "    - Estrutura recriada com sucesso!\n\n";

// ===========================================
// PARTE 2: LIMPAR ARQUIVOS DE UPLOADS
// ===========================================

echo "[2/3] Limpando arquivos de uploads...\n";

$dirsParaLimpar = [
    __DIR__ . '/uploads',
];

foreach ($dirsParaLimpar as $dir) {
    if (!is_dir($dir)) {
        echo "    - {$dir} não existe, pulando.\n";
        continue;
    }
    
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    
    $count = 0;
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
            $count++;
        }
    }
    echo "    - {$count} arquivo(s) removido(s) de {$dir}\n";
}

echo "\n    Estrutura de diretórios mantida.\n\n";

// ===========================================
// PARTE 3: LIMPEZA ADICIONAL (se houver)
// ===========================================

echo "[3/3] Verificando outros diretórios...\n";

$outrosDirs = [
    __DIR__ . '/evidencias',
    __DIR__ . '/anexos',
    __DIR__ . '/fotos',
    __DIR__ . '/images',
];

foreach ($outrosDirs as $dir) {
    if (is_dir($dir)) {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        
        $count = 0;
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
                $count++;
            }
        }
        echo "    - {$count} arquivo(s) removido(s) de {$dir}\n";
    }
}

// ===========================================
// RESULTADO FINAL
// ===========================================

echo "\n===========================================\n";
echo "   ✅ APOCALIPSE CONCLUÍDO! ✅\n";
echo "===========================================\n\n";

echo "Resumo:\n";
echo "  - Banco: destruído e recriado\n";
echo "  - Tabelas: recriadas do migrate_zero\n";
echo "  - Uploads: limpos\n";
echo "  - Mantidos: regimento.json, storage/estrutura.php\n\n";

echo "Lembre-se de rodar os migrates para recriar dados iniciais!\n";
