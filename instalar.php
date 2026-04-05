<?php
/**
 * INSTALADOR DO SISTEMA
 * 
 * Este script:
 * 1. Destrói e recria o banco de dados
 * 2. Executa a estrutura do storage/estrutura.sql
 * 3. Cria o usuário admin inicial
 * 
 * Uso:
 *   - CLI: php instalar.php [--force]
 *   - Navegador: acessar diretamente (com confirmação)
 */

require_once __DIR__ . '/config.php';

define('ADMIN_EMAIL', 'admin@admin.com');
define('ADMIN_SENHA', 'admin123');
define('ADMIN_NOME', 'Administrador');

$isWeb = php_sapi_name() !== 'cli';
$confirmado = false;

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador do Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #eee; }
        .danger { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #28a745; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .warning { background: #ffc107; color: #333; padding: 20px; border-radius: 8px; margin: 20px 0; }
        pre { background: #16213e; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
        .btn { display: inline-block; padding: 12px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        h1 { color: #e94560; }
        ul { line-height: 2; }
        .info { background: #17a2b8; padding: 15px; border-radius: 8px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>🚀 Instalador do Sistema</h1>
';
    
    if (isset($_POST['confirmar'])) {
        $confirmado = true;
    }
    
    if (!$confirmado) {
        echo '<div class="danger">
            <h2>⚠️ AVISO: OPERAÇÃO DESTRUTIVA!</h2>
            <p>Este script IRÁ:</p>
            <ul>
                <li>Destruir o banco de dados atual</li>
                <li>Recriar todas as tabelas</li>
                <li>Popular dados iniciais (permissões, status, tipos, etc)</li>
                <li>Criar usuário admin padrão</li>
            </ul>
            <p><strong>Todos os dados serão PERDIDOS!</strong></p>
        </div>';
        
        echo '<form method="POST">
            <p>Digite "INSTALAR" para confirmar:</p>
            <input type="text" name="confirmar" placeholder="Digite INSTALAR..." style="width: 100%; padding: 10px; margin: 10px 0; font-size: 16px;">
            <button type="submit" class="btn">INSTALAR SISTEMA</button>
        </form>';
        echo '</body></html>';
        exit;
    }
    
    echo '<div class="warning"><h2>⏳ Instalando...</h2><pre>';
    ob_flush();
    flush();
} else {
    if ($argc < 2 || $argv[1] !== '--force') {
        echo "===========================================\n";
        echo "   🚀 INSTALADOR DO SISTEMA\n";
        echo "===========================================\n\n";
        echo "Este script IRÁ:\n";
        echo "1. Destruir o banco de dados\n";
        echo "2. Recriar todas as tabelas\n";
        echo "3. Popular dados iniciais\n";
        echo "4. Criar usuário admin\n\n";
        echo "Use: php instalar.php --force\n";
        echo "Ou: php instalar.php --force INSTALAR\n";
        exit;
    }
    
    if ($argc < 3 || $argv[2] !== 'INSTALAR') {
        echo "Digite 'php instalar.php --force INSTALAR' para confirmar.\n";
        exit(1);
    }
    
    echo "\n>>> INICIANDO INSTALAÇÃO...\n\n";
}

$dbname = DB_NAME;
$output = function($msg) use ($isWeb) {
    if ($isWeb) {
        echo htmlspecialchars($msg) . "<br>\n";
    } else {
        echo $msg . "\n";
    }
};

// ===========================================
// PARTE 1: DESTRUIR E RECRIAR BANCO
// ===========================================

$output("[1/5] Conectando ao MySQL...");
try {
    $dsnSemDb = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdoMaster = new PDO($dsnSemDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $output("    ✓ Conectado!");
} catch (PDOException $e) {
    die("ERRO: " . $e->getMessage() . "\n");
}

$output("[2/5] Destruindo banco '{$dbname}'...");
$pdoMaster->exec("DROP DATABASE IF EXISTS `{$dbname}`");
$output("    ✓ Banco destruído.");

$output("    Recriando banco '{$dbname}'...");
$pdoMaster->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$output("    ✓ Banco criado.");

$pdo = getDbConnection();
if (!$pdo) {
    die("ERRO: Não foi possível reconectar ao banco.\n");
}

// ===========================================
// PARTE 2: EXECUTAR ESTRUTURA.SQL
// ===========================================

$estruturaFile = __DIR__ . '/storage/estrutura.sql';
if (!file_exists($estruturaFile)) {
    die("ERRO: Arquivo storage/estrutura.sql não encontrado!\n");
}

$output("[3/5] Executando estrutura.sql...");
$sql = file_get_contents($estruturaFile);

// Remover comandos perigosos
$sql = preg_replace('/SET FOREIGN_KEY_CHECKS = 0;/i', '', $sql);
$sql = preg_replace('/SET FOREIGN_KEY_CHECKS = 1;/i', '', $sql);
$sql = preg_replace('/SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";/i', '', $sql);

// Função para separar statements SQL corretamente (ignora ; dentro de strings)
function splitSqlStatements($sql) {
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);
    $i = 0;
    
    while ($i < $len) {
        $char = $sql[$i];
        $nextChar = $i + 1 < $len ? $sql[$i + 1] : '';
        
        // Detectar início/fim de string
        if (!$inString && ($char === "'" || $char === '"')) {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
        } elseif ($inString && $char === $stringChar && $nextChar === $stringChar) {
            // Escape de aspas ('' ou "")
            $current .= $char . $nextChar;
            $i += 2;
            continue;
        } elseif ($inString && $char === $stringChar) {
            $inString = false;
            $current .= $char;
        } elseif (!$inString && $char === ';' && $nextChar === ';') {
            // ;; escaping
            $current .= ';';
            $i += 2;
            continue;
        } elseif (!$inString && $char === ';') {
            $stmt = trim($current);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current = '';
        } else {
            $current .= $char;
        }
        
        $i++;
    }
    
    // Último statement
    $stmt = trim($current);
    if (!empty($stmt)) {
        $statements[] = $stmt;
    }
    
    return $statements;
}

$statements = splitSqlStatements($sql);

// DEBUG
$output("    DEBUG: Total statements após split: " . count($statements));
$output("    DEBUG: Tamanho do SQL: " . strlen($sql) . " bytes");

// Filtrar statements relevantes
$createStatements = [];
$insertStatements = [];
$otherStatements = [];

foreach ($statements as $statement) {
    $stmt = trim($statement);
    if (empty($stmt)) continue;
    
    // Pular comentários de linha
    if (strpos($stmt, '--') === 0) continue;
    
    if (stripos($stmt, 'CREATE TABLE') !== false) {
        $createStatements[] = $stmt;
    } elseif (stripos($stmt, 'INSERT INTO') !== false) {
        $insertStatements[] = $stmt;
    } else {
        $otherStatements[] = $stmt;
    }
}

$output("    DEBUG: CREATE TABLE encontrados: " . count($createStatements));
$output("    DEBUG: INSERT INTO encontrados: " . count($insertStatements));
$output("    DEBUG: Outros statements: " . count($otherStatements));

// Mostrar primeiros outros statements se houver
if (count($otherStatements) > 0 && count($otherStatements) <= 10) {
    $output("    DEBUG: Mostrando outros statements:");
    foreach ($otherStatements as $idx => $s) {
        $preview = substr($s, 0, 100);
        $output("      [$idx]: " . $preview . "...");
    }
}

// Extrair dependências de cada tabela
function getTableDependencies($sql) {
    $deps = [];
    if (preg_match('/CREATE TABLE `?(\w+)`?/i', $sql, $matches)) {
        $table = $matches[1];
        // Buscar todas as REFERENCES para outras tabelas
        if (preg_match_all('/REFERENCES `(\w+)`/i', $sql, $refMatches)) {
            foreach ($refMatches[1] as $ref) {
                if ($ref !== $table) {
                    $deps[] = $ref;
                }
            }
        }
    }
    return $deps;
}

// Remover FOREIGN KEY constraints de um CREATE TABLE (para criar tabelas primeiro)
function removeForeignKeys($sql) {
    // Remove CONSTRAINT xxx FOREIGN KEY (...) REFERENCES ... ON DELETE...
    $sql = preg_replace('/,\s*CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+[^\)]+(?:\s+ON\s+DELETE[^\,]+)?(?=\s*,?\s*(?:CONSTRAINT|PRIMARY|KEY|\)\s*\;))/', '', $sql);
    // Remove CONSTRAINT no final
    $sql = preg_replace('/,\s*CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+[^\)]+\s+ON\s+DELETE[^\,]+/', '', $sql);
    return $sql;
}

// Extrair FOREIGN KEY constraints para adicionar depois
function extractForeignKeys($sql) {
    $fks = [];
    if (preg_match('/CREATE TABLE `?(\w+)`?/i', $sql, $matches)) {
        $table = $matches[1];
        // Buscar todas as CONSTRAINT FOREIGN KEY
        if (preg_match_all('/CONSTRAINT\s+(\w+)\s+FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+`?(\w+)`?\s*\(([^)]+)\)([^,;]*)/i', $sql, $fkMatches, PREG_SET_ORDER)) {
            foreach ($fkMatches as $fk) {
                $fks[] = [
                    'constraint' => $fk[1],
                    'column' => $fk[2],
                    'ref_table' => $fk[3],
                    'ref_column' => $fk[4],
                    'on_delete' => trim($fk[5])
                ];
            }
        }
    }
    return [$table, $fks];
}

// Ordenar tabelas por dependência (topological sort com detecção de ciclos)
function topologicalSort($tables) {
    $graph = [];
    $inDegree = [];
    $allTables = [];
    
    // Inicializar
    foreach ($tables as $sql) {
        if (preg_match('/CREATE TABLE `?(\w+)`?/i', $sql, $matches)) {
            $table = $matches[1];
            $allTables[] = $table;
            $graph[$table] = getTableDependencies($sql);
            $inDegree[$table] = count(array_filter($graph[$table], function($dep) use ($allTables) {
                return in_array($dep, $allTables);
            }));
        }
    }
    
    // Encontrar tabelas sem dependências
    $queue = [];
    foreach ($inDegree as $table => $degree) {
        if ($degree == 0) {
            $queue[] = $table;
        }
    }
    
    // Processar em ordem
    $sorted = [];
    while (!empty($queue)) {
        $table = array_shift($queue);
        $sorted[] = $table;
        
        // Reduzir grau de entrada das tabelas que dependem desta
        foreach ($graph as $other => $deps) {
            if (in_array($table, $deps)) {
                $inDegree[$other]--;
                if ($inDegree[$other] == 0) {
                    $queue[] = $other;
                }
            }
        }
    }
    
    // Se ainda houver tabelas não processadas, há ciclos
    // Adicionar as restantes sem ordenação (elas terão dependências circulares)
    foreach ($allTables as $table) {
        if (!in_array($table, $sorted)) {
            $sorted[] = $table;
        }
    }
    
    return $sorted;
}

// Ordenar e criar tabelas
$sortedTables = topologicalSort($createStatements);

$tableMap = []; // Mapear nome para SQL
$foreignKeysToAdd = []; // Armazenar FKs para adicionar depois

foreach ($createStatements as $sql) {
    if (preg_match('/CREATE TABLE `?(\w+)`?/i', $sql, $matches)) {
        $tableName = $matches[1];
        $tableMap[$tableName] = $sql;
        
        // Extrair FKs para adicionar depois
        list($table, $fks) = extractForeignKeys($sql);
        if (!empty($fks)) {
            $foreignKeysToAdd[$table] = $fks;
        }
    }
}

$createdTables = [];
$countTables = 0;
$countInserts = 0;

// Criar tabelas SEM foreign keys primeiro
$output("    Criando tabelas (sem FK)...");
foreach ($sortedTables as $tableName) {
    if (!isset($tableMap[$tableName])) continue;
    
    $sql = removeForeignKeys($tableMap[$tableName]);
    
    try {
        $pdo->exec($sql);
        $countTables++;
        $createdTables[] = $tableName;
        $output("      ✓ {$tableName}");
    } catch (PDOException $e) {
        $output("      ✗ {$tableName}: " . $e->getMessage());
    }
}

// Adicionar Foreign Keys depois
if (!empty($foreignKeysToAdd)) {
    $output("    Adicionando Foreign Keys...");
    $fkCount = 0;
    foreach ($foreignKeysToAdd as $table => $fks) {
        foreach ($fks as $fk) {
            $onDelete = !empty($fk['on_delete']) ? 'ON DELETE ' . trim($fk['on_delete']) : '';
            $sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fk['constraint']}` FOREIGN KEY ({$fk['column']}) REFERENCES `{$fk['ref_table']}`({$fk['ref_column']}) {$onDelete}";
            try {
                $pdo->exec($sql);
                $fkCount++;
            } catch (PDOException $e) {
                $output("      ! {$table}.{$fk['constraint']}: " . $e->getMessage());
            }
        }
    }
    $output("      {$fkCount} FK(s) adicionadas");
}

// Inserir dados
$output("    Inserindo dados...");
foreach ($insertStatements as $statement) {
    try {
        $pdo->exec($statement);
        $countInserts++;
    } catch (PDOException $e) {
        // Ignorar duplicatas
        if (strpos($e->getMessage(), 'Duplicate') === false) {
            $output("    AVISO INSERT: " . $e->getMessage());
        }
    }
}

// Resetar AUTO_INCREMENT de todas as tabelas criadas
$output("    Resetando contadores AUTO_INCREMENT...");
foreach ($createdTables as $table) {
    try {
        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
    } catch (PDOException $e) {
        // Ignorar erros de tabelas sem AUTO_INCREMENT
    }
}

// Após inserts, resetar novamente para garantir que próximo ID comece do 1
foreach ($createdTables as $table) {
    try {
        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
    } catch (PDOException $e) {
        // Ignorar
    }
}

$output("    ✓ {$countTables} tabelas criadas");
$output("    ✓ {$countInserts} inserts executados");

// ===========================================
// PARTE 3: CRIAR ADMIN
// ===========================================

$output("[4/5] Criando usuário admin...");

// Verificar se já existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([ADMIN_EMAIL]);
if ($stmt->fetch()) {
    $output("    = Admin já existe, pulando.");
} else {
    // Buscar ID do grupo Admin
    $stmt = $pdo->prepare("SELECT id FROM grupos WHERE nome = 'Admin'");
    $stmt->execute();
    $grupoAdmin = $stmt->fetch();
    
    $grupoAdminId = $grupoAdmin ? $grupoAdmin['id'] : null;
    
    // Criar usuário
    $senhaHash = password_hash(ADMIN_SENHA, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, role, grupo_principal_id, criado_em) 
        VALUES (?, ?, ?, 'admin', ?, NOW())
    ");
    $stmt->execute([ADMIN_NOME, ADMIN_EMAIL, $senhaHash, $grupoAdminId]);
    
    $output("    ✓ Admin criado: " . ADMIN_EMAIL . " / " . ADMIN_SENHA);
}

// ===========================================
// PARTE 4: LIMPAR UPLOADS
// ===========================================

$output("[5/5] Limpando uploads...");

$dirs = [__DIR__ . '/uploads'];
foreach ($dirs as $dir) {
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
        $output("    - {$count} arquivo(s) removido(s)");
    }
}

// Recriar estrutura de diretórios de uploads
$output("    Recriando estrutura de uploads...");
$uploadDirs = [
    __DIR__ . '/uploads/imagens',
    __DIR__ . '/uploads/ocorrencias',
    __DIR__ . '/uploads/config',
];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $output("    + {$dir}");
    }
}

// ===========================================
// RESULTADO
// ===========================================

if ($isWeb) {
    echo '</pre></div>';
    echo '<div class="success">
        <h2>✅ INSTALAÇÃO CONCLUÍDA!</h2>
        <div class="info">
            <p><strong>Usuário Admin:</strong></p>
            <p>Email: ' . ADMIN_EMAIL . '</p>
            <p>Senha: ' . ADMIN_SENHA . '</p>
        </div>
        <p><strong>Resumo:</strong></p>
        <ul>
            <li>Tabelas criadas: ' . $countTables . '</li>
            <li>Registros inseridos: ' . $countInserts . '</li>
        </ul>
        <p><strong>⚠️ NÃO ESQUEÇA DE ALTERAR A SENHA DO ADMIN!</strong></p>
    </div>';
    echo '<a href="login.php" class="btn">Ir para Login</a>';
    echo '</body></html>';
} else {
    echo "\n===========================================\n";
    echo "   ✅ INSTALAÇÃO CONCLUÍDA!\n";
    echo "===========================================\n\n";
    echo "ADMIN:\n";
    echo "  Email: " . ADMIN_EMAIL . "\n";
    echo "  Senha: " . ADMIN_SENHA . "\n\n";
    echo "Resumo:\n";
    echo "  - Tabelas: {$countTables}\n";
    echo "  - Inserts: {$countInserts}\n\n";
    echo "⚠️ ALTERE A SENHA DO ADMIN APÓS O PRIMEIRO LOGIN!\n";
}
