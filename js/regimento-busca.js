// js/regimento-busca.js - Busca no regimento interno e TinyMCE

let selectedArticles = [];
let tinymceInstance = null;

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
                const response = await fetch(`${API_BASE_URL_PHP}/regimento.php?q=${encodeURIComponent(query)}`, {
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('accessToken')}` }
                });
                
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
        if (!searchWrapper.contains(e.target)) {
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
        resultsContainer.innerHTML = items.map(item => `
            <div class="regimento-result-item ${selectedArticles.some(a => a.notation === item.notacao) ? 'selected' : ''}" 
                 data-notation="${item.notacao}" 
                 data-text="${item.texto.replace(/"/g, '&quot;')}">
                <span class="notation">Art. ${item.notacao}</span>
                <p class="text">${item.texto}</p>
                ${item.capitulo ? `<span class="capitulo">${item.capitulo.titulo || ''}</span>` : ''}
            </div>
        `).join('');
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
    
    if (selectedArticles.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    list.innerHTML = selectedArticles.map(article => `
        <span class="selected-article-tag">
            Art. ${article.notation}
            <button onclick="removerArtigo('${article.notation}')">&times;</button>
        </span>
    `).join('');
}

window.removerArtigo = function(notation) {
    selectedArticles = selectedArticles.filter(a => a.notation !== notation);
    updateSelectedArticlesUI();
};

window.adicionarArtigosATextarea = function() {
    if (selectedArticles.length === 0 || !tinymceInstance) return;
    
    const conteudo = selectedArticles.map(article => {
        return `<p><strong>Art. ${article.notation}:</strong> ${article.text}</p>`;
    }).join('<br>');
    
    tinymceInstance.setContent(tinymceInstance.getContent() + conteudo);
    
    selectedArticles = [];
    updateSelectedArticlesUI();
};

async function inicializarTinyMCE() {
    if (typeof tinymce === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js';
        script.referrerpolicy = 'strict-origin-when-cross-origin';
        script.onload = () => initTinyMCEEditor();
        document.head.appendChild(script);
    } else {
        initTinyMCEEditor();
    }
}

function initTinyMCEEditor() {
    tinymce.init({
        selector: '#fundamentacao_legal',
        height: 200,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif; font-size:14px }',
        init_instance_callback: function(editor) {
            tinymceInstance = editor;
            editor.on('change', function() {
                editor.save();
            });
        }
    });
}
