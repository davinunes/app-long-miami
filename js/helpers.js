/**
 * helpers.js - Funções auxiliares para melhorar a UX do formulário.
 */

/**
 * Configura o campo 'bloco' para aceitar apenas uma letra maiúscula.
 */
function configurarCampoBloco() {
    const blocoInput = document.getElementById('bloco');
    if (!blocoInput) return;

    blocoInput.addEventListener('input', function(e) {
        // Remove qualquer caracter que não seja letra e converte para maiúsculo
        let valor = e.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase();
        // Limita a apenas um caracter
        e.target.value = valor.substring(0, 1);
    });
}

/**
 * Vincula os campos 'unidade' e 'bloco' para preenchimento inteligente.
 * Extrai a primeira letra digitada para o campo 'Bloco' e mantém
 * apenas os números no campo 'Unidade', independentemente da ordem.
 */
function vincularCamposUnidadeBloco() { 
    const unidadeInput = document.getElementById('unidade');
    const blocoInput = document.getElementById('bloco');

    if (!unidadeInput || !blocoInput) {
        console.error("ERRO: Elemento 'unidade' ou 'bloco' não encontrado!");
        return;
    }

    unidadeInput.addEventListener('input', function(e) {
        const valorAtual = e.target.value;

        // 1. Encontra a primeira letra (se houver) no que foi digitado.
        const matchLetra = valorAtual.match(/[a-zA-Z]/);
        const letra = matchLetra ? matchLetra[0].toUpperCase() : '';

        // 2. Encontra TODOS os números no que foi digitado.
        const matchNumeros = valorAtual.match(/\d/g);
        const numeros = matchNumeros ? matchNumeros.join('') : '';

        // 3. Atualiza o campo 'bloco' com a letra encontrada.
        //    Se o usuário apagar a letra, o campo bloco também é limpo.
        blocoInput.value = letra;

        // 4. Atualiza o campo 'unidade' para conter APENAS os números.
        //    Isso efetivamente "apaga" a letra do campo 'unidade'.
        e.target.value = numeros;
    });
}