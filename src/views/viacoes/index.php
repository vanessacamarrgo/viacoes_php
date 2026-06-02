<?php
declare(strict_types=1);

/** @var list<array> $viacoes */
/** @var string $busca */

$busca = $busca ?? '';
$filtros   = $filtros   ?? [];
$temFiltro = $temFiltro ?? false;
?>
<link rel="stylesheet" href="/css/layout.css">

<main class="container">
    <header class="page-header">
        <div>
            <h1 style="color: white">Viação / Marcas</h1>
            <nav class="actions">
                <a class="btn btn-primary" href="/home">Home</a>
                <a class="btn btn-primary" href="/historico?tab=viacoes">Histórico</a>
                <a class="btn btn-primary" href="/usuarios/">Usuários</a>
                <a class="btn btn-primary" href="/logout">Logout</a>
            </nav>
        </div>
        <a href="/viacoes/create" class="btn btn-primary">
            + Criar viação
        </a>
        <?php if (isset($_SESSION['user_nivel']) && $_SESSION['user_nivel'] === 1): ?>
            <div class="modoADM">
                <strong>Você é administrador</strong>
            </div>
        <?php endif; ?>
    </header>

    <form method="get" action="/viacoes" class="search-bar search-bar--multi">
        <div class="search-group search-group--multi">

            <input type="search" name="nome"
                   class="search-input"
                   placeholder="Nome da viação…"
                   value="<?= htmlspecialchars($filtros['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <input type="search" name="cidade"
                   class="search-input"
                   placeholder="Cidade…"
                   value="<?= htmlspecialchars($filtros['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <input type="search" name="url"
                   class="search-input"
                   placeholder="URL…"
                   value="<?= htmlspecialchars($filtros['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

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
                <a href="/viacoes" class="btn btn-ghost">Limpar</a>
            <?php endif; ?>
        </div>

        <?php if ($temFiltro): ?>
            <p class="search-info">
                <?= count($viacoes) ?> resultado(s) encontrado(s) nesta página
            </p>
        <?php endif; ?>
    </form>

    <?php if (count($viacoes) === 0): ?>
        <div class="card empty-state">
            <?php if ($busca !== ''): ?>
                <p>Nenhuma viação encontrada para a busca "<strong><?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?></strong>".</p>
            <?php else: ?>
                <p>Nenhuma viação cadastrada no sistema.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-card">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Cidade</th>
                    <th>Logo</th>
                    <th>Link</th>
                    <th>Status</th>
                    <th class="date-text">Criada em</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($viacoes as $viacao): ?>
                    <tr>
                        <td><strong><?= (int) $viacao->id ?></strong></td>
                        <td>
                            <a href="/viacoes/<?= (int) $viacao->id ?>" style="text-decoration: none; color: #000000; font-weight: bold;">
                                <?= htmlspecialchars($viacao->nome, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($viacao->cidade ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(substr($viacao->logo ?? '', 0, 20), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($viacao->url, ENT_QUOTES, 'UTF-8') ?>"
                               target="_blank" class="url-link">
                                Ver site
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($viacao->data_exclusao)): ?>
                                <span class="badge badge-inactive" style="background: #dc3545; color: #ffffff; border-color: #dc3545;">Deletado</span>
                            <?php elseif ($viacao->status == 1 || $viacao->status === true || $viacao->status === 'ativo'): ?>
                                <span class="badge badge-active">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="date-text">
                            <?= htmlspecialchars($viacao->data_criacao ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div class="actions" style="justify-content: flex-end;">
                                <a href="/viacoes/<?= (int) $viacao->id ?>/edit" class="btn btn-ghost">
                                    Editar
                                </a>
                                <?php if (empty($viacao->data_exclusao)): ?>
                                    <form method="post" action="/viacoes/<?= (int) $viacao->id ?>/delete"
                                          onsubmit="return confirm('Remover esta marca?');">
                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/viacoes/<?= (int) $viacao->id ?>/restore"
                                          onsubmit="return confirm('Restaurar esta marca?');">
                                        <button type="submit" class="btn btn-danger">Restaurar</button>
                                    </form>
                                <?php endif ?>
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
    <?php endif; ?>
</main>