<?php
declare(strict_types=1);
use App\Models\Usuario;

/** @var list<Usuario>  $usuarios */
/** @var string $busca */

$busca = $busca ?? '';

?>
<title>Usuários</title>

<div>
<link rel="stylesheet" href="/css/layout.css">

<main class="container">
    <header class="page-header">
        <div>
            <h1 style="color: white">Usuários</h1>
            <nav class="actions">
                <a class="btn btn-primary" href="/home">Home</a>
                <a class="btn btn-primary" href="/historico?tab=usuarios">Histórico</a>
                <a class="btn btn-primary" href="/viacoes/">Viações</a>
                <a class="btn btn-primary"  href="/logout">Logout</a>
            </nav>
        </div>
        <a href="/usuarios/create" class="btn btn-primary">
            + Criar usuário
        </a>
        <?php if (isset($_SESSION['user_nivel']) && $_SESSION['user_nivel'] === 1): ?>
            <div class="modoADM">
                <strong>Você é administrador</strong>
            </div>
        <?php endif; ?>
    </header>

    <!-- Barra de busca -->
    <?php
    $filtros   = $filtros   ?? [];
    $temFiltro = $temFiltro ?? false;
    ?>

    <!--filtros-->
    <form method="get" action="/usuarios" class="search-bar search-bar--multi">
        <div class="search-group search-group--multi">

            <input type="search" name="nome"
                   class="search-input"
                   placeholder="Nome do usuário…"
                   value="<?= htmlspecialchars($filtros['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <input type="search" name="email"
                   class="search-input"
                   placeholder="E-mail…"
                   value="<?= htmlspecialchars($filtros['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <select name="status" class="search-select">
                <option value="">Todos os status</option>
                <option value="1" <?= ($filtros['status'] ?? '') === '1' ? 'selected' : '' ?>>Ativo</option>
                <option value="0" <?= ($filtros['status'] ?? '') === '0' ? 'selected' : '' ?>>Inativo</option>
            </select>

            <select name="excluido" class="search-select">
                <option value="">Apenas não excluídas</option>
                <option value="1" <?= ($filtros['excluido'] ?? '') === '1' ? 'selected' : '' ?>>Excluído</option>
            </select>

            <button type="submit" class="btn btn-primary">Filtrar</button>

            <?php if ($temFiltro): ?>
                <a href="/usuarios" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>
        </div>

        <?php if ($temFiltro): ?>
            <p class="search-info">
                <?= count($usuarios) ?> resultado(s) encontrado(s)
            </p>
        <?php endif; ?>
    </form>

    <?php if (count($usuarios) === 0): ?>
        <div class="card empty-state">
            <?php if ($busca !== ''): ?>
                <p>Nenhum usuário encontrado para a busca "<strong><?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?></strong>".</p>
            <?php else: ?>
                <p>Nenhum usuário cadastrado no sistema.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-card">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th class="date-text">Criada em</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
                </thead>
                <tbody>

                <tbody>
                <?php foreach ($usuarios as $usuario): ?>
        <?php
    $isDeletado = !empty($usuario->data_exclusao);
    $isAtivo    = ($usuario->status == 1 || $usuario->status === true) && !$isDeletado;
    ?>
    <tr>
        <td><strong><?= (int) $usuario->id ?></strong></td>
        <td>
            <a href="/usuarios/<?= (int) $usuario->id ?>" style="text-decoration: none; color: #000000; font-weight: bold;">
                <?= htmlspecialchars($usuario->nome, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </td>
        <td><?= htmlspecialchars($usuario->email, ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <?php if ($isDeletado): ?>
                <span class="badge badge-inactive" style="background: #dc3545; color: #ffffff; border-color: #dc3545;">Deletado</span>
            <?php elseif ($isAtivo): ?>
                <span class="badge badge-active">Ativo</span>
            <?php else: ?>
                <span class="badge badge-inactive">Inativo</span>
            <?php endif; ?>
        </td>

                        <td class="date-text">
                            <?= htmlspecialchars($usuario->data_criacao ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div class="actions" style="justify-content: flex-end; gap: 8px;">
                                <a href="/usuarios/<?= (int) $usuario->id ?>/edit" class="btn btn-ghost">
                                    Editar
                                </a>

                                <?php if ($isDeletado): ?>
                                    <form method="post" action="/usuarios/<?= (int) $usuario->id ?>/restore"
                                          onsubmit="return confirm('Restaurar este usuário?');" style="display: inline;">
                                        <button type="submit" class="btn btn-ghost">
                                            Restaurar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/usuarios/<?= (int) $usuario->id ?>/delete"
                                          onsubmit="return confirm('Remover este usuário?');" style="display: inline;">
                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
    <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 20px; padding-bottom: 10px;">

        <?php if ($paginaAtual > 1): ?>
            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $paginaAtual - 1])) ?>" style="background: #0d2240; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <?php $ativo = ($i === $paginaAtual); ?>
            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $i])) ?>"
               style="padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; <?= $ativo ? 'background: white; color: #0d2240;' : 'background: rgba(255,255,255,0.2); color: white;' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($paginaAtual < $totalPaginas): ?>
            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $paginaAtual + 1])) ?>" style="background: #0d2240; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px;">Próxima &raquo;</a>
        <?php endif; ?>

    </div>
<?php endif; ?>
        </div>
</main>
    <?php endif; ?>