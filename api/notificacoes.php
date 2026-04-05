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
$pdo = getDbConnection();

// Verificar permissões
$isAdminDev = in_array($usuario['role'], ['admin', 'dev']);
$podeListar = $isAdminDev || temPermissao('notificacao.listar') || temPermissao('notificacao.listar_lavradas') || temPermissao('notificacao.listar_enviadas') || temPermissao('notificacao.listar_em_cobranca');
$podeCriar = $isAdminDev || temPermissao('notificacao.criar');
$podeVerDetalhes = $isAdminDev || temPermissao('notificacao.ver');
$podeEditar = $isAdminDev || temPermissao('notificacao.editar');
$podeExcluir = $isAdminDev || temPermissao('notificacao.excluir');
$podeAltFase = $isAdminDev || temPermissao('notificacao.alterar_fase') || temPermissao('notificacao.acao_rapida');
$podeLavrar = $isAdminDev || temPermissao('notificacao.lavrar');
$podeEnviar = $isAdminDev || temPermissao('notificacao.marcar_enviada');
$podeRegistrarCiencia = $isAdminDev || temPermissao('notificacao.registrar_ciencia');
$podeEncerrar = $isAdminDev || temPermissao('notificacao.encerrar');
$podeMarcarCobranca = $isAdminDev || temPermissao('notificacao.marcar_cobranca');
$podeReabrir = $isAdminDev || temPermissao('notificacao.reabrir');

if ($metodo === 'GET' && !isset($_GET['id']) && !isset($_GET['proximo_numero']) && !isset($_GET['buscar_ocorrencias']) && !$podeListar) {
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente para listar notificações.']);
    exit();
}

switch ($metodo) {
    case 'GET':
        if (isset($_GET['id'])) {
            if (!$podeVerDetalhes) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para ver detalhes.']);
                exit();
            }
            buscarNotificacao($pdo, (int)$_GET['id'], $usuario);
        } elseif (isset($_GET['proximo_numero'])) {
            buscarProximoNumero($pdo);
        } elseif (isset($_GET['buscar_ocorrencias'])) {
            if (!$podeCriar) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para buscar ocorrências.']);
                exit();
            }
            buscarOcorrenciasParaVincular($pdo, $_GET['buscar_ocorrencias']);
        } else {
            listarNotificacoes($pdo, $usuario);
        }
        break;

    case 'POST':
        $dados = json_decode(file_get_contents("php://input"));
        
        if (isset($dados->action) && $dados->action === 'sincronizar_evidencias') {
            if (!$isAdminDev && !temPermissao('notificacao.imagem.sincronizar')) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente.']);
                exit();
            }
            sincronizarEvidencias($pdo, $dados, $usuario);
        } elseif (isset($dados->mudar_fase)) {
            if (!$podeAltFase) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para alterar fase.']);
                exit();
            }
            mudarFase($pdo, $dados, $usuario);
        } elseif (isset($dados->deletar_imagem)) {
            if (!$isAdminDev && !temPermissao('notificacao.imagem.remover')) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente.']);
                exit();
            }
            deletarImagem($pdo, $dados, $usuario);
        } elseif (isset($dados->vincular_evidencias)) {
            if (!$isAdminDev && !temPermissao('notificacao.imagem.anexar')) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente.']);
                exit();
            }
            vincularEvidencias($pdo, $dados, $usuario);
        } elseif (isset($dados->alternar_imagem_ocorrencia)) {
            $id = (int)$dados->id;
            $status = (int)$dados->status;
            $stmt = $pdo->prepare("UPDATE notificacao_imagens SET inactive = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            http_response_code(200);
            echo json_encode(['message' => 'Status da imagem atualizado.']);
            exit;
        } elseif (isset($dados->quick_edit)) {
            if (!$isAdminDev && !temPermissao('notificacao.editar_datas')) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para editar datas.']);
                exit();
            }
            quickEditNotificacao($pdo, $dados);
        } elseif (isset($dados->action) && $dados->action === 'criar_tipo') {
            if (!$isAdminDev && !temPermissao('notificacao.criar')) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente.']);
                exit();
            }
            criarTipoNotificacao($pdo, $dados);
        } elseif (isset($dados->action) && $dados->action === 'excluir') {
            if (!$isAdminDev && !temPermissao('notificacao.excluir')) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para excluir.']);
                exit();
            }
            $id = (int)($dados->id ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'ID da notificação não fornecido.']);
                exit();
            }
            $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id = ?");
            $stmt->execute([$id]);
            http_response_code(200);
            echo json_encode(['message' => 'Notificação excluída com sucesso.']);
        } elseif (isset($dados->id) && !empty($dados->id)) {
            if (!$podeEditar) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para editar.']);
                exit();
            }
            atualizarNotificacao($pdo, $dados, $usuario);
        } else {
            if (!$podeCriar) {
                http_response_code(403);
                echo json_encode(['message' => 'Permissão insuficiente para criar.']);
                exit();
            }
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
            SELECT n.*, o.titulo as ocorrencia_titulo, o.fase as ocorrencia_fase, o.id as ocorrencia_id,
                   u_lav.nome as lavrada_por_nome, ns.slug as status_slug, ns.nome as status_nome,
                   nt.nome as tipo_nome
            FROM notificacoes n 
            LEFT JOIN ocorrencias o ON n.ocorrencia_id = o.id 
            LEFT JOIN usuarios u_lav ON n.lavrada_por = u_lav.id
            LEFT JOIN notificacao_status ns ON n.status_id = ns.id
            LEFT JOIN notificacao_tipos nt ON n.tipo_id = nt.id
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $notificacao = $stmt->fetch();
        
        if (!$notificacao) {
            http_response_code(404);
            echo json_encode(['message' => 'Notificação não encontrada.']);
            exit();
        }

        // Histórico de fases
        $stmt_log = $pdo->prepare("
            SELECT fl.*, u.nome as usuario_nome 
            FROM notificacao_fase_log fl 
            LEFT JOIN usuarios u ON fl.usuario_id = u.id 
            WHERE fl.notificacao_id = ? 
            ORDER BY fl.created_at DESC
        ");
        $stmt_log->execute([$id]);
        $notificacao['fase_log'] = $stmt_log->fetchAll();
        
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
        
        $stmt_artigos = $pdo->prepare("
            SELECT artigo_notacao as notation, artigo_texto as text, tipo
            FROM notificacao_artigos 
            WHERE notificacao_id = ?
        ");
        $stmt_artigos->execute([$id]);
        $notificacao['artigos'] = $stmt_artigos->fetchAll();
        
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

function listarNotificacoes($pdo, $usuario) {
    global $isAdminDev;
    
    $podeListarLavradas = $isAdminDev || temPermissao('notificacao.listar_lavradas');
    $podeListarEnviadas = $isAdminDev || temPermissao('notificacao.listar_enviadas');
    $podeListarCobranca = $isAdminDev || temPermissao('notificacao.listar_em_cobranca');
    
    try {
        $sql = "SELECT n.id, n.numero, n.ano, n.unidade, n.bloco, a.descricao as assunto, nt.nome as tipo, ns.nome as status, ns.slug as status_slug, n.data_emissao, n.ocorrencia_id, n.data_ciencia, n.data_envio, n.recurso_status
                FROM notificacoes n 
                JOIN assuntos a ON n.assunto_id = a.id 
                JOIN notificacao_tipos nt ON n.tipo_id = nt.id 
                JOIN notificacao_status ns ON n.status_id = ns.id 
                ORDER BY n.id DESC";
        $stmt = $pdo->query($sql);
        $notificacoes = $stmt->fetchAll();
        
        // Adicionar informação sobre disponibilidade para cobrança
        foreach ($notificacoes as &$n) {
            $n['pode_ir_cobranca'] = verificarPodeIrCobranca($n);
            $n['motivo_bloqueio_cobranca'] = getMotivoBloqueioCobranca($n);
        }
        
        // Se tem permissão genérica, mostra tudo
        if ($isAdminDev || temPermissao('notificacao.listar')) {
            $filtradas = $notificacoes;
        } else {
            // Filtrar baseado nas permissões específicas
            $filtradas = array_filter($notificacoes, function($n) use ($podeListarLavradas, $podeListarEnviadas, $podeListarCobranca) {
                $status = $n['status_slug'];
                
                // Rascunho só mostra se tiver permissão específica
                if ($status === 'rascunho') {
                    return false;
                }
                // Status lavrada precisa de permissão específica
                if ($status === 'lavrada') {
                    return $podeListarLavradas;
                }
                // Status enviada e ciente precisam de permissão específica
                if (in_array($status, ['enviada', 'ciente'])) {
                    return $podeListarEnviadas;
                }
                if ($status === 'cobranca') {
                    return $podeListarCobranca;
                }
                // Status encerrada é visível
                return true;
            });
        }
        
        http_response_code(200);
        echo json_encode(array_values($filtradas));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao listar notificações: ' . $e->getMessage()]);
    }
}

function verificarPodeIrCobranca($n) {
    if ($n['status_slug'] !== 'ciente') {
        return false;
    }
    
    if ($n['recurso_status'] === 'indeferido') {
        return true;
    }
    
    if ($n['recurso_status'] === 'pendente') {
        return false;
    }
    
    // Se tem ciência e passou 7+ dias
    if ($n['data_ciencia']) {
        $dataCiencia = new DateTime($n['data_ciencia']);
        $hoje = new DateTime();
        $diasCiencia = $hoje->diff($dataCiencia)->days;
        if ($diasCiencia >= 7) {
            return true;
        }
    }
    
    // Se foi enviada há 30+ dias sem ciência
    if ($n['data_envio'] && !$n['data_ciencia']) {
        $dataEnvio = new DateTime($n['data_envio']);
        $hoje = new DateTime();
        $diasEnvio = $hoje->diff($dataEnvio)->days;
        if ($diasEnvio >= 30) {
            return true;
        }
    }
    
    return false;
}

function getMotivoBloqueioCobranca($n) {
    if ($n['status_slug'] !== 'ciente') {
        return 'Notificação ainda não está no status "ciente"';
    }
    
    if ($n['recurso_status'] === 'indeferido') {
        return null;
    }
    
    if ($n['recurso_status'] === 'pendente') {
        return 'Recurso em análise - aguarde decisão';
    }
    
    if ($n['recurso_status'] === 'deferido') {
        return 'Recurso deferido - não pode ir para cobrança';
    }
    
    if ($n['data_ciencia']) {
        $dataCiencia = new DateTime($n['data_ciencia']);
        $hoje = new DateTime();
        $dias = $hoje->diff($dataCiencia)->days;
        if ($dias < 7) {
            return "Ciência registrada há $dias dia(s) - aguarde 7 dias para cobrar";
        }
    }
    
    if ($n['data_envio'] && !$n['data_ciencia']) {
        $dataEnvio = new DateTime($n['data_envio']);
        $hoje = new DateTime();
        $dias = $hoje->diff($dataEnvio)->days;
        if ($dias < 30) {
            return "Enviada há $dias dia(s) - aguarde 30 dias sem ciência para cobrar";
        }
    }
    
    return 'Aguarde 7 dias após ciência ou 30 dias após envio para cobrar';
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
        
        // Buscar status_id pelo slug ou manter o atual
        $statusId = null;
        if (!empty($dados->status_slug)) {
            $stmtStatus = $pdo->prepare("SELECT id FROM notificacao_status WHERE slug = ?");
            $stmtStatus->execute([$dados->status_slug]);
            $statusRow = $stmtStatus->fetch();
            if ($statusRow) {
                $statusId = $statusRow['id'];
            }
        }
        if (!$statusId) {
            $stmtStatusAtual = $pdo->prepare("SELECT status_id FROM notificacoes WHERE id = ?");
            $stmtStatusAtual->execute([$id]);
            $rowStatus = $stmtStatusAtual->fetch();
            $statusId = $rowStatus ? $rowStatus['status_id'] : null;
        }
        
        // Validar numeração duplicada
        $partes_numero = explode('/', $dados->numero ?? '');
        $numero = $partes_numero[0] ?? '';
        $ano = $partes_numero[1] ?? date('Y');
        
        $stmtDuplicado = $pdo->prepare("
            SELECT id FROM notificacoes 
            WHERE numero = ? AND ano = ? AND id != ?
        ");
        $stmtDuplicado->execute([$numero, $ano, $id]);
        if ($stmtDuplicado->fetch()) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['message' => "Já existe uma notificação com o número {$numero}/{$ano}."]);
            return;
        }
        
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

        if (!empty($dados->imagens_ocorrencia_desativar)) {
            $ids_desativar = array_map('intval', $dados->imagens_ocorrencia_desativar);
            if (!empty($ids_desativar)) {
                $placeholders = str_repeat('?,', count($ids_desativar) - 1) . '?';
                $stmt_desativar = $pdo->prepare("
                    UPDATE notificacao_imagens 
                    SET inactive = 1, deleted_at = NOW() 
                    WHERE id IN ($placeholders) AND notificacao_id = ? AND ocorrencia_id IS NOT NULL
                ");
                $params = array_merge($ids_desativar, [$id]);
                $stmt_desativar->execute($params);
            }
        }

        if (!empty($dados->imagens_ocorrencia_ativar)) {
            $ids_ativar = array_map('intval', $dados->imagens_ocorrencia_ativar);
            if (!empty($ids_ativar)) {
                $placeholders = str_repeat('?,', count($ids_ativar) - 1) . '?';
                $stmt_ativar = $pdo->prepare("
                    UPDATE notificacao_imagens 
                    SET inactive = 0, deleted_at = NULL 
                    WHERE id IN ($placeholders) AND notificacao_id = ? AND ocorrencia_id IS NOT NULL
                ");
                $params = array_merge($ids_ativar, [$id]);
                $stmt_ativar->execute($params);
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
            $statusId, 
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

        $pdo->prepare("DELETE FROM notificacao_artigos WHERE notificacao_id = ?")->execute([$id]);
        if (!empty($dados->artigos) && is_array($dados->artigos)) {
            $sql_artigo = "INSERT INTO notificacao_artigos (notificacao_id, artigo_notacao, artigo_texto, tipo) VALUES (?, ?, ?, 'regimento')";
            $stmt_artigo = $pdo->prepare($sql_artigo);
            foreach ($dados->artigos as $artigo) {
                if (isset($artigo->notation) || isset($artigo['notation'])) {
                    $stmt_artigo->execute([
                        $id,
                        $artigo->notation ?? $artigo['notation'],
                        $artigo->text ?? $artigo['text'] ?? null
                    ]);
                }
            }
        }

        if (!empty($dados->fotos_fatos)) {
            $sql_imagens = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ordem) VALUES (?, ?, ?, ?)";
            $stmt_imagens = $pdo->prepare($sql_imagens);
            foreach ($dados->fotos_fatos as $ordem => $foto) {
                if(!isset($foto->b64)) continue;
                $dados_imagem = base64_decode($foto->b64);
                // Redimensionar imagem (máx 600px, qualidade 80%)
                $dados_imagem = redimensionarImagem($foto->b64, 600, 80) ?: $dados_imagem;
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
        $numero = $partes_numero[0] ?? '';
        $ano = $partes_numero[1] ?? date('Y');
        
        // Validar numeração duplicada
        $stmtDuplicado = $pdo->prepare("SELECT id FROM notificacoes WHERE numero = ? AND ano = ?");
        $stmtDuplicado->execute([$numero, $ano]);
        if ($stmtDuplicado->fetch()) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['message' => "Já existe uma notificação com o número {$numero}/{$ano}."]);
            return;
        }
        
        // Buscar ID do status "rascunho" pelo slug
        $stmtStatus = $pdo->prepare("SELECT id FROM notificacao_status WHERE slug = 'rascunho'");
        $stmtStatus->execute();
        $statusRascunho = $stmtStatus->fetch();
        if (!$statusRascunho) {
            throw new Exception("Status 'rascunho' não encontrado. Execute o seed de tipos.");
        }
        $statusId = $statusRascunho['id'];
        
        // Buscar ID do tipo pelo nome se foi criado dinamicamente
        $tipoId = $dados->tipo_id ?? null;
        if (!$tipoId && !empty($dados->tipo_nome)) {
            $stmtTipo = $pdo->prepare("SELECT id FROM notificacao_tipos WHERE nome = ?");
            $stmtTipo->execute([$dados->tipo_nome]);
            $tipo = $stmtTipo->fetch();
            if ($tipo) {
                $tipoId = $tipo['id'];
            }
        }
        
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
            $tipoId, 
            $statusId, 
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
                $sql_copia_img = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ocorrencia_id, anexo_ocorrencia_id) VALUES (?, ?, ?, ?, ?)";
                $stmt_copia_img = $pdo->prepare($sql_copia_img);
                
                $sql_evidencia = "INSERT INTO evidencia_compartilhada (ocorrencia_anexo_id, notificacao_id) VALUES (?, ?)";
                $stmt_evidencia = $pdo->prepare($sql_evidencia);
                
                foreach ($anexos as $anexo) {
                    $caminho_relativo = ltrim($anexo['url'], '/');
                    
                    $stmt_copia_img->execute([
                        $notificacao_id,
                        $caminho_relativo,
                        $anexo['nome_original'],
                        $dados->ocorrencia_id,
                        $anexo['id']
                    ]);
                    $stmt_evidencia->execute([$anexo['id'], $notificacao_id]);
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

        if (!empty($dados->artigos) && is_array($dados->artigos)) {
            $sql_artigo = "INSERT INTO notificacao_artigos (notificacao_id, artigo_notacao, artigo_texto, tipo) VALUES (?, ?, ?, 'regimento')";
            $stmt_artigo = $pdo->prepare($sql_artigo);
            foreach ($dados->artigos as $artigo) {
                if (isset($artigo->notation) || isset($artigo['notation'])) {
                    $stmt_artigo->execute([
                        $notificacao_id,
                        $artigo->notation ?? $artigo['notation'],
                        $artigo->text ?? $artigo['text'] ?? null
                    ]);
                }
            }
        }
        
        if (!empty($dados->fotos_fatos)) {
            $sql_imagens = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ordem) VALUES (?, ?, ?, ?)";
            $stmt_imagens = $pdo->prepare($sql_imagens);
            foreach ($dados->fotos_fatos as $ordem => $foto) {
                $dados_imagem = base64_decode($foto->b64);
                // Redimensionar imagem (máx 600px, qualidade 80%)
                $dados_imagem = redimensionarImagem($foto->b64, 600, 80) ?: $dados_imagem;
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
        
        // Sincronizar IMAGENS
        $stmt_anexos = $pdo->prepare("SELECT * FROM ocorrencia_anexos WHERE ocorrencia_id = ? AND tipo = 'imagem' AND inactive = 0");
        $stmt_anexos->execute([$ocorrencia_id]);
        $anexos = $stmt_anexos->fetchAll();
        
        $stmt_existente = $pdo->prepare("SELECT anexo_ocorrencia_id, id FROM notificacao_imagens WHERE notificacao_id = ? AND anexo_ocorrencia_id IS NOT NULL");
        $stmt_existente->execute([$notificacao_id]);
        $ja_vinculados = [];
        $ids_inativos = [];
        foreach ($stmt_existente->fetchAll() as $row) {
            $ja_vinculados[] = $row['anexo_ocorrencia_id'];
            if ($row['anexo_ocorrencia_id']) {
                $ids_inativos[$row['anexo_ocorrencia_id']] = $row['id'];
            }
        }
        
        $sql_img = "INSERT INTO notificacao_imagens (notificacao_id, caminho_arquivo, nome_original, ocorrencia_id, anexo_ocorrencia_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_img = $pdo->prepare($sql_img);
        
        $sql_evidencia = "INSERT IGNORE INTO evidencia_compartilhada (ocorrencia_anexo_id, notificacao_id) VALUES (?, ?)";
        $stmt_evidencia = $pdo->prepare($sql_evidencia);
        
        $count_novas = 0;
        $count_reativadas = 0;
        
        foreach ($anexos as $anexo) {
            if (isset($ids_inativos[$anexo['id']])) {
                $stmt_reativar = $pdo->prepare("UPDATE notificacao_imagens SET inactive = 0, deleted_at = NULL WHERE id = ?");
                $stmt_reativar->execute([$ids_inativos[$anexo['id']]]);
                $count_reativadas++;
            } elseif (!in_array($anexo['id'], $ja_vinculados)) {
                $caminho_relativo = ltrim($anexo['url'], '/');
                
                $stmt_img->execute([
                    $notificacao_id,
                    $caminho_relativo,
                    $anexo['nome_original'],
                    $ocorrencia_id,
                    $anexo['id']
                ]);
                
                $stmt_evidencia->execute([$anexo['id'], $notificacao_id]);
                $count_novas++;
            }
        }
        
        $total_imagens = $count_novas + $count_reativadas;
        
        // Buscar evidências TEXTUAIS (mensagens marcadas como evidência)
        $stmt_evidencias_texto = $pdo->prepare("
            SELECT id, mensagem, usuario_id, created_at 
            FROM ocorrencia_mensagens 
            WHERE ocorrencia_id = ? AND eh_evidencia = 1
            ORDER BY created_at ASC
        ");
        $stmt_evidencias_texto->execute([$ocorrencia_id]);
        $evidencias_texto = $stmt_evidencias_texto->fetchAll();
        
        // Buscar fatos já existentes na notificação
        $stmt_fatos = $pdo->prepare("SELECT id, descricao FROM notificacao_fatos WHERE notificacao_id = ?");
        $stmt_fatos->execute([$notificacao_id]);
        $fatos_existentes = $stmt_fatos->fetchAll();
        $fatos_textos = array_map(function($f) { return trim($f['descricao']); }, $fatos_existentes);
        
        // Inserir fatos novos (evidências de texto que ainda não existem)
        $stmt_insert_fato = $pdo->prepare("INSERT INTO notificacao_fatos (notificacao_id, descricao) VALUES (?, ?)");
        $novas_evidencias_texto = [];
        foreach ($evidencias_texto as $ev) {
            $texto = trim($ev['mensagem']);
            if (!empty($texto) && !in_array($texto, $fatos_textos)) {
                $stmt_insert_fato->execute([$notificacao_id, $texto]);
                $novas_evidencias_texto[] = $texto;
                $fatos_textos[] = $texto; // Evita duplicatas no loop
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'message' => "Sincronizado: {$total_imagens} imagem(ns), " . count($novas_evidencias_texto) . " evidência(s) de texto.",
            'images_count' => $total_imagens,
            'images_novas' => $count_novas,
            'images_reativadas' => $count_reativadas,
            'text_evidencias' => $novas_evidencias_texto,
            'text_count' => count($novas_evidencias_texto)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao sincronizar evidências: ' . $e->getMessage()]);
    }
}

function registrarHistoricoNotificacao($pdo, $notificacaoId, $usuarioId, $faseAnterior, $faseNova, $observacao = '') {
    $stmt = $pdo->prepare("INSERT INTO notificacao_fase_log (notificacao_id, usuario_id, fase_anterior, fase_nova, observacao) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$notificacaoId, $usuarioId, $faseAnterior, $faseNova, $observacao]);
}

function mudarFase($pdo, $dados, $usuario) {
    if (empty($dados->id) || empty($dados->nova_fase)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID e nova fase são obrigatórios.']);
        exit();
    }
    
    $id = (int)$dados->id;
    $novaFaseSlug = $dados->nova_fase;
    $observacao = $dados->observacao ?? '';
    $isAdminDev = in_array($usuario['role'], ['admin', 'dev']);
    
    try {
        $stmt = $pdo->prepare("
            SELECT n.status_id, ns.slug as status_slug 
            FROM notificacoes n 
            JOIN notificacao_status ns ON n.status_id = ns.id 
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $notificacao = $stmt->fetch();
        
        if (!$notificacao) {
            http_response_code(404);
            echo json_encode(['message' => 'Notificação não encontrada.']);
            exit();
        }
        
        $faseAnteriorSlug = $notificacao['status_slug'];
        
        // --- LÓGICA ESPECIAL: REABRIR ---
        if ($novaFaseSlug === 'reabrir') {
            if (!$isAdminDev && !temPermissao('notificacao.reabrir')) {
                http_response_code(403);
                echo json_encode(['message' => "Você não tem permissão para reabrir notificações."]);
                exit();
            }
            
            // Buscar a última fase válida antes da atual
            $stmt_last = $pdo->prepare("
                SELECT fase_anterior FROM notificacao_fase_log 
                WHERE notificacao_id = ? AND fase_nova = ? 
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt_last->execute([$id, $faseAnteriorSlug]);
            $last = $stmt_last->fetch();
            
            if (!$last || !$last['fase_anterior']) {
                http_response_code(400);
                echo json_encode(['message' => "Não foi possível identificar a fase anterior para reabertura."]);
                exit();
            }
            $novaFaseSlug = $last['fase_anterior'];
        }

        // Buscar o ID do novo status
        $stmt_status = $pdo->prepare("SELECT id FROM notificacao_status WHERE slug = ?");
        $stmt_status->execute([$novaFaseSlug]);
        $novoStatus = $stmt_status->fetch();
        
        if (!$novoStatus) {
            http_response_code(400);
            echo json_encode(['message' => "Status '{$novaFaseSlug}' não encontrado no sistema."]);
            exit();
        }
        
        $novoStatusId = $novoStatus['id'];
        
        // Verificar permissões específicas por transição
        if (!$isAdminDev) {
            $permissaoNecessaria = '';
            
            if ($novaFaseSlug === 'lavrada') $permissaoNecessaria = 'notificacao.lavrar';
            elseif ($novaFaseSlug === 'rascunho' && $faseAnteriorSlug === 'lavrada') $permissaoNecessaria = 'notificacao.retornar_rascunho';
            elseif ($novaFaseSlug === 'enviada') $permissaoNecessaria = 'notificacao.marcar_enviada';
            elseif ($novaFaseSlug === 'ciente') $permissaoNecessaria = 'notificacao.registrar_ciencia';
            elseif ($novaFaseSlug === 'em_recurso') $permissaoNecessaria = 'notificacao.registrar_recurso';
            elseif (in_array($novaFaseSlug, ['recurso_deferido', 'recurso_indeferido'])) $permissaoNecessaria = 'notificacao.julgar_recurso';
            elseif ($novaFaseSlug === 'cobranca') $permissaoNecessaria = 'notificacao.marcar_cobranca';
            elseif ($novaFaseSlug === 'encerrada') $permissaoNecessaria = 'notificacao.encerrar';
            
            if ($permissaoNecessaria && !temPermissao($permissaoNecessaria)) {
                http_response_code(403);
                echo json_encode(['message' => "Você não tem permissão para esta ação ({$permissaoNecessaria})."]);
                exit();
            }
        }

        // --- LÓGICA ESPECIAL: CONDIÇÕES PARA COBRANÇA ---
        if ($novaFaseSlug === 'cobranca') {
            $stmt_check = $pdo->prepare("SELECT data_ciencia, recurso_status, status_id FROM notificacoes WHERE id = ?");
            $stmt_check->execute([$id]);
            $n = $stmt_check->fetch();
            
            $podeIrCobranca = false;
            $motivoBloqueio = '';
            
            if ($n['recurso_status'] === 'indeferido') {
                $podeIrCobranca = true;
            }
            elseif ($n['recurso_status'] === 'pendente') {
                $podeIrCobranca = false;
                $motivoBloqueio = 'Recurso em análise - aguarde decisão';
            }
            elseif ($n['data_ciencia']) {
                $dataCiencia = new DateTime($n['data_ciencia']);
                $hoje = new DateTime();
                $diff = $hoje->diff($dataCiencia)->days;
                if ($diff >= 7) {
                    $podeIrCobranca = true;
                } else {
                    $motivoBloqueio = "Ciência registrada há $diff dia(s) - aguarde 7 dias para cobrar";
                }
            }
            
            if (!$podeIrCobranca && !$isAdminDev) {
                http_response_code(400);
                echo json_encode(['message' => $motivoBloqueio ?: "Regra de negócio não atendida para cobrança."]);
                exit();
            }
            
            if (!$podeIrCobranca && $isAdminDev && empty($dados->forcar)) {
                http_response_code(400);
                echo json_encode(['message' => $motivoBloqueio, 'forcar_permitido' => true]);
                exit();
            }
        }
        
        $pdo->beginTransaction();
        
        // Atualizar campos específicos dependendo da fase
        $sqlExtra = "";
        $paramsExtra = [];
        
        if ($novaFaseSlug === 'lavrada') {
            $sqlExtra = ", data_lavratura = NOW(), lavrada_por = ?";
            $paramsExtra[] = $usuario['id'];
        } elseif ($novaFaseSlug === 'enviada') {
            $sqlExtra = ", data_envio = NOW()";
        } elseif ($novaFaseSlug === 'ciente') {
            $sqlExtra = ", data_ciencia = NOW(), ciencia_por = ?";
            $paramsExtra[] = $dados->metodo_ciencia ?? 'Sistema';
        } elseif ($novaFaseSlug === 'em_recurso') {
            $sqlExtra = ", tem_recurso = 1, data_recurso = NOW(), recurso_texto = ?";
            $paramsExtra[] = $dados->recurso_texto ?? '';
        } elseif ($novaFaseSlug === 'recurso_deferido' || $novaFaseSlug === 'recurso_indeferido') {
            $sqlExtra = ", recurso_status = ?";
            $paramsExtra[] = ($novaFaseSlug === 'recurso_deferido' ? 'deferido' : 'indeferido');
        } elseif ($novaFaseSlug === 'cobranca') {
            $sqlExtra = ", encerrada = 0, data_encerramento = NULL"; // Reset caso reaberto
        } elseif ($novaFaseSlug === 'encerrada') {
            $sqlExtra = ", encerrada = 1, data_encerramento = NOW(), motivo_encerramento = ?";
            $paramsExtra[] = $dados->motivo_encerramento ?? 'Lançamento efetuado';
        }
        
        $sql = "UPDATE notificacoes SET status_id = ? $sqlExtra WHERE id = ?";
        $params = array_merge([$novoStatusId], $paramsExtra, [$id]);
        
        $stmt_upd = $pdo->prepare($sql);
        $stmt_upd->execute($params);
        
        registrarHistoricoNotificacao($pdo, $id, $usuario['id'], $faseAnteriorSlug, $novaFaseSlug, $observacao);
        
        $pdo->commit();
        
        http_response_code(200);
        echo json_encode(['message' => 'Fase atualizada com sucesso!', 'nova_fase' => $novaFaseSlug]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao mudar fase: ' . $e->getMessage()]);
    }
}

function quickEditNotificacao($pdo, $dados) {
    try {
        $id = (int)$dados->id;
        
        $updates = [];
        $params = [];
        
        if (isset($dados->data_envio)) {
            if ($dados->data_envio) {
                $updates[] = "data_envio = ?";
                $params[] = $dados->data_envio;
            } else {
                $updates[] = "data_envio = NULL";
            }
        }
        
        if (isset($dados->data_ciencia)) {
            if ($dados->data_ciencia) {
                $updates[] = "data_ciencia = ?";
                $params[] = $dados->data_ciencia;
            } else {
                $updates[] = "data_ciencia = NULL";
            }
        }
        
        if (isset($dados->recurso_status)) {
            if ($dados->recurso_status) {
                $updates[] = "recurso_status = ?";
                $params[] = $dados->recurso_status;
            } else {
                $updates[] = "recurso_status = NULL";
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['message' => 'Nenhum campo para atualizar.']);
            return;
        }
        
        $params[] = $id;
        $sql = "UPDATE notificacoes SET " . implode(", ", $updates) . ", data_atualizacao = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode(['message' => 'Atualizado com sucesso!']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao atualizar: ' . $e->getMessage()]);
    }
}

function criarTipoNotificacao($pdo, $dados) {
    try {
        $nome = trim($dados->nome ?? '');
        
        if (empty($nome)) {
            http_response_code(400);
            echo json_encode(['message' => 'Nome do tipo é obrigatório.']);
            return;
        }
        
        // Verificar se já existe
        $stmt = $pdo->prepare("SELECT id FROM notificacao_tipos WHERE nome = ?");
        $stmt->execute([$nome]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'Já existe um tipo com este nome.']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO notificacao_tipos (nome, descricao) VALUES (?, ?)");
        $stmt->execute([$nome, 'Tipo criado pelo usuário.']);
        $id = $pdo->lastInsertId();
        
        http_response_code(201);
        echo json_encode(['message' => 'Tipo criado!', 'id' => $id]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Erro ao criar tipo: ' . $e->getMessage()]);
    }
}
?>
