<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HistoricoService;

final class HistoricoController
{
    private HistoricoService $service;

    public function __construct(?HistoricoService $service = null)
    {
        $this->service = $service ?? new HistoricoService();
    }

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tabAtual = $_GET['tab'] ?? '';

        if (!in_array($tabAtual, ['viacoes', 'usuarios', ''], true)) {
            $tabAtual = '';
        }

        // normaliza os filtros vindos dos campos de input da barra de pesquisa
        $filtroUsuario = trim((string)($_GET['usuario'] ?? $_GET['alterado_por'] ?? ''));
        $filtroAlvo    = trim((string)($_GET['alvo'] ?? $_GET['item_afetado'] ?? ''));


        $paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina   = 10;

        $filters = [
            'tab'       => $tabAtual,
            'usuario'   => $filtroUsuario,
            'alvo'      => $filtroAlvo,
            'pagina'    => $paginaAtual,
            'porPagina' => $porPagina
        ];

        $historico = $this->service->getHistory($filters);

        $totalLogs = $this->service->contarTotal([
            'tab'     => $tabAtual,
            'usuario' => $filtroUsuario,
            'alvo'    => $filtroAlvo
        ]);

        $totalPaginas = (int) ceil($totalLogs / $porPagina);

        $filtros = [
            'tab'     => $tabAtual,
            'usuario' => $filtroUsuario,
            'alvo'    => $filtroAlvo
        ];

        $title = "Histórico Geral";

        require __DIR__ . '/../views/historico/index.php';
    }
}