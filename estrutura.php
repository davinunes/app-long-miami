<?php
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;
$outputDir = __DIR__ . '/storage';
$outputFile = $outputDir . '/migrate_zero.sql';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$output = "-- Estrutura do banco de dados: {$dbname}\n";
$output .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n\n";

$output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$output .= "SET time_zone = \"+00:00\";\n\n";

$pdo = getDbConnection();
if (!$pdo) {
    die("Erro ao conectar ao banco de dados.\n");
}

$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $output .= "\n-- --------------------------------------------------------\n";
    $output .= "-- Estrutura da tabela `{$table}`\n";
    $output .= "-- --------------------------------------------------------\n\n";
    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $output .= $row[1] . ";\n\n";
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($outputFile, $output);

echo "Estrutura exportada com sucesso!\n";
echo "Arquivo: {$outputFile}\n";
echo "Tabelas exportadas: " . count($tables) . "\n";
