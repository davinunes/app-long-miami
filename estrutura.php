<?php
/**
 * Script para exportar estrutura E dados iniciais do banco
 * 
 * Convenção de comentários:
 *   -- STRUCTURE: table_name    = apenas estrutura (CREATE)
 *   -- SEED DATA: table_name    = estrutura + dados (CREATE + INSERT)
 * 
 * Uso:
 *   - CLI: php estrutura.php [--structure-only] [--seed-only]
 *   - Navegador: acessar diretamente
 */

require_once __DIR__ . '/config.php';

$isWeb = php_sapi_name() !== 'cli';
$seedOnly = false;
$structureOnly = false;

if (!$isWeb) {
    global $argv;
    if (in_array('--seed-only', $argv)) $seedOnly = true;
    if (in_array('--structure-only', $argv)) $structureOnly = true;
}

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Exportar Estrutura do Banco</title>
    <style>
        body { font-family: monospace; max-width: 900px; margin: 20px auto; padding: 20px; background: #1a1a2e; color: #eee; }
        pre { background: #16213e; padding: 20px; border-radius: 8px; overflow-x: auto; max-height: 500px; }
        .success { background: #28a745; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #17a2b8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        a { color: #667eea; }
    </style>
</head>
<body>
    <h1>📦 Exportar Estrutura do Banco</h1>
';
}

function exportStructure($pdo, $table) {
    $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $create = $row[1];
    // Remover AUTO_INCREMENT do CREATE para forçar começar do 1
    $create = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $create);
    return $create;
}

function exportTableData($pdo, $table) {
    $stmt = $pdo->query("SELECT * FROM `{$table}`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) return [];
    
    $columns = array_keys($rows[0]);
    $inserts = [];
    
    foreach ($rows as $row) {
        $values = [];
        foreach ($row as $val) {
            if ($val === null) {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . str_replace(["'", "\\"], ["''", "\\\\"], $val) . "'";
            }
        }
        $inserts[] = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");";
    }
    
    return $inserts;
}

$pdo = getDbConnection();
if (!$pdo) {
    die("Erro ao conectar ao banco.\n");
}

// Tabelas e seus dados - marcar quais precisam de dados iniciais
$seedTables = [
    'permissoes' => 'Sistema de permissões granulares',
    'notificacao_status' => 'Status do ciclo de vida das notificações',
    'notificacao_tipos' => 'Tipos de notificação (Advertência, Multa, etc)',
    'assuntos' => 'Assuntos predefinidos para notificações',
    'grupos' => 'Grupos de usuários padrão',
    'papeles' => 'Papéis legados do sistema',
];

// Tables to export (todas)
$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

// Gerar SQL
$output = "-- ========================================================\n";
$output .= "-- ESTRUTURA E DADOS INICIAIS - " . DB_NAME . "\n";
$output .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- ========================================================\n\n";

$output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

$seedOutput = "-- ========================================================\n";
$seedOutput .= "-- DADOS INICIAIS (SEED)\n";
$seedOutput .= "-- ========================================================\n\n";

// Exportar cada tabela
foreach ($tables as $table) {
    $output .= "-- --------------------------------------------------------\n";
    $output .= "-- Estrutura da tabela `{$table}`\n";
    $output .= "-- --------------------------------------------------------\n\n";
    
    $create = exportStructure($pdo, $table);
    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $output .= $create . ";\n\n";
    
    // Se é tabela de seed, exportar dados também
    if (isset($seedTables[$table]) && !$structureOnly) {
        $seedOutput .= "-- {$seedTables[$table]}\n";
        $seedOutput .= "-- STRUCTURE: {$table}\n";
        $seedOutput .= "-- --------------------------------------------------------\n";
        $seedOutput .= $create . ";\n\n";
        
        $data = exportTableData($pdo, $table);
        if (!empty($data)) {
            $seedOutput .= "-- Dados da tabela `{$table}`\n";
            $seedOutput .= "-- SEED DATA: {$table}\n";
            foreach ($data as $insert) {
                $seedOutput .= $insert . "\n";
            }
            $seedOutput .= "\n";
        }
    }
}

$output .= "\n-- ========================================================\n";
$output .= "-- DADOS INICIAIS (SEED)\n";
$output .= "-- ========================================================\n\n";
$output .= $seedOutput;

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// Salvar arquivo
$outputFile = __DIR__ . '/storage/estrutura.sql';
file_put_contents($outputFile, $output);

if ($isWeb) {
    echo '<div class="success">✅ Estrutura exportada com sucesso!</div>';
    echo '<div class="info">';
    echo '<p>Tabelas exportadas: ' . count($tables) . '</p>';
    echo '<p>Tabelas com dados (seed): ' . count($seedTables) . '</p>';
    echo '<p>Arquivo: <a href="storage/estrutura.sql">' . $outputFile . '</a></p>';
    echo '</div>';
    echo '<h3>Preview do arquivo:</h3>';
    echo '<pre>' . htmlspecialchars(substr($output, 0, 5000)) . '...</pre>';
    echo '<p><a href="storage/estrutura.sql">Download completo</a></p>';
    echo '</body></html>';
} else {
    echo "✅ Estrutura exportada!\n";
    echo "Arquivo: {$outputFile}\n";
    echo "Tabelas: " . count($tables) . "\n";
    echo "Seed tables: " . count($seedTables) . "\n";
}
