<?php
// Endpoint: /api/notificacoes.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../api/helpers.php';
$usuario = requireApiLogin();

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
            buscarNotificacao($pdo, (int)$_GET['id'], $usuario);
        } elseif (isset($_GET['proximo_numero'])) {
            buscarProximoNumero($pdo);
        } elseif (isset($_GET['buscar_ocorrencias'])) {
            buscarOcorrenciasParaVincular($pdo, $_GET['buscar_ocorrencias']);
        } else {
            listarNotificacoes($pdo);
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        if (isset($dados->action) && $dados->action === 'sincronizar_evidencias') {
            sincronizarEvidencias($pdo, $dados, $usuario);
        } elseif (isset($dados->deletar_imagem)) {
            deletarImagem($pdo, $dados, $usuario);
        } elseif (isset($dados->vincular_evidencias)) {
            vincularEvidencias($pdo, $dados, $usuario);
        } elseif (isset($dados->id) && !empty($dados->id)) {
            atualizarNotificacao($pdo, $dados, $usuario);
        } else {
            criarNotificacao($pdo, $dados, $usuario);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Método não permitido.']);
        break;
}

function buscarNotificacao($pdo, $id, $usuario) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, o.titulo as ocorrencia_titulo, o.fase as ocorrencia_fase, o.id as ocorrencia_id
            FROM notificacoes n 
            LEFT JOIN ocorrencias o ON n.ocorrencia_id = o.id 
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $notificacao = $stmt->fetch();
        
        if (!$notificacao) {
            http_response_code(404);
            echo json_encode(['message' => 'Notificação não encontrada.']);
            exit();
        }
        
        if (!$notificacao['ocorrencia_id']) {
            $stmt_link = $pdo->prepare("
                SELECT ocorrencia_id FROM ocorrencia_notificacoes 
                WHERE notificacao_id = ? LIMIT 1
            ");
            $stmt_link->execute([$id]);
            $link = $stmt_link->fetch();
            if ($link) {
                $notificacao['ocorrencia_id'] = $link['ocorrencia_id'];
                $stmt_oc = $pdo->prepare("SELECT titulo, fase FROM ocorrencias WHERE id = ?");
                $stmt_oc->execute([$link['ocorrencia_id']]);
                $oc = $stmt_oc->fetch();
                if ($oc) {
                    $notificacao['ocorrencia_titulo'] = $oc['titulo'];
                    $notificacao['ocorrencia_fase'] = $oc['fase'];
                }
            }
        }
        
        $stmt_fatos = $pdo->prepare("SELECT descricao FROM notificacao_fatos WHERE notificacao_id = ? ORDER BY ordem ASC");
        $stmt_fatos->execute([$id]);
        $notificacao['fatos'] = $stmt_fatos->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt_imagens = $pdo->prepare("
            SELECT id, caminho_arquivo, nome_original, inactive, ocorrencia_id, anexo_ocorrencia_id 
            FROM notificacao_imagens 
            WHERE notificacao_id = ? AND (inactive = 0 OR inactive IS NULL)
            ORDER BY ordem ASC
        ");
        $stmt_imagens->execute([$id]);
        $notificacao['imagens'] = $stmt_imagens->fetchAll();
        
        if ($notificacao['ocorrencia_id']) {
            $stmt_todas_evidencias = $pdo->prepare("
                SELECT id, url, nome_original, tipo, created_at
                FROM ocorrencia_anexos
                WHERE ocorrencia_id = ? AND tipo = 'imagem' AND (inactive = 0 OR inactive IS NULL)
                ORDER BY created_at DESC
            ");
            $stmt_todas_evidencias->execute([$notificacao['ocorrencia_id']]);
            $notificacao['todas_evidencias_ocorrencia'] = $stmt_todas_evidencias->fetchAll();
        }
        
        http_response_code(200);
        echo json_encode($notificacao);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
    }
}

function buscarProximoNumero($pdo) {
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
}

function buscarOcorrenciasParaVincular($pdo, $busca) {
    try {
        $sql = "
            SELECT o.id, o.titulo, o.fase, o.data_fato, o.data_criacao, u.nome as autor_nome,
                   GROUP_CONCAT(CONCAT(COALESCE(ou.unidade_bloco, ''), ou.unidade_numero) SEPARATOR ', ') as unidades
            FROM ocorrencias o
            LEFT JOIN usuarios u ON o.created_by = u.id
            LEFT JOIN ocorrencia_unidades ou ON o.id = ou.ocorrencia_id
            WHERE o.fase = 'homologada' AND o.notificacao_id IS NULL
        ";
        
        $params = [];
        if ($busca) {
            $sql .= " AND (o.titulo LIKE ? OR o.descricao_fato LIKE ?)";
            $params = ['%' . $busca . '%', '%' . $busca . '%'];
        }
        
        $sql .= " GROUP BY o.id ORDER BY o.data_criacao DESC LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode($stmt->fetchAll());
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao buscar ocorrências: ' . $e->getMessage()]);
    }
}

function listarNotificacoes($pdo) {
    try {
        $sql = "SELECT n.id, n.numero, n.ano, n.unidade, n.bloco, a.descricao as assunto, nt.nome as tipo, ns.nome as status, n.data_emissao, n.ocorrencia_id 
                FROM notificacoes n 
                JOIN assuntos a ON n.assunto_id = a.id 
                JOIN notificacao_tipos nt ON n.tipo_id = nt.id 
                JOIN notificacao_status ns ON n.status_id = ns.id 
                ORDER BY n.id DESC";
        $stmt = $pdo->query($sql);
        http_response_code(200);
        echo json_encode($stmt->fetchAll());
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao listar notificações: ' . $e->getMessage()]);
    }
}

function deletarImagem($pdo, $dados, $usuario) {
    $id = (int)$dados->id;
    
    $stmt = $pdo->prepare("SELECT * FROM notificacao_imagens WHERE id = ?");
    $stmt->execute([$id]);
    $imagem = $stmt->fetch();
    
    if (!$imagem) {
        http_response_code(404);
        echo json_encode(['message' => 'Imagem não encontrada.']);
        exit();
    }
    
    try {
        if (!empty($imagem['ocorrencia_id']) || !empty($imagem['anexo_ocorrencia_id'])) {
            $stmt = $pdo->prepare("UPDATE notificacao_imagens SET inactive = 1, deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            http_response_code(200);
            echo json_encode(['message' => 'Imagem removida da notificação (mantida na ocorrência).', 'soft_delete' => true]);
        } else {
            $caminho_completo = UPLOADS_PATH . $imagem['caminho_arquivo'];
            if (file_exists($caminho_completo)) {
                unlink($caminho_completo);
            }
            $stmt = $pdo->prepare("DELETE FROM notificacao_imagens WHERE id = ?");
            $stmt->execute([$id]);
            http_response_code(200);
            echo json_encode(['message' => 'Imagem excluída permanently.', 'soft_delete' => false]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao deletar imagem: ' . $e->getMessage()]);
    }
}

function vincularEvidencias($pdo, $dados, $usuario) {
    $notificacao_id = (int)$dados->notificacao_id;
    $evidencias = $dados->evidencias ?? [];
    
    if (empty($notificacao_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID da notificação é obrigatório.']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt_remover = $pdo->prepare("UPDATE evidencia_compartilhada SET inactive = 1 WHERE notificacao_id = ?");
        $stmt_remover->execute([$notificacao_id]);
        
        foreach ($evidencias as $evidencia_id) {
            $stmt_check = $pdo->prepare("SELECT id FROM evidencia_compartilhada WHERE ocorrencia_anexo_id = ? AND notificacao_id = ?");
            $stmt_check->execute([(int)$evidencia_id, $notificacao_id]);
            $existe = $stmt_check->fetch();
            
            if ($existe) {
                $stmt_ativa = $pdo->prepare("UPDATE evidencia_compartilhada SET inactive = 0 WHERE id = ?");
                $stmt_ativa->execute([$existe['id']]);
            } else {
                $stmt_insere = $pdo->prepare("INSERT INTO evidencia_compartilhada (ocorrencia_anexo_id, notificacao_id) VALUES (?, ?)");
                $stmt_insere->execute([(int)$evidencia_id, $notificacao_id]);
            }
        }
        
        $pdo->commit();
        http_response_code(200);
        echo json_encode(['message' => 'Evidências vinculadas com sucesso!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao vincular evidências: ' . $e->getMessage()]);
    }
}

function atualizarNotificacao($pdo, $dados, $usuario) {
    try {
        $pdo->beginTransaction();
        $id = (int)$dados->id;
        
        $partes_numero = explode('/', $dados->numero ?? '');
        
        if (!empty($dados->imagens_para_deletar)) {
            $ids_para_deletar = array_map('intval', $dados->imagens_para_deletar);
            if (!empty($ids_para_deletar)) {
                foreach ($ids_para_deletar as $img_id) {
                    $stmt_img = $pdo->prepare("SELECT * FROM notificacao_imagens WHERE id = ? AND notificacao_id = ?");
                    $stmt_img->execute([$img_id, $id]);
                    $imagem = $stmt_img->fetch();
                    
                    if ($imagem) {
                        if (!empty($imagem['ocorrencia_id']) || !empty($imagem['anexo_ocorrencia_id'])) {
                            $stmt_update = $pdo->prepare("UPDATE notificacao_imagens SET inactive = 1, deleted_at = NOW() WHERE id = ?");
                            $stmt_update->execute([$img_id]);
                        } else {
                            $caminho_completo = UPLOADS_PATH . $imagem['caminho_arquivo'];
                            if (file_exists($caminho_completo)) {
                                unlink($caminho_completo);
                            }
                            $stmt_delete = $pdo->prepare("DELETE FROM notificacao_imagens WHERE id = ?");
                            $stmt_delete->execute([$img_id]);
                        }
                    }
                }
            }
        }

        $sql_update = "UPDATE notificacoes SET unidade=?, bloco=?, numero=?, ano=?, data_emissao=?, fundamentacao_legal=?, valor_multa=?, url_recurso=?, assunto_id=?, tipo_id=?, status_id=?, ocorrencia_id=?, data_atualizacao=CURRENT_TIMESTAMP WHERE id=?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([ 
            $dados->unidade, 
            $dados->bloco ?? null, 
            $partes_numero[0] ?? $dados->numero, 
            $partes_numero[1] ?? date('Y'), 
            $dados->data_emissao, 
            $dados->fundamentacao_legal ?? null, 
            $dados->valor_multa ?? null, 
            $dados->url_recurso ?? null, 
            $dados->assunto_id, 
            $dados->tipo_id, 
            $dados->status_id, 
            $dados->ocorrencia_id ?? null, 
            $id 
        ]);
        
        $pdo->prepare("DELETE FROM notificacao_fatos WHERE notificacao_id = ?")->execute([$id]);
        if (!empty($dados->fatos)) {
            $sql_fatos = "INSERT INTO notificacao_fatos (notificacao_id, descricao, ordem) VALUES (?, ?, ?)";
            $stmt_fatos = $pdo->prepare($sql_fatos);
            foreach ($dados->fatos as $ordem => $descricao) { 
                $stmt_fatos->execute([$id, $descricao, $ordem]); 
            }
        }

        if (!empty($dados->fotos_fatos)) {
            $sql_imagens = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ordem) VALUES (?, ?, ?, ?)";
            $stmt_imagens = $pdo->prepare($sql_imagens);
            foreach ($dados->fotos_fatos as $ordem => $foto) {
                if(!isset($foto->b64)) continue;
                $dados_imagem = base64_decode($foto->b64);
                $nome_arquivo = uniqid('img_' . $id . '_', true) . '.jpg';
                $caminho_completo = UPLOADS_PATH . $nome_arquivo;
                if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0755, true); }
                if (file_put_contents($caminho_completo, $dados_imagem)) {
                    $stmt_imagens->execute([$id, $nome_arquivo, $foto->name, $ordem]);
                } else { 
                    throw new Exception("Não foi possível salvar a nova imagem."); 
                }
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
}

function criarNotificacao($pdo, $dados, $usuario) {
    try {
        if (empty($dados->numero) || empty($dados->unidade) || empty($dados->assunto_id)) {
            throw new Exception("Dados incompletos.");
        }
        
        $partes_numero = explode('/', $dados->numero);
        
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO notificacoes (unidade, bloco, numero, ano, data_emissao, cidade_emissao, fundamentacao_legal, texto_descritivo, valor_multa, url_recurso, prazo_recurso, assunto_id, tipo_id, status_id, ocorrencia_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([ 
            $dados->unidade, 
            $dados->bloco ?? null, 
            $partes_numero[0], 
            $partes_numero[1] ?? date('Y'), 
            $dados->data_emissao, 
            $dados->cidade_emissao ?? null, 
            $dados->fundamentacao_legal ?? null, 
            null, 
            $dados->valor_multa ?? null, 
            $dados->url_recurso ?? null, 
            $dados->prazo_recurso ?? 5, 
            $dados->assunto_id, 
            $dados->tipo_id, 
            1, 
            $dados->ocorrencia_id ?? null 
        ]);
        $notificacao_id = $pdo->lastInsertId();
        
        if (!empty($dados->ocorrencia_id)) {
            $pdo->prepare("UPDATE ocorrencias SET notificacao_id = ? WHERE id = ?")->execute([$notificacao_id, $dados->ocorrencia_id]);
            $pdo->prepare("INSERT INTO ocorrencia_notificacoes (ocorrencia_id, notificacao_id, tipo_vinculo) VALUES (?, ?, 'gerada')")->execute([$dados->ocorrencia_id, $notificacao_id]);
            
            $stmt_anexos = $pdo->prepare("SELECT * FROM ocorrencia_anexos WHERE ocorrencia_id = ? AND inactive = 0");
            $stmt_anexos->execute([$dados->ocorrencia_id]);
            $anexos = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($anexos)) {
                if (!is_dir(UPLOADS_PATH)) { mkdir(UPLOADS_PATH, 0755, true); }
                
                $sql_copia_img = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ocorrencia_id, anexo_ocorrencia_id) VALUES (?, ?, ?, ?, ?)";
                $stmt_copia_img = $pdo->prepare($sql_copia_img);
                
                $sql_evidencia = "INSERT INTO evidencia_compartilhada (ocorrencia_anexo_id, notificacao_id) VALUES (?, ?)";
                $stmt_evidencia = $pdo->prepare($sql_evidencia);
                
                foreach ($anexos as $anexo) {
                    $nome_arquivo = uniqid('img_notif_' . $notificacao_id . '_', true) . '.jpg';
                    $caminho_origem = dirname(__DIR__) . '/' . $anexo['url'];
                    $caminho_destino = UPLOADS_PATH . $nome_arquivo;
                    
                    if (file_exists($caminho_origem)) {
                        if (copy($caminho_origem, $caminho_destino)) {
                            $stmt_copia_img->execute([
                                $notificacao_id,
                                $nome_arquivo,
                                $anexo['nome_original'],
                                $dados->ocorrencia_id,
                                $anexo['id']
                            ]);
                            $stmt_evidencia->execute([$anexo['id'], $notificacao_id]);
                        }
                    }
                }
            }
        }
        
        if (!empty($dados->fatos)) {
            $sql_fatos = "INSERT INTO notificacao_fatos (notificacao_id, descricao, ordem) VALUES (?, ?, ?)";
            $stmt_fatos = $pdo->prepare($sql_fatos);
            foreach ($dados->fatos as $ordem => $descricao) { 
                $stmt_fatos->execute([$notificacao_id, $descricao, $ordem]); 
            }
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
                } else { 
                    throw new Exception("Não foi possível salvar a imagem."); 
                }
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

function sincronizarEvidencias($pdo, $dados, $usuario) {
    try {
        $notificacao_id = (int)$dados->notificacao_id;
        $ocorrencia_id = (int)$dados->ocorrencia_id;
        
        $stmt_check = $pdo->prepare("SELECT id FROM notificacoes WHERE id = ? AND ocorrencia_id = ?");
        $stmt_check->execute([$notificacao_id, $ocorrencia_id]);
        if (!$stmt_check->fetch()) {
            throw new Exception("Notificação não está vinculada a esta ocorrência.");
        }
        
        $stmt_anexos = $pdo->prepare("SELECT * FROM ocorrencia_anexos WHERE ocorrencia_id = ? AND tipo = 'imagem' AND inactive = 0");
        $stmt_anexos->execute([$ocorrencia_id]);
        $anexos = $stmt_anexos->fetchAll();
        
        $stmt_existente = $pdo->prepare("SELECT anexo_ocorrencia_id FROM notificacao_imagens WHERE notificacao_id = ? AND anexo_ocorrencia_id IS NOT NULL");
        $stmt_existente->execute([$notificacao_id]);
        $ja_vinculados = array_column($stmt_existente->fetchAll(), 'anexo_ocorrencia_id');
        
        $sql_img = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ocorrencia_id, anexo_ocorrencia_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_img = $pdo->prepare($sql_img);
        
        $sql_evidencia = "INSERT IGNORE INTO evidencia_compartilhada (ocorrencia_anexo_id, notificacao_id) VALUES (?, ?)";
        $stmt_evidencia = $pdo->prepare($sql_evidencia);
        
        $count = 0;
        foreach ($anexos as $anexo) {
            if (in_array($anexo['id'], $ja_vinculados)) {
                continue;
            }
            
            $caminho_relativo = ltrim($anexo['url'], '/');
            
            $stmt_img->execute([
                $notificacao_id,
                $caminho_relativo,
                $anexo['nome_original'],
                $ocorrencia_id,
                $anexo['id']
            ]);
            
            $stmt_evidencia->execute([$anexo['id'], $notificacao_id]);
            $count++;
        }
        
        http_response_code(200);
        echo json_encode(['message' => 'Evidências sincronizadas com sucesso!', 'images_count' => $count]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao sincronizar evidências: ' . $e->getMessage()]);
    }
}
?>
