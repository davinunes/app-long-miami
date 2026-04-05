// js/tinymce-loader.js - Carregador do TinyMCE
// Inclui TinyMCE via CDN e fornece funções para inicialização condicional

const TINYMCE_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js';

let tinyMCELoaded = false;
let tinyMCELoading = false;
let tinyMCEInitCallbacks = [];

function loadTinyMCE() {
    return new Promise((resolve, reject) => {
        if (tinyMCELoaded) {
            resolve();
            return;
        }
        
        if (tinyMCELoading) {
            tinyMCEInitCallbacks.push(resolve);
            return;
        }
        
        tinyMCELoading = true;
        tinyMCEInitCallbacks.push(resolve);
        
        const script = document.createElement('script');
        script.src = TINYMCE_CDN;
        script.onload = function() {
            tinyMCELoaded = true;
            tinyMCELoading = false;
            tinyMCEInitCallbacks.forEach(cb => cb());
            tinyMCEInitCallbacks = [];
        };
        script.onerror = function() {
            tinyMCELoading = false;
            reject(new Error('Falha ao carregar TinyMCE'));
        };
        document.head.appendChild(script);
    });
}

function getTinyMCEConfig(selector, height = 200) {
    return {
        selector: selector,
        height: height,
        menubar: false,
        plugins: 'advlist autolink lists link image preview table code help wordcount',
        toolbar: 'formatselect | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | link image | code',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
        branding: false,
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false
    };
}

async function initTinyMCEFor(selector, height) {
    if (typeof tinymce === 'undefined') {
        await loadTinyMCE();
    }
    
    const config = getTinyMCEConfig(selector, height);
    
    if (tinymce.get(selector.replace('#', ''))) {
        return tinymce.get(selector.replace('#', ''));
    }
    
    tinymce.init(config);
    return tinymce.get(selector.replace('#', ''));
}

async function destroyTinyMCE(selector) {
    const id = selector.replace('#', '');
    const editor = tinymce.get(id);
    if (editor) {
        editor.remove();
    }
}

function getTinyMCESetting(settingName) {
    return window['tinymce_' + settingName] === '1';
