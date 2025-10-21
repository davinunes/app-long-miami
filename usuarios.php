<div class="container">
    <div class="header">
        <h1>Gerenciar Usuários</h1>
        <p>Adicione, edite e gerencie os usuários do sistema.</p>
    </div>
    
    <div class="table-container">
        <div class="header-actions">
            <a href="#modal-usuario" class="btn-new modal-trigger" id="btn-novo-usuario">+ Novo Usuário</a>
        </div>
        
        <table class="striped highlight">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Nível (Role)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="usuarios-table-body">
                <tr><td colspan="4" style="text-align: center;">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="modal-usuario" class="modal">
    <form id="form-usuario" onsubmit="return false;">
        <div class="modal-content">
            <h4 id="modal-usuario-titulo">Novo Usuário</h4>
            
            <input type="hidden" id="usuario_id">
            
            <div class="row">
                <div class="input-field col s12">
                    <input id="usuario_nome" type="text" class="validate" required>
                    <label for="usuario_nome">Nome</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="usuario_email" type="email" class="validate" required>
                    <label for="usuario_email">Email</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <select id="usuario_role" required>
                        <option value="" disabled selected>Escolha um nível</option>
                        <option value="conselheiro">Conselheiro</option>
                        <option value="condomino">Condômino</option>
                        <option value="fiscal">Fiscal</option>
                        <option value="sindico">Síndico</option>
                        <option value="pode_assinar">Pode Assinar</option>
                        <option value="admin">Admin</option>
                        <option value="dev">Dev</option>
                    </select>
                    <label>Nível (Role)</label>
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