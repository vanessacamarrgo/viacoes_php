<?php

declare(strict_types=1);

/**
 * @var list<array|stdClass> $historico
 * @var string $title
 * @var string $tabAtual
 * @var string $filtroUsuario
 * @var string $filtroAlvo
 * @var int $paginaAtual
 * @var int $totalPaginas
 * @var array $filtros
 */

$tabAtual      = $tabAtual      ?? '';
$filtroUsuario = $filtroUsuario ?? '';
$filtroAlvo    = $filtroAlvo    ?? '';
$filtros       = $filtros       ?? [];

if (isset($historico) && is_array($historico)) {
    foreach ($historico as $key => $item) {
        if (is_object($item)) {
            $historico[$key] = (array) $item;
        }
    }
} else {
    $historico = [];
}

// título dinâmico baseado no filtro selecionado
$nomeAba = 'Histórico Geral';
if ($tabAtual === 'usuarios') {
    $nomeAba .= ' - Usuários';
} elseif ($tabAtual === 'viacoes') {
    $nomeAba .= ' - Viações';
}
?>
<title><?= $nomeAba ?></title>

<?php
$rotulosUsuarios = [
        'nome'   => 'Nome',
        'email'  => 'E-mail',
        'status' => 'Status',
];

$rotulosViacoes = [
        'nome'   => 'Nome',
        'url'    => 'Site',
        'cidade' => 'Cidade',
        'status' => 'Status',
        'logo'   => 'Logo',
];

$formatar = static function (string $campo, mixed $valor): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    if ($campo === 'status') {
        $v = strtolower(trim((string)$valor));
        if ($v === 'true' || $v === 'ativo' || $v === '1') return 'Ativo';
        if ($v === 'false' || $v === 'inativo' || $v === '0' || $v === 'deletado') return 'Inativo';
    }
    if ($campo === 'logo') {
        return '✔ ' . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

// badge de cor por ação normalizada
$badgeAcao = static function (string $acao): string {
    $map = [
            'CREATE'  => 'badge badge-active',
            'UPDATE'  => 'badge',
            'DELETE'  => 'badge badge-inactive',
            'RESTORE' => 'badge badge-active',
    ];
    $acaoAlta = strtoupper(trim($acao));
    if ($acaoAlta === 'EDITAR')  $acaoAlta = 'UPDATE';
    if ($acaoAlta === 'CRIAR')   $acaoAlta = 'CREATE';
    if ($acaoAlta === 'REMOVER') $acaoAlta = 'DELETE';

    $class = $map[$acaoAlta] ?? 'badge';
    return '<span class="' . $class . '">' . htmlspecialchars($acaoAlta, ENT_QUOTES, 'UTF-8') . '</span>';
};

$temFiltro = $filtroUsuario !== '' || $filtroAlvo !== '' || $tabAtual !== '';
?>

<link rel="stylesheet" href="/css/layout.css">

<style>
    .tabs-container {
        display: flex;
        gap: var(--spacing-sm);
        margin-bottom: var(--spacing-lg);
        border-bottom: 1px solid var(--color-border);
        padding-bottom: var(--spacing-sm);
    }
    .tab-btn {
        padding: var(--spacing-sm) var(--spacing-md);
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: var(--font-size-base);
        background: var(--color-bg-alt);
        color: var(--color-secondary);
        transition: all var(--transition);
    }
    .tab-btn:hover {
        background: var(--color-border);
    }
    .tab-btn.active {
        background: #0d2240; /* Cor primária do painel */
        color: var(--color-text-light);
    }
    .msg-no-changes {
        font-size: var(--font-size-sm);
        color: var(--color-text-muted);
        font-style: italic;
        padding: var(--spacing-xs) 0;
    }
</style>

<div class="container">

    <div class="page-header">
        <h1>Histórico Geral</h1>
        <a href="/viacoes" class="btn btn-ghost">Voltar</a>
    </div>

    <div class="tabs-container">
        <a href="/historico?tab=viacoes" class="tab-btn <?= $tabAtual === 'viacoes' ? 'active' : '' ?>">Histórico de Viações</a>
        <a href="/historico?tab=usuarios" class="tab-btn <?= $tabAtual === 'usuarios' ? 'active' : '' ?>">Histórico de Usuários</a>
    </div>

    <form method="get" action="/historico" class="search-bar search-bar--multi">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabAtual, ENT_QUOTES, 'UTF-8') ?>">
        <div class="search-group search-group--multi">
            <input
                    type="search"
                    name="usuario"
                    class="search-input"
                    placeholder="Alterado por (Nome do Admin)…"
                    value="<?= htmlspecialchars($filtroUsuario, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="off"
            >
            <input
                    type="search"
                    name="alvo"
                    class="search-input"
                    placeholder="<?= $tabAtual === 'usuarios' ? 'Nome do usuário afetado…' : ($tabAtual === 'viacoes' ? 'Nome da viação afetada…' : 'Buscar termo no histórico…') ?>"
                    value="<?= htmlspecialchars($filtroAlvo, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="off"
            >
            <button type="submit" class="btn btn-primary" style="background: #0d2240; color: #ffffff;">Filtrar</button>
            <?php if ($temFiltro): ?>
                <a href="/historico" class="btn btn-ghost">Limpar Filtros</a>
            <?php endif; ?>
        </div>
        <?php if ($temFiltro): ?>
            <p class="search-info"><?= count($historico) ?> registro(s) listado(s) nesta página</p>
        <?php endif; ?>
    </form>

    <?php if (count($historico) === 0): ?>
        <div class="card empty-state">
            <p>Nenhum registro de alteração encontrado.</p>
        </div>
    <?php else: ?>

        <div class="table-card">
            <table class="historico-table">
                <thead>
                <tr>
                    <th>Alterado Por</th>
                    <th>Entidade</th>
                    <th>Ação</th>
                    <th>Alterações</th>
                    <th>Data da Alteração</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($historico as $h): ?>
                    <?php
                    $entidadeTipoRow = strtolower((string)($h['entidade_tipo'] ?? ''));
                    $isUsuario       = ($entidadeTipoRow === 'usuarios' || $entidadeTipoRow === 'usuario');

                    // seleciona os rótulos corretos para esta linha da tabela
                    $rotulos = $isUsuario ? $rotulosUsuarios : $rotulosViacoes;

                    // normalização do gatilho de ação
                    $campoAlteradoRaw = (string)($h['campo_alterado'] ?? $h['acao'] ?? '');
                    $acaoOriginal     = strtoupper(trim($campoAlteradoRaw));
                    $acaoLimpa        = $acaoOriginal;

                    if ($acaoOriginal === 'EDITAR')  $acaoLimpa = 'UPDATE';
                    if ($acaoOriginal === 'CRIAR')   $acaoLimpa = 'CREATE';
                    if ($acaoOriginal === 'REMOVER') $acaoLimpa = 'DELETE';

                    $vAntigoRaw = $h['valor_antigo'] ?? null;
                    $vNovoRaw   = $h['valor_novo']   ?? $h['dados'] ?? null;

                    $jsonAntigo = is_string($vAntigoRaw) ? (json_decode($vAntigoRaw, true) ?? []) : (is_array($vAntigoRaw) ? $vAntigoRaw : []);
                    $jsonNovo   = is_string($vNovoRaw)   ? (json_decode($vNovoRaw, true)   ?? []) : (is_array($vNovoRaw)   ? $vNovoRaw   : []);

                    $antes  = !empty($jsonNovo['antes'])  ? $jsonNovo['antes']  : (!empty($jsonAntigo['antes']) ? $jsonAntigo['antes'] : null);
                    $depois = !empty($jsonNovo['depois']) ? $jsonNovo['depois'] : (!empty($jsonAntigo['depois']) ? $jsonAntigo['depois'] : null);

                    // fallback se o JSON vier plano/direto na raiz
                    if ($antes === null && $depois === null) {
                        if (isset($jsonNovo['status']) || isset($jsonNovo['nome']) || isset($jsonNovo['email'])) { $depois = $jsonNovo; }
                        if (isset($jsonAntigo['status']) || isset($jsonAntigo['nome']) || isset($jsonAntigo['email'])) { $antes = $jsonAntigo; }
                    }

                    // identifica quais campos mudaram para exibir estritamente apenas eles
                    $camposAlterados = [];
                    foreach ($rotulos as $campo => $_) {
                        $vA = $antes[$campo] ?? null;
                        $vD = $depois[$campo] ?? null;

                        if (($vA === null || $vA === '') && ($vD === null || $vD === '')) {
                            continue;
                        }

                        if ($campo === 'status') {
                            $normA = (str_replace(['1', 'true', 'ativo'], 'ativo', strtolower(trim((string)$vA))) === 'ativo') ? 'ativo' : 'inativo';
                            $normD = (str_replace(['1', 'true', 'ativo'], 'ativo', strtolower(trim((string)$vD))) === 'ativo') ? 'ativo' : 'inativo';
                            if ($normA !== $normD) {
                                $camposAlterados[] = $campo;
                            }
                            continue;
                        }

                        if ((string)$vA !== (string)$vD) {
                            $camposAlterados[] = $campo;
                        }
                    }
                    ?>

                    <tr>

                        <td>
                            <strong><?= htmlspecialchars((string)($h['usuario_nome'] ?? $h['alterado_por_nome'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <br><small class="dados">#<?= (int)($h['alterado_por'] ?? $h['usuario_id'] ?? 1) ?></small>
                        </td>

                        <td>
                            <span class="dados" style="font-weight: 700; font-size: var(--font-size-xs); text-transform: uppercase; display: block; margin-bottom: 4px;">
                                <?= $isUsuario ? 'USUÁRIO' : 'VIAÇÃO' ?>
                            </span>

                            <?php
                            $nomeAlvo = $depois['nome'] ?? $antes['nome'] ?? null;

                            if ($nomeAlvo !== null): ?>
                                <strong style="color: var(--color-primary); font-size: var(--font-size-sm); display: block; margin-top: 2px;">
                                    <?= htmlspecialchars((string)$nomeAlvo, ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                                <small class="color-text-muted" style="font-size: 11px;">#<?= (int)($h['entidade_id'] ?? 0) ?></small>
                            <?php else: ?>
                                <small class="color-text-muted" style="font-style: italic;">ID: #<?= (int)($h['entidade_id'] ?? 0) ?></small>
                            <?php endif; ?>
                        </td>

                        <td><?= $badgeAcao($acaoLimpa) ?></td>

                        <td>
                            <div class="diff-grid">
                                <?php if ($acaoLimpa === 'CREATE' && $depois !== null): ?>
                                    <div class="diff-col unico">
                                        <h4>Dados iniciais</h4>
                                        <table>
                                            <?php foreach ($rotulos as $campo => $rotulo): ?>
                                                <tr>
                                                    <td><?= $rotulo ?>:</td>
                                                    <td><?= $formatar($campo, $depois[$campo] ?? null) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>

                                <?php elseif ($acaoLimpa === 'DELETE' && $antes !== null): ?>
                                    <div class="diff-col antes">
                                        <h4>Dados removidos</h4>
                                        <table>
                                            <?php foreach ($rotulos as $campo => $rotulo): ?>
                                                <tr>
                                                    <td><?= $rotulo ?>:</td>
                                                    <td><?= $formatar($campo, $antes[$campo] ?? null) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>

                                <?php elseif ($acaoLimpa === 'UPDATE'): ?>

                                    <?php if (count($camposAlterados) > 0): ?>
                                        <div class="diff-col antes">
                                            <h4>Antes</h4>
                                            <table>
                                                <?php foreach ($camposAlterados as $campo): ?>
                                                    <tr class="campo-alterado">
                                                        <td><?= $rotulos[$campo] ?>:</td>
                                                        <td><?= $formatar($campo, $antes[$campo] ?? null) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>

                                        <div class="diff-col depois">
                                            <h4>Depois</h4>
                                            <table>
                                                <?php foreach ($camposAlterados as $campo): ?>
                                                    <tr class="campo-alterado">
                                                        <td><?= $rotulos[$campo] ?>:</td>
                                                        <td><?= $formatar($campo, $depois[$campo] ?? null) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="diff-col unico">
                                            <p class="msg-no-changes">Nenhum campo mudou (dados de envio idênticos).</p>
                                        </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <div class="diff-col unico">
                                        <pre class="json"><?= htmlspecialchars(json_encode($jsonNovo ?: $jsonAntigo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="date-text" style="white-space:nowrap; vertical-align: middle;">
                            <?php
                            $dataRaw = $h['data_alteracao'] ?? $h['criado_em'] ?? '';
                            if ($dataRaw !== '' && $dataRaw !== '—') {
                                try {
                                    $date = new \DateTime($dataRaw);
                                    echo htmlspecialchars($date->format('d/m/Y H:i:s'), ENT_QUOTES, 'UTF-8');
                                } catch (\Exception $e) {
                                    echo htmlspecialchars((string)$dataRaw, ENT_QUOTES, 'UTF-8');
                                }
                            } else {
                                echo '—';
                            }
                            ?>
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

        </div> <?php endif; ?>
</div>