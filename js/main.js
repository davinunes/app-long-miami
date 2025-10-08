// js/main.js - Lógica específica da página de CRIAÇÃO

document.addEventListener('DOMContentLoaded', async function() {
    document.getElementById('data_emissao').value = new Date().toISOString().split('T')[0];
    addFato();
    
    // Chama funções de UI que agora estão em funcs.js
    configurarCampoBloco();
    vincularCamposUnidadeBloco();
    
    // Configura o botão principal para a ação de SALVAR
    document.getElementById('btnSalvar').onclick = salvarNotificacao;
    
    // Busca os dados iniciais e o próximo número
    await Promise.all([
        fetchInitialData(),
        fetchProximoNumero()
    ]);
});

async function fetchProximoNumero() {
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php?proximo_numero=true`);
        if (!response.ok) throw new Error('Falha ao buscar o próximo número.');
        const data = await response.json();
        const numeroInput = document.getElementById('numero');
        if (numeroInput && data.proximo_numero) {
            numeroInput.value = data.proximo_numero;
        }
    } catch (error) {
        showStatus(error.message, 'error');
        console.error("Fetch next number error:", error);
    }
}

async function salvarNotificacao() {
    const dados = getFormData(false);
    if (!dados.numero || !dados.unidade || !dados.assunto_id) {
        showStatus('Preencha os campos obrigatórios.', 'error');
        return;
    }
    showStatus('Salvando notificação...', 'loading');
    try {
        const response = await fetch(`${API_BASE_URL_PHP}/notificacoes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        if (response.ok) {
            showStatus(`Notificação salva! ID: ${result.id}`, 'success');
            // Opcional: redirecionar para a página de edição ou lista
            // setTimeout(() => window.location.href = `editar.php?id=${result.id}`, 1500);
        } else {
            showStatus(`Erro ao salvar: ${result.message}`, 'error');
        }
    } catch (error) {
        showStatus(`Erro de conexão: ${error.message}`, 'error');
    }
}