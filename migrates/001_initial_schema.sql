-- Senha: umaSenhaMuitoForte123! (já com hash bcrypt)
INSERT INTO usuarios (nome, email, senha, role) VALUES 
    ('Administrador', 'admin@seusistema.com', '$2y$10$gIlV6Aw0DXP0cN67L5kHAOz6NxzekmybdC8VkjPTBGVAuReonpI0C', 'admin')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
