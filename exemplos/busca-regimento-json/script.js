document.addEventListener('DOMContentLoaded', () => {

    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.getElementById('resultsContainer');
    const notationOutput = document.getElementById('notationOutput');

    let database = null;
    const selectedNotations = new Set();

    // --- CARREGAMENTO DO BANCO DE DADOS (sem alteração) ---
    async function loadDatabase() {
        try {
            const response = await fetch('database.json');
            if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
            database = await response.json();
            console.log("Banco de dados carregado.");
        } catch (error) {
            searchInput.placeholder = "Erro ao carregar o banco de dados.";
            console.error(error);
        }
    }

    // --- FUNÇÕES DE BUSCA (sem alteração) ---
    function pesquisarPorTexto(termo) {
        const resultados = [];
        const termoBusca = termo.toLowerCase();
        if (termoBusca.length < 3) return [];
        function explorar(objeto, caminho) {
            if (objeto.texto && objeto.texto.toLowerCase().includes(termoBusca)) {
                const artigoNum = caminho.split('.')[0];
                const capituloInfo = { numero: database.artigos[artigoNum].capitulo, titulo: database.capitulos[database.artigos[artigoNum].capitulo] };
                resultados.push({ notacao: caminho, texto: objeto.texto, capitulo: capituloInfo });
            }
            for (const chave of ['paragrafos', 'incisos', 'alineas']) {
                if (objeto[chave]) {
                    for (const subChave in objeto[chave]) {
                        explorar(objeto[chave][subChave], `${caminho}.${subChave}`);
                    }
                }
            }
        }
        for (const artigoNum in database.artigos) {
            explorar(database.artigos[artigoNum], artigoNum);
        }
        return resultados;
    }
	
	/**
     * NOVA FUNÇÃO: Pega um número de artigo e retorna uma lista de seus componentes (o artigo em si e seus parágrafos/incisos).
     */
    function buscarEstruturaDoArtigo(articleNum) {
        const articleObj = encontrarNoCaminho(articleNum);
        if (!articleObj) return [];

        const items = [];
        const capituloInfo = { numero: articleObj.capitulo, titulo: database.capitulos[articleObj.capitulo] };

        // Adiciona o próprio artigo como um item selecionável
        items.push({
            notacao: articleNum,
            texto: articleObj.titulo_artigo || articleObj.texto || `Artigo ${articleNum} (texto principal)`,
            capitulo: capituloInfo
        });

        // Função auxiliar para processar os filhos de um objeto (parágrafos, incisos, etc.)
        const processChildren = (children, typePrefix) => {
            if (!children) return;
            for (const key in children) {
                const child = children[key];
                // Gera a notação explícita (ex: 8.p1) para evitar ambiguidades na seleção
                const notation = `${articleNum}.${typePrefix}${key}`;
                items.push({
                    notacao: notation,
                    texto: child.texto || "Conteúdo aninhado",
                    capitulo: capituloInfo
                });
            }
        };

        processChildren(articleObj.paragrafos, 'p');
        processChildren(articleObj.incisos, 'i');

        return items;
    }
    
    function getItemsFromNotations(notationsSet) {
        const items = [];
        notationsSet.forEach(notation => {
            const item = encontrarNoCaminho(notation);
            if (item) {
                const artigoNum = notation.split('.')[0];
                const capituloInfo = { numero: database.artigos[artigoNum].capitulo, titulo: database.capitulos[database.artigos[artigoNum].capitulo] };
                items.push({ notacao: notation, texto: item.texto || "Conteúdo aninhado", capitulo: capituloInfo });
            }
        });
        return items;
    }
    
    function encontrarNoCaminho(notacao) {
        if (!database || !database.artigos || !notacao) return null;
        const partes = notacao.split('.');
        let resultado = database.artigos[partes[0]];
        if (!resultado) return null;
        for (let i = 1; i < partes.length; i++) {
            let proximoNivelEncontrado = false;
            for (const subnivel of ['incisos', 'paragrafos', 'alineas']) {
                if (resultado[subnivel] && resultado[subnivel][partes[i]]) {
                    resultado = resultado[subnivel][partes[i]];
                    proximoNivelEncontrado = true;
                    break;
                }
            }
            if (!proximoNivelEncontrado) return null;
        }
        return resultado;
    }

    // --- NOVAS FUNÇÕES PARA CONSULTA DE TRECHOS ---

/**
 * Função para buscar o texto completo das notações selecionadas
 */
async function fetchNotationText(notationsString) {
    if (!notationsString) {
        document.getElementById('notationResult').value = "";
        return;
    }

    try {
        const response = await fetch(`trecho.php?notacao=${encodeURIComponent(notationsString)}&formato=texto`);
        if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
        
        const data = await response.json();
        
        if (data.erro) {
            document.getElementById('notationResult').value = `Erro: ${data.erro}`;
        } else if (data.texto_formatado) {
            document.getElementById('notationResult').value = data.texto_formatado;
        } else {
            document.getElementById('notationResult').value = "Formato de resposta inesperado.";
        }
    } catch (error) {
        console.error("Erro ao buscar texto da notação:", error);
        document.getElementById('notationResult').value = "Erro ao carregar o texto. Verifique a conexão.";
    }
}
	
	// --- FUNÇÕES DE RENDERIZAÇÃO E FORMATAÇÃO (ATUALIZADAS) ---

    function renderResultsList(items) {
        // ... (sem alteração)
        resultsContainer.innerHTML = '';
        if (items.length === 0) {
            resultsContainer.innerHTML = '<div class="result-item"><span class="text">Nenhum resultado encontrado.</span></div>';
            return;
        }
        items.forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'result-item';
            itemDiv.dataset.notation = item.notacao;
            if (selectedNotations.has(item.notacao)) {
                itemDiv.classList.add('selected');
            }
            itemDiv.innerHTML = `
                <span class="notation">${item.notacao}</span>
                <p class="text">${item.texto}</p>
            `;
            resultsContainer.appendChild(itemDiv);
        });
    }

    /**
     * NOVA FUNÇÃO: Pega uma lista de notações e agrupa, gerando uma string compacta.
     * Ex: ['58.7', '58.9'] se torna '58.[7,9]'
     */
    function compactarNotacoes(notationsArray) {
        if (notationsArray.length === 0) return "";

        const grouped = {};
        // Agrupa as notações pelo seu "pai"
        notationsArray.forEach(notation => {
            const lastDotIndex = notation.lastIndexOf('.');
            // Se não tem ponto, a notação é o próprio pai (ex: um artigo inteiro '6')
            const parent = lastDotIndex === -1 ? notation : notation.substring(0, lastDotIndex);
            const child = lastDotIndex === -1 ? null : notation.substring(lastDotIndex + 1);

            if (!grouped[parent]) {
                grouped[parent] = [];
            }
            if (child !== null) {
                grouped[parent].push(child);
            }
        });

        const finalParts = [];
        // Remonta a string de forma compacta
        for (const parent in grouped) {
            const children = grouped[parent];
            if (children.length === 0) { // É um artigo sozinho, ex: '6'
                finalParts.push(parent);
            } else if (children.length === 1) { // Apenas um filho, usa notação normal
                finalParts.push(`${parent}.${children[0]}`);
            } else { // Múltiplos filhos, usa a notação compacta com colchetes
                finalParts.push(`${parent}.[${children.join(',')}]`);
            }
        }
        
        return finalParts.sort().join(',');
    }
    
    // ATUALIZADO: Chama a nova função de compactação
    function updateNotationOutput() {
        const sortedNotations = Array.from(selectedNotations).sort();
        // Chama a função para compactar antes de exibir
        const compactedString = compactarNotacoes(sortedNotations);
        notationOutput.value = compactedString;
        fetchNotationText(document.getElementById('notationOutput').value);
		
    }

    // --- EVENTOS DA INTERFACE (ATUALIZADOS) ---

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        let results = [];

        // NOVO: Verifica se a busca é um número ou um texto
        if (/^\d+$/.test(query)) {
            // Se for um número, busca pela estrutura do artigo
            results = buscarEstruturaDoArtigo(query);
        } else if (query.length >= 3) {
            // Se for texto, faz a busca por palavra-chave
            results = pesquisarPorTexto(query);
        }

        // Lógica para exibir ou esconder a lista
        if (query.length > 0) {
             renderResultsList(results);
             resultsContainer.style.display = 'block';
        } else {
            // Se o campo esvaziar, mostra os selecionados ou esconde
            if (selectedNotations.size > 0) {
                const selectedItems = getItemsFromNotations(selectedNotations);
                renderResultsList(selectedItems);
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.style.display = 'none';
            }
        }
    });
    
	notationOutput.addEventListener('input', () => {
		console.log("...");
        fetchNotationText(document.getElementById('notationOutput').value);
    });

    resultsContainer.addEventListener('click', (event) => {
        const clickedItem = event.target.closest('.result-item');
        if (clickedItem && clickedItem.dataset.notation) {
            const notation = clickedItem.dataset.notation;
            if (selectedNotations.has(notation)) {
                selectedNotations.delete(notation);
            } else {
                selectedNotations.add(notation);
            }
            clickedItem.classList.toggle('selected');
            updateNotationOutput();
        }
    });
    
    searchInput.addEventListener('focus', () => {
        // Apenas mostra a lista se houver algo nela ou se tiver itens selecionados
        if (resultsContainer.innerHTML !== "" || selectedNotations.size > 0) {
             if (searchInput.value.length < 3 && selectedNotations.size > 0) {
                const selectedItems = getItemsFromNotations(selectedNotations);
                renderResultsList(selectedItems);
            }
            resultsContainer.style.display = 'block';
        }
    });

    // CORREÇÃO DO BUG: A lista só some se clicar fora do componente de busca
    document.addEventListener('click', (event) => {
        const searchWrapper = document.querySelector('.search-wrapper');
        // Se o elemento clicado NÃO está dentro do .search-wrapper, esconde a lista
        if (!searchWrapper.contains(event.target)) {
            resultsContainer.style.display = 'none';
        }
    });

    loadDatabase();
});