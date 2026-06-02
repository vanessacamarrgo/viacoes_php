<?php
//Renderiza a página inicial pública com as viações ativas.
// Tem tratamento de erro  — se o banco falhar, a página continua funcionando (sem viações em vez de quebrar).
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\ViacaoService;
use PDOException;

/** Controla o fluxo HTTP de marcas e delega persistencia ao ViacaoService. */
final class HomeController
{
    private ViacaoService $viacoes;

    public function __construct(?ViacaoService $viacoes = null)
    {
        $this->viacoes = $viacoes ?? new ViacaoService();
    }

    public function index(): void
    {
        try {
            $viacoesAtivas = $this->viacoes->ativas();

            View::render('home/index', [
                'viacoesAtivas' => $viacoesAtivas,
                'erroConexao'   => false,

            ]);
            return;  // Early return — sucesso

        } catch (PDOException $e) {
            // Se o banco falhar, renderiza a home sem viações
            // (graceful degradation — a página não quebra)
            View::render('home/index', [
                'viacoesAtivas' => [],
                'erroConexao'   => true,
            ]);
        }
    }
}
