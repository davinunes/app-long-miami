<?php
header('Content-Type: application/json; charset=utf-8');

// --- Funções Auxiliares (sem alterações) ---

function encontrarNoCaminho($dados, $notacao) {
    if (!$dados || !isset($dados['artigos']) || !$notacao) return null;
    $partes = explode('.', strtolower($notacao));
    $artigoNumero = $partes[0];
    if (!isset($dados['artigos'][$artigoNumero])) return null;
    $resultado = $dados['artigos'][$artigoNumero];
    for ($i = 1; $i < count($partes); $i++) {
        $parteDoCaminho = $partes[$i];
        $proximoNivelEncontrado = false;
        if (preg_match('/^([pia])(.+)$/', $parteDoCaminho, $matches)) {
            $tipo = $matches[1]; $chave = $matches[2];
            $mapaTipos = ['p' => 'paragrafos', 'i' => 'incisos', 'a' => 'alineas'];
            $subnivelAlvo = $mapaTipos[$tipo];
            if (isset($resultado[$subnivelAlvo]) && isset($resultado[$subnivelAlvo][$chave])) {
                $resultado = $resultado[$subnivelAlvo][$chave];
                $proximoNivelEncontrado = true;
            }
        } else {
            $chave = $parteDoCaminho;
            $ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
            foreach ($ordemDeBusca as $subnivel) {
                if (isset($resultado[$subnivel]) && isset($resultado[$subnivel][$chave])) {
                    $resultado = $resultado[$subnivel][$chave];
                    $proximoNivelEncontrado = true;
                    break;
                }
            }
        }
        if (!$proximoNivelEncontrado) return null;
    }
    return $resultado;
}

function parsearSelecaoComplexa($seletor) {
    $chavesFinais = [];
    $partes = explode(',', $seletor);
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if (strpos($parte, '-') !== false) {
            list($inicio, $fim) = explode('-', $parte);
            $range = (is_numeric($inicio) && is_numeric($fim)) ? range((int)$inicio, (int)$fim) : range($inicio, $fim);
            foreach ($range as $item) { $chavesFinais[] = (string)$item; }
        } else { $chavesFinais[] = $parte; }
    }
    return $chavesFinais;
}

function formatarItemComoTexto($item) {
    $textoItem = "Referência: {$item['notacao_pesquisada']}\n";
    $conteudo = $item['conteudo'];
    if (isset($conteudo['texto_artigo_pai'])) {
        $textoItem .= ($conteudo['titulo_artigo_pai'] ? mb_strtoupper($conteudo['titulo_artigo_pai'], 'UTF-8') . "\n" : "") . $conteudo['texto_artigo_pai'] . "\n";
        $conteudoParaRecursao = $conteudo['item_especifico'];
    } else {
        $conteudoParaRecursao = $conteudo;
    }
    $gerarTextoRecursivo = function($objeto, $nivel = 0) use (&$gerarTextoRecursivo) {
        $bloco = "";
        $indentacao = str_repeat("  ", $nivel);
        if (isset($objeto['titulo_artigo']) && $nivel == 0) { $bloco .= $indentacao . mb_strtoupper($objeto['titulo_artigo'], 'UTF-8') . "\n"; }
        if (isset($objeto['texto'])) { $bloco .= $indentacao . $objeto['texto'] . "\n"; }
        $subniveis = ['paragrafos', 'incisos', 'alineas'];
        foreach($subniveis as $subnivel) {
            if (isset($objeto[$subnivel])) {
                foreach ($objeto[$subnivel] as $num => $subObj) {
                    $label = "";
                    if ($subnivel === 'paragrafos') $label = ($num === 'unico') ? 'Parágrafo único:' : "§ {$num}°:";
                    if ($subnivel === 'incisos') $label = "Inciso {$num}:";
                    if ($subnivel === 'alineas') $label = "Alínea {$num}):";
                    $bloco .= "\n" . $indentacao . $label . "\n";
                    $bloco .= $gerarTextoRecursivo($subObj, $nivel + 1);
                }
            }
        }
        return $bloco;
    };
    $textoItem .= $gerarTextoRecursivo($conteudoParaRecursao);
    return $textoItem;
}

// --- LÓGICA PRINCIPAL DO ENDPOINT (TOTALMENTE REESTRUTURADA) ---
try {
    $jsonString = file_get_contents('database.json');
    $database = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) { throw new Exception('Erro ao ler ou decodificar o arquivo database.json.'); }
    if (!isset($_GET['notacao'])) {
        http_response_code(400);
        echo json_encode(['erro' => "Parâmetro 'notacao' é obrigatório."]);
        exit;
    }

    $notacaoCompleta = $_GET['notacao'];
    $formato = isset($_GET['formato']) ? $_GET['formato'] : 'estruturado';
    $respostaFinal = [];

    // Roteamento da busca
    $tarefasDeBusca = explode(',', $notacaoCompleta);
    if (count($tarefasDeBusca) > 1 && strpos($notacaoCompleta, '[') === false) {
        // Busca em lote (ex: 14.5,47,49)
        foreach ($tarefasDeBusca as $tarefa) {
            $tarefa = trim($tarefa);
            if(empty($tarefa)) continue;
            $itemEncontrado = encontrarNoCaminho($database, $tarefa);
            if ($itemEncontrado) {
                $artigoNumero = explode('.', $tarefa)[0];
                $capituloInfo = ['numero' => $database['artigos'][$artigoNumero]['capitulo'], 'titulo' => $database['capitulos'][$database['artigos'][$artigoNumero]['capitulo']]];
                $respostaFinal[] = ['notacao_pesquisada' => $tarefa, 'capitulo' => $capituloInfo, 'conteudo' => $itemEncontrado];
            }
        }
    } else {
        // Busca simples ou com colchetes (trata como uma única tarefa)
        $tarefa = $notacaoCompleta;
        if (preg_match('/(.*)\.\s*\[(.*)\]$/', $tarefa, $matches)) {
            $caminhoPai = $matches[1];
            $seletor = $matches[2];
            $objetoPai = encontrarNoCaminho($database, $caminhoPai);
            if ($objetoPai) {
                $chavesParaBuscar = parsearSelecaoComplexa($seletor);
                foreach ($chavesParaBuscar as $chave) {
                    $itemEncontrado = null;
                    $ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
                    foreach ($ordemDeBusca as $subnivel) {
                        if (isset($objetoPai[$subnivel]) && isset($objetoPai[$subnivel][$chave])) { $itemEncontrado = $objetoPai[$subnivel][$chave]; break; }
                    }
                    if ($itemEncontrado) {
                        $artigoNumero = explode('.', $caminhoPai)[0];
                        $capituloInfo = ['numero' => $database['artigos'][$artigoNumero]['capitulo'], 'titulo' => $database['capitulos'][$database['artigos'][$artigoNumero]['capitulo']]];
                        $respostaFinal[] = ['notacao_pesquisada' => "{$caminhoPai}.{$chave}", 'capitulo' => $capituloInfo, 'conteudo' => $itemEncontrado];
                    }
                }
            }
        } else {
            $itemEspecifico = encontrarNoCaminho($database, $tarefa);
            if ($itemEspecifico) {
                $partes = explode('.', $tarefa);
                $artigoNumero = $partes[0];
                $capituloInfo = ['numero' => $database['artigos'][$artigoNumero]['capitulo'], 'titulo' => $database['capitulos'][$database['artigos'][$artigoNumero]['capitulo']]];
                $conteudoFinal = $itemEspecifico;
                if (count($partes) > 1) {
                    $artigoPai = encontrarNoCaminho($database, $artigoNumero);
                    $conteudoFinal = ['texto_artigo_pai' => $artigoPai['texto'] ?? null, 'titulo_artigo_pai' => $artigoPai['titulo_artigo'] ?? null, 'item_especifico' => $itemEspecifico];
                }
                $respostaFinal[] = ['notacao_pesquisada' => $tarefa, 'capitulo' => $capituloInfo, 'conteudo' => $conteudoFinal];
            }
        }
    }
    
    if (empty($respostaFinal)) {
        http_response_code(404);
        echo json_encode(['erro' => "Nenhum item válido encontrado para a notação \"{$notacaoCompleta}\"."]);
        exit;
    }

    // LÓGICA DE FORMATAÇÃO (AGORA COMPLETA)
    if ($formato === 'texto') {
        $textoConcatenado = "";
        $agrupadoPorCapitulo = [];
        foreach ($respostaFinal as $res) { $agrupadoPorCapitulo[$res['capitulo']['numero']][] = $res; }
        ksort($agrupadoPorCapitulo);
        foreach ($agrupadoPorCapitulo as $numCap => $itens) {
            $textoConcatenado .= "Capítulo {$numCap}: {$itens[0]['capitulo']['titulo']}\n";
            foreach ($itens as $item) { $textoConcatenado .= formatarItemComoTexto($item) . "\n"; }
            $textoConcatenado .= "\n";
        }
        $respostaFinal = ['texto_formatado' => trim($textoConcatenado)];
    } else {
        if (count($respostaFinal) === 1 && strpos($notacaoCompleta, ',') === false && strpos($notacaoCompleta, '[') === false) {
            $respostaFinal = $respostaFinal[0];
        }
    }

    echo json_encode($respostaFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Ocorreu um erro interno no servidor.', 'detalhes' => $e->getMessage()]);
}
?>