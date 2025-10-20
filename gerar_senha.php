<?php
// Escolha uma senha forte para o seu usuário administrador
$senha_em_texto_puro = 'dinovatech!'; 

// Gera o hash seguro
$hash_da_senha = password_hash($senha_em_texto_puro, PASSWORD_DEFAULT);

// Exibe o hash para você copiar
echo $hash_da_senha;
?>