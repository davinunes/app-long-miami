<?php
// Endpoint: /api/notificacoes.php

// --- PASSO 1: Declarar TODOS os headers CORS primeiro ---
// É crucial que 'Authorization' esteja em 'Allow-Headers'
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Adicionamos OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// --- PASSO 2: Lidar com a requisição Preflight (OPTIONS) ---
// Se for uma requisição OPTIONS, apenas retornamos OK (200) e saímos.
// Isso diz ao navegador "Sim, eu aceito os headers que você quer enviar".
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- PASSO 3: AGORA sim, verificamos o token ---
// Se o script chegou aqui, não era um OPTIONS, então deve ser um GET ou POST.
// Agora podemos exigir o token de autorização.
require_once '../verificar_token.php';
$dadosUsuario = verificarTokenEAutorizar();

// --- PASSO 4: O resto do seu script continua normal ---
require_once '../config.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$pdo = getDbConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['message' => 'Falha na conexão com o banco de dados.']);
    exit();
}

switch ($metodo) {
    case 'GET':
        if (isset($_GET['id'])) {
            try {
                $id = (int)$_GET['id'];
                $stmt = $pdo->prepare("SELECT * FROM notificacoes WHERE id = ?");
                $stmt->execute([$id]);
                $notificacao = $stmt->fetch();
                if (!$notificacao) { http_response_code(404); echo json_encode(['message' => 'Notificação não encontrada.']); exit(); }
                $stmt_fatos = $pdo->prepare("SELECT descricao FROM notificacao_fatos WHERE notificacao_id = ? ORDER BY ordem ASC");
                $stmt_fatos->execute([$id]);
                $notificacao['fatos'] = $stmt_fatos->fetchAll(PDO::FETCH_COLUMN);
                $stmt_imagens = $pdo->prepare("SELECT id, caminho_arquivo, nome_original FROM notificacao_imagens WHERE notificacao_id = ? ORDER BY ordem ASC");
                $stmt_imagens->execute([$id]);
                $notificacao['imagens'] = $stmt_imagens->fetchAll();
                http_response_code(200);
                echo json_encode($notificacao);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
            }
        } else if (isset($_GET['proximo_numero'])) {
            try {
                $ano_atual = date('Y');
                $sql = "SELECT MAX(CAST(numero AS UNSIGNED)) as max_num FROM notificacoes WHERE ano = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ano_atual]);
                $resultado = $stmt->fetch();
                $proximo_numero = ($resultado && $resultado['max_num']) ? $resultado['max_num'] + 1 : 1;
                $numero_formatado = sprintf('%03d/%s', $proximo_numero, $ano_atual);
                http_response_code(200);
                echo json_encode(['proximo_numero' => $numero_formatado]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao buscar próximo número: ' . $e->getMessage()]);
            }
        } else {
            try {
                $sql = "SELECT n.id, n.numero, n.ano, n.unidade, n.bloco, a.descricao as assunto, nt.nome as tipo, ns.nome as status, n.data_emissao FROM notificacoes n JOIN assuntos a ON n.assunto_id = a.id JOIN notificacao_tipos nt ON n.tipo_id = nt.id JOIN notificacao_status ns ON n.status_id = ns.id ORDER BY n.id DESC";
                $stmt = $pdo->query($sql);
                $notificacoes = $stmt->fetchAll();
                http_response_code(200);
                echo json_encode($notificacoes);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao listar notificações: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        if (isset($dados->id) && !empty($dados->id)) {
            // --- LÓGICA DE ATUALIZAÇÃO ---
            try {
                $pdo->beginTransaction();
                $id = (int)$dados->id;
                
                if (!empty($dados->imagens_para_deletar)) {
                    $ids_para_deletar = array_map('intval', $dados->imagens_para_deletar);
                    if (!empty($ids_para_deletar)) {
                        $placeholders = implode(',', array_fill(0, count($ids_para_deletar), '?'));
                        $sql_select = "SELECT caminho_arquivo FROM notificacao_imagens WHERE id IN ($placeholders) AND notificacao_id = ?";
                        $stmt_select = $pdo->prepare($sql_select);
                        $params = array_merge($ids_para_deletar, [$id]);
                        $stmt_select->execute($params);
                        $arquivos_para_deletar = $stmt_select->fetchAll(PDO::FETCH_COLUMN);
                        $sql_delete = "DELETE FROM notificacao_imagens WHERE id IN ($placeholders) AND notificacao_id = ?";
                        $stmt_delete = $pdo->prepare($sql_delete);
                        $stmt_delete->execute($params);
                        foreach ($arquivos_para_deletar as $arquivo) {
                            $caminho_completo = UPLOADS_PATH . $arquivo;
                            if (file_exists($caminho_completo)) { unlink($caminho_completo); }
                        }
                    }
                }

                $sql_update = "UPDATE notificacoes SET unidade=?, bloco=?, numero=?, ano=?, data_emissao=?, fundamentacao_legal=?, valor_multa=?, url_recurso=?, assunto_id=?, tipo_id=?, status_id=?, data_atualizacao=CURRENT_TIMESTAMP WHERE id=?";
                $stmt_update = $pdo->prepare($sql_update);
                $partes_numero = explode('/', $dados->numero);
                $stmt_update->execute([ $dados->unidade, $dados->bloco ?? null, $partes_numero[0], $partes_numero[1], $dados->data_emissao, $dados->fundamentacao_legal ?? null, $dados->valor_multa ?? null, $dados->url_recurso ?? null, $dados->assunto_id, $dados->tipo_id, $dados->status_id, $id ]);
                
                $pdo->prepare("DELETE FROM notificacao_fatos WHERE notificacao_id = ?")->execute([$id]);
                if (!empty($dados->fatos)) {
                    $sql_fatos = "INSERT INTO notificacao_fatos (notificacao_id, descricao, ordem) VALUES (?, ?, ?)";
                    $stmt_fatos = $pdo->prepare($sql_fatos);
                    foreach ($dados->fatos as $ordem => $descricao) { $stmt_fatos->execute([$id, $descricao, $ordem]); }
                }

                if (!empty($dados->fotos_fatos)) {
                    $sql_imagens = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ordem) VALUES (?, ?, ?, ?)";
                    $stmt_imagens = $pdo->prepare($sql_imagens);
                    foreach ($dados->fotos_fatos as $ordem => $foto) {
                        if(!isset($foto->b64)) continue; // Pula imagens já existentes
                        $dados_imagem = base64_decode($foto->b64);
                        $nome_arquivo = uniqid('img_' . $id . '_', true) . '.jpg';
                        $caminho_completo = UPLOADS_PATH . $nome_arquivo;
                        if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0755, true); }
                        if (file_put_contents($caminho_completo, $dados_imagem)) {
                            $stmt_imagens->execute([$id, $nome_arquivo, $foto->name, $ordem]);
                        } else { throw new Exception("Não foi possível salvar a nova imagem."); }
                    }
                }
                
                $pdo->commit();
                http_response_code(200);
                echo json_encode(['message' => 'Notificação atualizada com sucesso!', 'id' => $id]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao atualizar notificação: ' . $e->getMessage()]);
            }
        } else {
            // --- LÓGICA DE CRIAÇÃO ---
            try {
                if (empty($dados->numero) || empty($dados->unidade) || empty($dados->assunto_id)) {
                    throw new Exception("Dados incompletos.");
                }
                $pdo->beginTransaction();
                $sql = "INSERT INTO notificacoes (unidade, bloco, numero, ano, data_emissao, cidade_emissao, fundamentacao_legal, texto_descritivo, valor_multa, url_recurso, prazo_recurso, assunto_id, tipo_id, status_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $partes_numero = explode('/', $dados->numero);
                $stmt->execute([ $dados->unidade, $dados->bloco ?? null, $partes_numero[0], $partes_numero[1], $dados->data_emissao, $dados->cidade_emissao ?? null, $dados->fundamentacao_legal ?? null, null, $dados->valor_multa ?? null, $dados->url_recurso ?? null, $dados->prazo_recurso ?? 5, $dados->assunto_id, $dados->tipo_id, 1 ]);
                $notificacao_id = $pdo->lastInsertId();
                if (!empty($dados->fatos)) {
                    $sql_fatos = "INSERT INTO notificacao_fatos (notificacao_id, descricao, ordem) VALUES (?, ?, ?)";
                    $stmt_fatos = $pdo->prepare($sql_fatos);
                    foreach ($dados->fatos as $ordem => $descricao) { $stmt_fatos->execute([$notificacao_id, $descricao, $ordem]); }
                }
                if (!empty($dados->fotos_fatos)) {
                    $sql_imagens = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ordem) VALUES (?, ?, ?, ?)";
                    $stmt_imagens = $pdo->prepare($sql_imagens);
                    foreach ($dados->fotos_fatos as $ordem => $foto) {
                        $dados_imagem = base64_decode($foto->b64);
                        $nome_arquivo = uniqid('img_' . $notificacao_id . '_', true) . '.jpg';
                        $caminho_completo = UPLOADS_PATH . $nome_arquivo;
                        if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0755, true); }
                        if (file_put_contents($caminho_completo, $dados_imagem)) {
                            $stmt_imagens->execute([$notificacao_id, $nome_arquivo, $foto->name, $ordem]);
                        } else { throw new Exception("Não foi possível salvar a imagem."); }
                    }
                }
                $pdo->commit();
                http_response_code(201);
                echo json_encode(['message' => 'Notificação criada com sucesso!', 'id' => $notificacao_id]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['message' => 'Erro ao criar notificação: ' . $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}
?>