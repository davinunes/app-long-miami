<?php
/**
 * Helper para APIs - Inicializa sessão e retorna usuário
 * Suporta tanto papéis (legacy) quanto permissões
 */

require_once __DIR__ . '/../auth.php';

/**
 * Retorna o usuário logado ou rejeita a requisição
 */
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function getApiUsuario() {
    if (!estaLogado()) {
        http_response_code(401);
        echo json_encode(['message' => 'Não autenticado.']);
        exit;
    }
    return getUsuario();
}

/**
 * Alias para getApiUsuario
 */
function requireApiLogin() {
    return getApiUsuario();
}

/**
 * Verifica se o usuário tem um dos papéis (legacy)
 */
function requireApiPapel($papeis) {
    $usuario = getApiUsuario();
    $papeisPermitidos = is_array($papeis) ? $papeis : [$papeis];
    $papeisUsuario = getPapeisUsuario();
    
    foreach ($papeisPermitidos as $papel) {
        if (in_array($papel, $papeisUsuario)) {
            return true;
        }
    }
    
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente. Papel necessário: ' . implode(', ', $papeisPermitidos)]);
    exit;
}

/**
 * Verifica se o usuário tem uma permissão específica
 * 
 * @param string $permissao Slug da permissão
 * @param array $context Contexto adicional
 * @return bool
 */
function requireApiPermissao($permissao, $context = []) {
    $usuario = getApiUsuario();
    
    // DEV e ADMIN sempre têm todas as permissões
    $role = $usuario['role'] ?? '';
    if ($role === 'dev' || $role === 'admin') {
        return true;
    }
    
    // Verifica se tem nos papéis (sessão)
    if (in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? [])) {
        return true;
    }
    
    if (temPermissao($permissao, $context)) {
        return true;
    }
    
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente. Necessário: ' . $permissao]);
    exit;
}

/**
 * Verifica se o usuário tem pelo menos uma das permissões
 * 
 * @param array $permissoes Array de slugs
 * @return bool
 */
function requireApiAlgumaPermissao($permissoes) {
    $usuario = getApiUsuario();
    
    // DEV e ADMIN sempre têm todas as permissões
    $role = $usuario['role'] ?? '';
    if ($role === 'dev' || $role === 'admin') {
        return true;
    }
    
    if (in_array('dev', $usuario['papeis'] ?? []) || in_array('admin', $usuario['papeis'] ?? [])) {
        return true;
    }
    
    if (temAlgumaPermissao($permissoes)) {
        return true;
    }
    
    http_response_code(403);
    echo json_encode(['message' => 'Permissão insuficiente. Necessário: ' . implode(' ou ', $permissoes)]);
    exit;
}

/**
 * Verifica se o usuário pode editar um recurso próprio ou qualquer um
 * 
 * @param string $tabela Tabela do recurso
 * @param int $registroId ID do registro
 * @param string $permissaoPropria Permissão para próprio
 * @param string $permissaoGeral Permissão geral
 * @return bool
 */
function podeEditarRecurso($tabela, $registroId, $permissaoPropria, $permissaoGeral) {
    return podeEditar($tabela, $registroId, $permissaoPropria, $permissaoGeral);
}

/**
 * Redimensiona uma imagem para caber dentro de um máximo de pixels no maior lado
 * Mantém proporção e reduz a qualidade
 * 
 * @param string $imageData Dados da imagem em Base64 ou binário
 * @param int $maxPixels Tamanho máximo do maior lado (padrão: 600)
 * @param int $quality Qualidade do JPEG (0-100, padrão: 80)
 * @return string|null Dados da imagem redimensionada ou null se falhar
 */
function redimensionarImagem($imageData, $maxPixels = 600, $quality = 80) {
    // Detectar se é base64
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
        $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
    }
    
    $source = imagecreatefromstring($imageData);
    if (!$source) {
        return null;
    }
    
    $width = imagesx($source);
    $height = imagesy($source);
    
    // Se já é menor que o máximo, retorna original
    if ($width <= $maxPixels && $height <= $maxPixels) {
        imagedestroy($source);
        return $imageData;
    }
    
    // Calcular novas dimensões mantendo proporção
    if ($width > $height) {
        $newWidth = $maxPixels;
        $newHeight = round(($height / $width) * $maxPixels);
    } else {
        $newHeight = $maxPixels;
        $newWidth = round(($width / $height) * $maxPixels);
    }
    
    // Criar nova imagem
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparência para PNG
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    
    // Redimensionar com reamostragem bicúbica
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Obter extensão original da imagem
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);
    
    // Gerar output
    ob_start();
    if ($mimeType === 'image/png') {
        imagepng($resized, null, round($quality / 10)); // PNG usa 0-9, então convertemos
    } else {
        imagejpeg($resized, null, $quality);
    }
    $output = ob_get_clean();
    
    imagedestroy($source);
    imagedestroy($resized);
    
    return $output ?: null;
}
