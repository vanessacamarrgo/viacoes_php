<link rel="stylesheet" href="/css/layout.css">

<style>
    .diff-container {
        display: flex;
        gap: 15px;
        font-size: 13px;
        font-family: monospace;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #e3e6f0;
        color: #333;
    }
    .diff-side {
        flex: 1;
    }
    .diff-side h4 {
        margin: 0 0 5px 0;
        font-size: 11px;
        text-transform: uppercase;
        color: #777;
        border-bottom: 1px dashed #ddd;
        padding-bottom: 2px;
    }
    .diff-side table {
        width: 100%;
        border-collapse: collapse;
        background: transparent !important;
        margin: 0 !important;
    }
    .diff-side table td {
        padding: 3px 0 !important;
        border: none !important;
        background: transparent !important;
        color: #333 !important;
    }
    .text-before { color: #c0392b; font-weight: bold; }
    .text-after { color: #27ae60; font-weight: bold; }
</style>

<main class="container">
    <header class="page-header">
        <div>
            <h1 style="color: white">Detalhes da Viação #<?= (int)$viacao->id ?></h1>
            <nav class="actions">
                <a class="btn btn-primary" href="/viacoes">Voltar para Lista</a>
                <a class="btn btn-primary" href="/viacoes/<?= (int)$viacao->id ?>/edit">Editar Viação</a>
            </nav>
        </div>
    </header>

    <div class="card" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 25px; align-items: center; color: #333;">
        <?php if (!empty($viacao->logo)): ?>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e3e6f0; display: flex; align-items: center; justify-content: center; width: 160px; height: 80px;">
                <img src="/uploads/<?= htmlspecialchars($viacao->logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        <?php endif; ?>
        <div>
            <p style="margin-bottom: 8px;"><strong>Nome da Marca / Empresa:</strong> <?= htmlspecialchars($viacao->nome, ENT_QUOTES, 'UTF-8') ?></p>
            <p style="margin-bottom: 8px;"><strong>Cidade:</strong> <?= htmlspecialchars($viacao->cidade, ENT_QUOTES, 'UTF-8') ?></p>
            <p style="margin-bottom: 8px;"><strong>Site Oficial:</strong> <a href="<?= htmlspecialchars($viacao->url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color: #0056b3; font-weight: bold;"><?= htmlspecialchars($viacao->url, ENT_QUOTES, 'UTF-8') ?></a></p>
            <p style="margin-bottom: 0;"><strong>Status:</strong>
                <?php if (!empty($viacao->data_exclusao)): ?>
                    <span class="badge" style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 4px;">Deletado</span>
                    <span style="font-size: 13px; color: #666;">(Excluído em: <?= htmlspecialchars($viacao->data_exclusao, ENT_QUOTES, 'UTF-8') ?>)</span>
                <?php elseif ($viacao->status == 1 || $viacao->status === true): ?>
                    <span class="badge" style="background: #28a745; color: white; padding: 2px 8px; border-radius: 4px;">Ativo</span>
                <?php else: ?>
                    <span class="badge" style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 4px;">Inativo</span>
                <?php endif; ?>
            </p>
            <p style="margin-bottom: 0;"><strong>Cadastrado em:</strong> <?= htmlspecialchars($viacao->data_criacao ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <div class="table-card">
        <h2 style="color: white; margin-bottom: 15px;">Histórico de Alterações da Viação</h2>
        <?php if (empty($historico)): ?>
            <p style="color: white; padding: 15px 0;">Nenhuma alteração registrada para esta viação no log de auditoria.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Ação</th>
                    <th>Data da Alteração</th>
                    <th>Dados Detalhados</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $rotulosViacoes = [
                    'nome'   => 'Nome',
                    'url'    => 'Site',
                    'cidade' => 'Cidade',
                    'status' => 'Status',
                    'logo'   => 'Logo',
                ];

                $formatarViacao = static function (string $campo, mixed $valor): string {
                    if ($valor === null || $valor === '') return '—';
                    if ($campo === 'status') {
                        $v = strtolower(trim((string)$valor));
                        return ($v === 'true' || $v === 'ativo' || $v === '1') ? 'Ativo' : 'Inativo';
                    }
                    if ($campo === 'logo') {
                        return '✔ ' . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
                    }
                    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
                };

                foreach ($historico as $log):
                    $logArray = (array) $log;

                    $campoAlteradoRaw = (string)($logArray['campo_alterado'] ?? $logArray['acao'] ?? 'UPDATE');
                    $acaoLimpa        = strtoupper(trim($campoAlteradoRaw));
                    if ($acaoLimpa === 'EDITAR')  $acaoLimpa = 'UPDATE';
                    if ($acaoLimpa === 'CRIAR')   $acaoLimpa = 'CREATE';
                    if ($acaoLimpa === 'REMOVER') $acaoLimpa = 'DELETE';

                    $dataLog = $logArray['data_alteracao'] ?? $logArray['data_criacao'] ?? '';

                    $vAntigoRaw = $logArray['valor_antigo'] ?? null;
                    $vNovoRaw   = $logArray['valor_novo']   ?? $logArray['dados'] ?? null;

                    $jsonAntigo = is_string($vAntigoRaw) ? (json_decode($vAntigoRaw, true) ?? []) : (is_array($vAntigoRaw) ? $vAntigoRaw : []);
                    $jsonNovo   = is_string($vNovoRaw)   ? (json_decode($vNovoRaw, true)   ?? []) : (is_array($vNovoRaw)   ? $vNovoRaw   : []);

                    $antes  = !empty($jsonNovo['antes'])  ? $jsonNovo['antes']  : (!empty($jsonAntigo['antes']) ? $jsonAntigo['antes'] : null);
                    $depois = !empty($jsonNovo['depois']) ? $jsonNovo['depois'] : (!empty($jsonAntigo['depois']) ? $jsonAntigo['depois'] : null);

                    if ($antes === null && $depois === null) {
                        if (isset($jsonNovo['status']) || isset($jsonNovo['nome']) || isset($jsonNovo['url']) || isset($jsonNovo['cidade'])) { $depois = $jsonNovo; }
                        if (isset($jsonAntigo['status']) || isset($jsonAntigo['nome']) || isset($jsonAntigo['url']) || isset($jsonAntigo['cidade'])) { $antes = $jsonAntigo; }
                    }

                    $camposAlterados = [];
                    if ($antes !== null || $depois !== null) {
                        foreach ($rotulosViacoes as $campo => $_) {
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
                    }

                    $campoDireto = (string)($logArray['campo_alterado'] ?? '');
                    $exibirDireto = ($antes === null && $depois === null && $campoDireto !== '' && isset($logArray['valor_antigo']));
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
                            <?php if ($acaoLimpa === 'CREATE' && $depois !== null): ?>
                                <div class="diff-container">
                                    <div class="diff-side">
                                        <h4>Dados Iniciais</h4>
                                        <table>
                                            <?php foreach ($rotulosViacoes as $campo => $rotulo): ?>
                                                <tr><td style="width:70px;"><strong><?= $rotulo ?>:</strong></td><td><?= $formatarViacao($campo, $depois[$campo] ?? null) ?></td></tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                </div>
                            <?php elseif ($acaoLimpa === 'DELETE' && $antes !== null): ?>
                                <div class="diff-container">
                                    <div class="diff-side">
                                        <h4>Dados Removidos</h4>
                                        <table>
                                            <?php foreach ($rotulosViacoes as $campo => $rotulo): ?>
                                                <tr><td style="width:70px;"><strong><?= $rotulo ?>:</strong></td><td><?= $formatarViacao($campo, $antes[$campo] ?? null) ?></td></tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                </div>
                            <?php elseif (!empty($camposAlterados)): ?>
                                <div class="diff-container">
                                    <div class="diff-side">
                                        <h4>Antes</h4>
                                        <table>
                                            <?php foreach ($camposAlterados as $campo): ?>
                                                <tr><td style="width:70px;"><strong><?= $rotulosViacoes[$campo] ?>:</strong></td><td class="text-before"><?= $formatarViacao($campo, $antes[$campo] ?? null) ?></td></tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                    <div class="diff-side">
                                        <h4>Depois</h4>
                                        <table>
                                            <?php foreach ($camposAlterados as $campo): ?>
                                                <tr><td style="width:70px;"><strong><?= $rotulosViacoes[$campo] ?>:</strong></td><td class="text-after"><?= $formatarViacao($campo, $depois[$campo] ?? null) ?></td></tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                </div>
                            <?php elseif ($exibirDireto): ?>
                                <?php
                                $rotuloExibicao = $rotulosViacoes[strtolower($campoDireto)] ?? $campoDireto;
                                ?>
                                <div class="diff-container">
                                    <div class="diff-side">
                                        <h4>Antes</h4>
                                        <p style="margin:0;"><strong><?= htmlspecialchars($rotuloExibicao, ENT_QUOTES, 'UTF-8') ?>:</strong> <span class="text-before"><?= $formatarViacao(strtolower($campoDireto), $logArray['valor_antigo']) ?></span></p>
                                    </div>
                                    <div class="diff-side">
                                        <h4>Depois</h4>
                                        <p style="margin:0;"><strong><?= htmlspecialchars($rotuloExibicao, ENT_QUOTES, 'UTF-8') ?>:</strong> <span class="text-after"><?= $formatarViacao(strtolower($campoDireto), $logArray['valor_novo']) ?></span></p>
                                    </div>
                                </div>
                            <?php else: ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>