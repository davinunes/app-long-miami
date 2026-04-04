// js/regimento-busca.js - Busca no regimento interno

let selectedArticles = [];

function getSelectedArticles() {
    return selectedArticles;
}

function setSelectedArticles(articles) {
    selectedArticles = articles || [];
    console.log('Artigos carregados:', selectedArticles);
    updateSelectedArticlesUI();
}

async function inicializarBuscaRegimento() {
    const searchInput = document.getElementById('regimento-search');
    const resultsContainer = document.getElementById('regimento-results');
    
    if (!searchInput) return;

    let debounceTimer;

    searchInput.addEventListener('input', async () => {
        clearTimeout(debounceTimer);
        const query = searchInput.value.trim();
        
        if (query.length < 1) {
            resultsContainer.classList.remove('show');
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`${API_BASE_URL_PHP}/regimento.php?q=${encodeURIComponent(query)}`);
                
                if (!response.ok) throw new Error('Erro na busca');
                
                const data = await response.json();
                renderResults(data.results || []);
            } catch (error) {
                console.error('Erro ao buscar regimento:', error);
                resultsContainer.innerHTML = '<div class="regimento-result-item">Erro ao buscar. Tente novamente.</div>';
                resultsContainer.classList.add('show');
            }
        }, 300);
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length > 0 || selectedArticles.length > 0) {
            resultsContainer.classList.add('show');
        }
    });

    document.addEventListener('click', (e) => {
        const searchWrapper = searchInput.closest('.regimento-busca');
        if (!searchWrapper || !searchWrapper.contains(e.target)) {
            resultsContainer.classList.remove('show');
        }
    });

    resultsContainer.addEventListener('click', (e) => {
        const item = e.target.closest('.regimento-result-item');
        if (item) {
            const notation = item.dataset.notation;
            const text = item.dataset.text;
            
            toggleArticleSelection(notation, text);
            resultsContainer.classList.remove('show');
            searchInput.value = '';
        }
    });
}

function renderResults(items) {
    const resultsContainer = document.getElementById('regimento-results');
    
    if (items.length === 0) {
        resultsContainer.innerHTML = '<div class="regimento-result-item">Nenhum resultado encontrado.</div>';
    } else {
        resultsContainer.innerHTML = items.map(item => {
            const isSelected = selectedArticles.some(a => a.notation === item.notacao);
            return `
            <div class="regimento-result-item ${isSelected ? 'selected' : ''}" 
                 data-notation="${item.notacao}" 
                 data-text="${item.texto.replace(/"/g, '&quot;')}">
                <span class="notation">Art. ${item.notacao} ${isSelected ? '<small style="color:#fff;font-size:10px;">(selecionado)</small>' : ''}</span>
                <p class="text">${item.texto}</p>
                ${item.capitulo ? `<span class="capitulo">${item.capitulo.titulo || ''}</span>` : ''}
            </div>
        `}).join('');
    }
    
    resultsContainer.classList.add('show');
}

function toggleArticleSelection(notation, text) {
    const index = selectedArticles.findIndex(a => a.notation === notation);
    
    if (index > -1) {
        selectedArticles.splice(index, 1);
    } else {
        selectedArticles.push({ notation, text });
    }
    
    updateSelectedArticlesUI();
}

function updateSelectedArticlesUI() {
    const container = document.getElementById('selected-articles');
    const list = document.getElementById('selected-articles-list');
    const badge = document.getElementById('artigos-count-badge');
    
    if (selectedArticles.length === 0) {
        container.style.display = 'none';
        if (badge) badge.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    if (badge) {
        badge.style.display = 'inline';
        badge.textContent = selectedArticles.length + ' artigo(s)';
    }
    
    list.innerHTML = selectedArticles.map(article => `
        <span class="selected-article-tag">
            Art. ${article.notation}
            <button type="button" onclick="removerArtigo('${article.notation}')">&times;</button>
        </span>
    `).join('');
}

window.removerArtigo = function(notation) {
    selectedArticles = selectedArticles.filter(a => a.notation !== notation);
    updateSelectedArticlesUI();
};

window.adicionarArtigosATextarea = function() {
    if (selectedArticles.length === 0) return;
    
    const fundamentacao = document.getElementById('fundamentacao_legal');
    if (!fundamentacao) return;
    
    const conteudo = selectedArticles.map(article => {
        return `\nArt. ${article.notation}: ${article.text}`;
    }).join('\n');
    
    fundamentacao.value += conteudo;
    
    selectedArticles = [];
    updateSelectedArticlesUI();
};
