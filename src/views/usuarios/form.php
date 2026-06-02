<link rel="stylesheet" href="/css/layout.css">

<div class="container">
    <form
            method="post"
            action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>"
            enctype="multipart/form-data"
    >
        <?php if ($method !== null): ?>
            <input type="hidden" name="_method" value="<?= htmlspecialchars($method, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="usuario">Nome do usuário</label>
            <input
                    id="usuario"
                    type="text"
                    name="nome"
                    value="<?= htmlspecialchars($old['nome'] ?? $usuario->nome ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    maxlength="255"
            >
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input
                    id="email"
                    type="text"
                    name="email"
                    value="<?= htmlspecialchars($old['email'] ?? $usuario->email ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <?php if (!isset($usuario->id) && empty($id)): ?>
            <div class="form-group">
                <label>Senha</label>
                <input
                        type="password"
                        name="senha"
                        required
                        maxlength="255"
                >
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Status</label>
            <div class="radio-group">
                <label>
                    <input
                            type="radio"
                            name="status"
                            value="1"
                            <?= (!isset($old['status']) && isset($usuario->status) && $usuario->status == 1) || (isset($old['status']) && $old['status'] == '1') ? 'checked' : '' ?>
                    >
                    Ativo
                </label>

                <label>
                    <input
                            type="radio"
                            name="status"
                            value="0"
                            <?= (!isset($old['status']) && isset($usuario->status) && $usuario->status == 0) || (isset($old['status']) && $old['status'] == '0') ? 'checked' : '' ?>
                    >
                    Inativo
                </label>
            </div>
        </div>

        <button type="submit">Salvar</button>
    </form>
</div>