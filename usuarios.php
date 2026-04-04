<div class="container">
    <div class="header">
        <h1>Gerenciar Usuários</h1>
        <p>Adicione, edite e gerencie os usuários do sistema.</p>
    </div>
    
    <div class="table-container">
        <div class="header-actions">
            <a href="#modal-usuario" class="btn-new modal-trigger" id="btn-novo-usuario">+ Novo Usuário</a>
            <a href="#modal-grupos" class="btn-new modal-trigger" id="btn-gerenciar-grupos">⚙ Gerenciar Grupos</a>
        </div>
        
        <table class="striped highlight">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Grupos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="usuarios-table-body">
                <tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Usuário -->
<div id="modal-usuario" class="modal modal-fixed-footer">
    <form id="form-usuario" onsubmit="return false;">
        <div class="modal-content">
            <h4 id="modal-usuario-titulo">Novo Usuário</h4>
            
            <input type="hidden" id="usuario_id">
            
            <div class="row">
                <div class="input-field col s12 m6">
                    <input id="usuario_nome" type="text" class="validate" required>
                    <label for="usuario_nome">Nome Completo</label>
                </div>
                <div class="input-field col s12 m6">
                    <input id="usuario_email" type="email" class="validate" required>
                    <label for="usuario_email">Email</label>
                </div>
            </div>
            
            <div class="row">
                <div class="input-field col s12">
                    <select id="usuario_grupos" multiple>
                        <option value="" disabled>Selecione os grupos</option>
                    </select>
                    <label>Grupos</label>
                    <span class="helper-text">Grupos aos quais o usuário pertence</span>
                </div>
            </div>
            
            <div class="row">
                <div class="input-field col s12">
                    <input id="usuario_senha" type="password">
                    <label for="usuario_senha">Senha</label>
                    <span class="helper-text" id="senha-helper-text">Para criar, a senha é obrigatória. Para editar, deixe em branco para não alterar.</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
            <button type="submit" class="waves-effect waves-green btn" id="modal-salvar-usuario">Salvar</button>
        </div>
    </form>
</div>

<!-- Modal de Grupos -->
<div id="modal-grupos" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4>Gerenciar Grupos</h4>
        
        <ul class="collection" id="grupos-lista">
            <li class="collection-item">Carregando...</li>
        </ul>
        
        <div class="section">
            <h5>Criar Novo Grupo</h5>
            <div class="row">
                <div class="input-field col s12 m6">
                    <input id="novo_grupo_nome" type="text" placeholder="Nome do grupo">
                </div>
                <div class="input-field col s12 m6">
                    <input id="novo_grupo_desc" type="text" placeholder="Descrição (opcional)">
                </div>
            </div>
            <div class="row">
                <div class="col s12" id="novo-grupo-papeis">
                </div>
            </div>
            <button class="btn waves-effect waves-light" id="btn-criar-grupo">Criar Grupo</button>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Fechar</a>
    </div>
</div>

<!-- Modal de Edição de Grupo -->
<div id="modal-editar-grupo" class="modal modal-fixed-footer">
    <form id="form-grupo" onsubmit="return false;">
        <div class="modal-content">
            <h4 id="modal-grupo-titulo">Editar Grupo</h4>
            <input type="hidden" id="grupo_id">
            
            <div class="row">
                <div class="input-field col s12 m6">
                    <input id="grupo_nome" type="text" required>
                    <label for="grupo_nome">Nome</label>
                </div>
                <div class="input-field col s12 m6">
                    <input id="grupo_desc" type="text">
                    <label for="grupo_desc">Descrição</label>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12">
                    <label>Papéis do Grupo</label>
                    <div id="grupo-papeis-checkboxes" class="checkbox-grid">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
            <button type="button" class="waves-effect waves-green btn" id="btn-salvar-grupo">Salvar Grupo</button>
        </div>
    </form>
</div>

<style>
.chip {
    display: inline-block;
    padding: 0 8px;
    height: 24px;
    font-size: 12px;
    line-height: 24px;
    border-radius: 16px;
    background-color: #e0e0e0;
    margin: 2px;
}
.collection-item > div {
    overflow: hidden;
}
.collection-item .btn-small {
    height: 32px;
    line-height: 32px;
    width: 32px;
    padding: 0;
}
.collection-item .btn-small i {
    font-size: 18px;
}
</style>
