<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Notificações</title>
    
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-container {
            padding: 30px;
            overflow-x: auto; /* Garante que a tabela seja rolável em telas pequenas */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
            white-space: nowrap; /* Impede que o texto quebre em várias linhas */
        }
        th {
            background-color: #f4f4f4;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            background-color: #3498db;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .action-btn:hover {
            background-color: #2980b9;
        }
        .header-actions {
            display: flex;
            justify-content: flex-end;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .btn-new {
            padding: 10px 20px;
            background-color: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header">
            <h1>Lista de Notificações</h1>
            <p>Visualize e gerencie as notificações registradas.</p>
        </div>
        <div class="table-container">
            <div class="header-actions">
                <a href="index.php" class="btn-new">+ Criar Nova Notificação</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Unidade</th>
                        <th>Assunto</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Data de Emissão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="notifications-table-body">
                    <tr><td colspan="7" style="text-align: center;">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('notifications-table-body');
            const apiEndpoint = '/api/notificacoes.php';

            fetch(apiEndpoint)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro na rede: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    tbody.innerHTML = ''; // Limpa a mensagem "Carregando..."
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Nenhuma notificação encontrada.</td></tr>';
                        return;
                    }

                    data.forEach(n => {
                        // Formata a data para o padrão brasileiro, tratando o fuso horário
                        const dataEmissao = new Date(n.data_emissao + 'T00:00:00');
                        const dataFormatada = dataEmissao.toLocaleDateString('pt-BR');

                        const row = `
                            <tr>
                                <td>${n.numero}/${n.ano}</td>
                                <td>${n.bloco ? n.bloco : ''}${n.unidade}</td>
                                <td>${n.assunto}</td>
                                <td>${n.tipo}</td>
                                <td>${n.status}</td>
                                <td>${dataFormatada}</td>
                                <td>
                                    <a href="editar.php?id=${n.id}" class="action-btn">Detalhes / Editar</a>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Erro ao buscar notificações:', error);
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Erro ao carregar dados. Verifique a conexão com a API.</td></tr>';
                });
        });
    </script>
</body>
</html>