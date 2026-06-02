<?php
/**
 * @var stdClass $usuario
 * @var array|list<array|stdClass> $historico
 */
?>
<link rel="stylesheet" href="/css/layout.css">

<style>
    .diff-container {
        display: flex;
        gap: 15px;
        font-size: 13px;
        font-family: monospace;
        background: #f8f9fa;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid #e3e6f0;
        color: #333;
    }
    .diff-side {
        flex: 1;
    }
    .diff-side h4 {
        margin: 0 0 8px 0;
        font-size: 11px;
        text-transform: uppercase;
        color: #555;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 4px;
        font-weight: 700;
    }
    .diff-side table {
        width: 100%;
        border-collapse: collapse;
        background: transparent !important;
        margin: 0 !important;
    }
    .diff-side table td {
        padding: 4px 0 !important;
        border: none !important;
        background: transparent !important;
        color: #333 !important;
    }
    .text-before { color: #c0392b; font-weight: bold; background: #fceade; padding: 2px 4px; border-radius: 3px; }
    .text-after { color: #27ae60; font-weight: bold; background: #e8f8f5; padding: 2px 4px; border-radius: 3px; }
    .msg-empty-log { color: #858796; font-style: italic; font-size: 13px; }
</style>

<main class="container">
    <header class="page-header">
        <div>
            <h1 style="color: white">Detalhes do Usuário #<?= (int)$usuario->id ?></h1>
            <nav class="actions">
                <a class="btn btn-primary" href="/usuarios">Voltar para Lista</a>
                <a class="btn btn-primary" href="/usuarios/<?= (int)$usuario->id ?>/edit">Editar Usuário</a>
            </nav>
        </div>
    </header>

    <div class="card" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; color: #333;">
        <p style="margin-bottom: 10px;"><strong>Nome:</strong> <?= htmlspecialchars($usuario->nome, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin-bottom: 10px;"><strong>E-mail:</strong> <?= htmlspecialchars($usuario->email, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin-bottom: 10px;"><strong>Status Atual:</strong>
            <?php if (!empty($usuario->data_exclusao)): ?>
                <span class="badge" style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 4px;">Deletado</span>
            <?php elseif ($usuario->status == 1 || $usuario->status === true || $usuario->status === '1'): ?>
                <span class="badge" style="background: #28a745; color: white; padding: 2px 8px; border-radius: 4px;">Ativo</span>
            <?php else: ?>
                <span class="badge" style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 4px;">Inativo</span>
            <?php endif; ?>
        </p>
        <p style="margin-bottom: 0;"><strong>Cadastrado em:</strong> <?= htmlspecialchars($usuario->data_criacao ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="table-card">
        <h2 style="color: white; margin-bottom: 15px;">Histórico de Alterações do Usuário</h2>
        <?php if (empty($historico)): ?>
            <p style="color: white; padding: 15px 0;">Nenhuma alteração registrada para este usuário no log de auditoria.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th style="width: 15%;">Ação</th>
                    <th style="width: 25%;">Data da Alteração</th>
                    <th style="width: 60%;">Dados Detalhados</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $rotulosUsuarios = [
                    'nome'   => 'Nome',
                    'email'  => 'E-mail',
                    'status' => 'Status',
                ];

                $formatar = static function (string $campo, mixed $valor): string {
                    if ($valor === null || $valor === '') return '—';
                    if ($campo === 'status') {
                        $v = strtolower(trim((string)$valor));
                        return ($v === 'true' || $v === 'ativo' || $v === '1' || $v === '1') ? 'Ativo' : 'Inativo';
                    }
                    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
                };

                foreach ($historico as $log):
                    $logArray = (array) $log;

                    $campoAlteradoRaw = (string)($logArray['campo_alterado'] ?? $logArray['acao'] ?? 'UPDATE');
                    $acaoLimpa        = strtoupper(trim($campoAlteradoRaw));
                    if ($acaoLimpa === 'EDITAR' || str_contains($acaoLimpa, 'UPDATE'))  $acaoLimpa = 'UPDATE';
                    if ($acaoLimpa === 'CRIAR'  || str_contains($acaoLimpa, 'CREATE'))  $acaoLimpa = 'CREATE';
                    if ($acaoLimpa === 'REMOVER' || str_contains($acaoLimpa, 'DELETE')) $acaoLimpa = 'DELETE';

                    $dataLog = $logArray['data_alteracao'] ?? $logArray['data_criacao'] ?? '';

                    $vAntigoRaw = $logArray['valor_antigo'] ?? null;
                    $vNovoRaw   = $logArray['valor_novo']   ?? null;

                    $jsonAntigo = is_string($vAntigoRaw) ? (json_decode($vAntigoRaw, true) ?? null) : null;
                    $jsonNovo   = is_string($vNovoRaw)   ? (json_decode($vNovoRaw, true)   ?? null) : null;

                    $antes = [];
                    $depois = [];

                    if (is_array($jsonAntigo)) {
                        $antes = isset($jsonAntigo['antes']) ? $jsonAntigo['antes'] : $jsonAntigo;
                    } elseif ($vAntigoRaw !== null) {
                        $campoChave = strtolower($campoAlteradoRaw);
                        if (array_key_exists($campoChave, $rotulosUsuarios)) {
                            $antes[$campoChave] = $vAntigoRaw;
                        } else {
                            $antes['nome'] = $vAntigoRaw;
                        }
                    }

                    if (is_array($jsonNovo)) {
                        $depois = isset($jsonNovo['depois']) ? $jsonNovo['depois'] : $jsonNovo;
                    } elseif ($vNovoRaw !== null) {
                        $campoChave = strtolower($campoAlteradoRaw);
                        if (array_key_exists($campoChave, $rotulosUsuarios)) {
                            $depois[$campoChave] = $vNovoRaw;
                        } else {
                            $depois['nome'] = $vNovoRaw;
                        }
                    }

                    $camposAlterados = [];
                    foreach ($rotulosUsuarios as $campo => $_) {
                        $vA = $antes[$campo] ?? null;
                        $vD = $depois[$campo] ?? null;

                        if ($vA === null && $vD === null) continue;

                        if ($campo === 'status') {
                            $normA = (str_replace(['1', 'true', 'ativo'], 'ativo', strtolower(trim((string)$vA))) === 'ativo') ? 'ativo' : 'inativo';
                            $normD = (str_replace(['1', 'true', 'ativo'], 'ativo', strtolower(trim((string)$vD))) === 'ativo') ? 'ativo' : 'inativo';
                            if ($normA !== $normD) {
                                $camposAlterados[] = $campo;
                            }
                            continue;
                        }

                        if (trim((string)$vA) !== trim((string)$vD)) {
                            $camposAlterados[] = $campo;
                        }
                    }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($acaoLimpa, ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td>
                            <?php
                            if ($dataLog !== '' && $dataLog !== '—') {
                                try {
                                    $date = new \DateTime($dataLog);
                                    echo htmlspecialchars($date->format('d/m/Y H:i:s'), ENT_QUOTES, 'UTF-8');
                                } catch (\Exception $e) {
                                    echo htmlspecialchars((string)$dataLog, ENT_QUOTES, 'UTF-8');
                                }
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($camposAlterados)): ?>
                                <div class="diff-container">
                                    <div class="diff-side">
                                        <h4>Antes</h4>
                                        <table>
                                            <?php foreach ($camposAlterados as $campo): ?>
                                                <tr>
                                                    <td style="width:70px;"><strong><?= $rotulosUsuarios[$campo] ?>:</strong></td>
                                                    <td class="text-before"><?= $formatar($campo, $antes[$campo] ?? null) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                    <div class="diff-side">
                                        <h4>Depois</h4>
                                        <table>
                                            <?php foreach ($camposAlterados as $campo): ?>
                                                <tr>
                                                    <td style="width:70px;"><strong><?= $rotulosUsuarios[$campo] ?>:</strong></td>
                                                    <td class="text-after"><?= $formatar($campo, $depois[$campo] ?? null) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($vAntigoRaw) || !empty($vNovoRaw)): ?>
                                    <div class="diff-container">
                                        <div class="diff-side">
                                            <h4>Antes</h4>
                                            <span class="text-before"><?= htmlspecialchars(is_string($vAntigoRaw) ? $vAntigoRaw : json_encode($vAntigoRaw), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="diff-side">
                                            <h4>Depois</h4>
                                            <span class="text-after"><?= htmlspecialchars(is_string($vNovoRaw) ? $vNovoRaw : json_encode($vNovoRaw), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>