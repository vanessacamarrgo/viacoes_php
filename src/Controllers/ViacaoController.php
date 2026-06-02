<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Viacao;
use App\Services\ViacaoService;
use App\Services\HistoricoService;

/** Controla o fluxo HTTP de viações e delega persistência ao Service. */
final class ViacaoController
{
    private ViacaoService   $viacaoService;
    private HistoricoService $historicoService;

    public function __construct(?ViacaoService $ViacaoService = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->verificarLogin();
        $this->viacaoService    = $ViacaoService ?? new ViacaoService();
        $this->historicoService = new HistoricoService();
    }

    /** action: Visualizar os detalhes de uma única viação */
    public function show(int $id): void
    {
        $viacao = $this->viacaoService->find($id);

        if ($viacao === null) {
            http_response_code(404);
            echo 'Viação não encontrada.';
            return;
        }

        // Busca apenas as alterações DESTA viação
        $historico = $this->historicoService->getHistory([
            'tab'  => 'viacoes',
            'alvo' => (string) $id
        ]);

        View::render('viacoes/show', [
            'title'     => 'Visualizar Viação - ' . $viacao->nome,
            'viacao'    => $viacao,
            'historico' => $historico
        ]);
    }

    /** Bloqueia acesso de usuários não autenticados. */
    private function verificarLogin(): void
    {
        if (empty($_SESSION['user_id']) && empty($_SESSION['usuario_id'])) {
            View::redirect('/login');
            exit;
        }
    }

    /** Recupera o ID do Administrador logado na sessão */
    private function getAdminId(): int
    {
        return (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 1);
    }

    /** Retorna o status tratado puramente como string, levando em conta a data de exclusão */
    private function snapshot(Viacao $v): array
    {
        if (!empty($v->data_exclusao)) {
            $statusString = 'deletado';
        } else {
            $statusString = ($v->status === true || $v->status === 1 || $v->status === 'ativo') ? 'ativo' : 'inativo';
        }

        return [
            'nome'          => $v->nome,
            'url'           => $v->url,
            'cidade'        => $v->cidade,
            'status'        => $statusString,
            'logo'          => $v->logo,
            'data_exclusao' => $v->data_exclusao
        ];
    }

    /** Lista viações e renderiza a tela principal. */
    public function index(): void
    {
        $nome     = trim((string) ($_GET['nome']   ?? ''));
        $cidade   = trim((string) ($_GET['cidade'] ?? ''));
        $url      = trim((string) ($_GET['url']    ?? ''));
        $status   = $_GET['status'] ?? '';
        $excluido = $_GET['excluido'] ?? '';

        // configuração da paginação
        $paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina   = 3;

        $viacoes = $this->viacaoService->listar(
            nome:     $nome     !== '' ? $nome     : null,
            cidade:   $cidade   !== '' ? $cidade   : null,
            url:      $url      !== '' ? $url      : null,
            status:   $status   !== '' ? $status   : null,
            excluido: $excluido !== '' ? $excluido : null,
            pagina:   $paginaAtual,
            porPagina: $porPagina
        );

        $totalViacoes = $this->viacaoService->contarTotal(
            nome:     $nome     !== '' ? $nome     : null,
            cidade:   $cidade   !== '' ? $cidade   : null,
            url:      $url      !== '' ? $url      : null,
            status:   $status   !== '' ? $status   : null,
            excluido: $excluido !== '' ? $excluido : null
        );

        $totalPaginas = (int) ceil($totalViacoes / $porPagina);
        $temFiltro    = $nome !== '' || $cidade !== '' || $url !== '' || $status !== '' || $excluido !== '';

        View::render('viacoes/index', [
            'title'        => 'Viações',
            'viacoes'      => $viacoes,
            'temFiltro'    => $temFiltro,
            'filtros'      => compact('nome', 'cidade', 'url', 'status', 'excluido'),
            'paginaAtual'  => $paginaAtual,
            'totalPaginas' => $totalPaginas
        ]);
    }

    /** Exibe o histórico de todas as alterações. */
    public function historico(): void
    {
        $filtroUsuario = isset($_GET['usuario']) ? trim((string) $_GET['usuario']) : '';
        $filtroViacao  = isset($_GET['viacao'])  ? trim((string) $_GET['viacao'])  : '';
        $filtroAcao    = isset($_GET['acao'])    ? trim((string) $_GET['acao'])    : '';
        $tab           = isset($_GET['tab'])     ? trim((string) $_GET['tab'])     : 'viacoes'; // Define a aba ativa

        // configuração da paginação
        $paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina   = 10; // Exibe 10 logs por página

        // captura os dados da página atual
        $historico = $this->historicoService->getHistory([
            'tab'       => $tab,
            'usuario'   => $filtroUsuario,
            'alvo'      => $filtroViacao,
            'acao'      => $filtroAcao,
            'pagina'    => $paginaAtual,
            'porPagina' => $porPagina
        ]);

        // conta o total geral de registros de histórico para os filtros ativos
        $totalLogs = $this->historicoService->contarTotal([
            'tab'     => $tab,
            'usuario' => $filtroUsuario,
            'alvo'    => $filtroViacao,
            'acao'    => $filtroAcao
        ]);

        $totalPaginas = (int) ceil($totalLogs / $porPagina);

        View::render('viacoes/historico', [
            'title'         => 'Histórico de viações',
            'historico'     => $historico,
            'filtroUsuario' => $filtroUsuario,
            'filtroViacao'  => $filtroViacao,
            'filtroAcao'    => $filtroAcao,
            'tab'           => $tab,
            'filtros'       => ['usuario' => $filtroUsuario, 'viacao' => $filtroViacao, 'acao' => $filtroAcao, 'tab' => $tab],
            'paginaAtual'   => $paginaAtual,
            'totalPaginas'  => $totalPaginas
        ]);
    }

    /** Exibe o formulário de criação. */
    public function create(): void
    {
        View::render('viacoes/create', [
            'title'  => 'Criar viação',
            'errors' => [],
            'old'    => ['nome' => '', 'url' => '', 'cidade' => '', 'logo' => '', 'status' => 'ativo'],
        ]);
    }

    /** Processa o POST de criação. */
    public function store(): void
    {
        $nomeViacao = trim((string) ($_POST['nome']   ?? ''));
        $url        = trim((string) ($_POST['url']    ?? ''));
        $cidade     = trim((string) ($_POST['cidade'] ?? ''));

        $status = (isset($_POST['status']) && ($_POST['status'] === '1' || $_POST['status'] === 'ativo')) ? 'ativo' : 'inativo';

        $errors = $this->validateName($nomeViacao);

        if ($errors !== []) {
            View::render('viacoes/create', [
                'title'  => 'Criar viação',
                'errors' => $errors,
                'old'    => ['nome' => $nomeViacao, 'url' => $url, 'cidade' => $cidade, 'logo' => '', 'status' => $status],
            ]);
            return;
        }

        $file = (!empty($_FILES['logo']['name'])) ? $_FILES['logo'] : null;

        $id = $this->viacaoService->create($nomeViacao, $url, $cidade, $status, $file);
        $viaCriada = $this->viacaoService->find($id);

        $this->historicoService->criar(
            usuarioId:    $this->getAdminId(),
            entidadeId:   $id,
            acao:         'CREATE',
            dados: [
                'antes'  => null,
                'depois' => $viaCriada !== null ? $this->snapshot($viaCriada) : null,
            ],
            entidadeTipo: 'viacoes'
        );

        View::flash('success', 'Viação criada com sucesso (#' . $id . ').');
        View::redirect('/viacoes');
    }

    /** Exibe o formulário de edição. */
    public function edit(int $id): void
    {
        $viacao = $this->viacaoService->find($id);

        if ($viacao === null) {
            http_response_code(404);
            echo 'Viação não encontrada.';
            return;
        }

        View::render('viacoes/edit', [
            'title'  => 'Editar viação',
            'viacao' => $viacao,
            'errors' => [],
            'old'    => [
                'nome'   => $viacao->nome,
                'url'    => $viacao->url,
                'cidade' => $viacao->cidade,
                'logo'   => $viacao->logo ?? '',
                'status' => $viacao->status,
            ],
        ]);
    }

    /** Processa o PUT de atualização gerindo os status numéricos e a data de exclusão */
    public function update(int $id): void
    {
        $viacaoEditar = $this->viacaoService->find($id);

        if ($viacaoEditar === null) {
            http_response_code(404);
            echo 'Viação não encontrada.';
            return;
        }

        $snapshotAntes = $this->snapshot($viacaoEditar);

        $nomeViacao = trim((string) ($_POST['nome']   ?? ''));
        $url        = trim((string) ($_POST['url']    ?? ''));
        $cidade     = trim((string) ($_POST['cidade'] ?? ''));

        $statusForm = $_POST['status'] ?? '1';

        $status = ($statusForm === '1' || $statusForm === 'ativo') ? 1 : 0;

        $dataExclusao = null;
        if ($statusForm === 'deletado') {
            $dataExclusao = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');
        }

        $errors = $this->validateName($nomeViacao);

        if ($errors !== []) {
            View::render('viacoes/edit', [
                'title'  => 'Editar viação',
                'viacao' => $viacaoEditar,
                'errors' => $errors,
                'old'    => [
                    'nome'   => $nomeViacao,
                    'logo'   => $viacaoEditar->logo ?? '',
                    'status' => $statusForm,
                    'url'    => $url,
                    'cidade' => $cidade,
                ],
            ]);
            return;
        }

        $file = (!empty($_FILES['logo']['name'])) ? $_FILES['logo'] : null;

        $this->viacaoService->update($id, $nomeViacao, $cidade, $status, $url, $file, $dataExclusao);

        $viacaoAtualizada = $this->viacaoService->find($id);

        $this->historicoService->criar(
            usuarioId:    $this->getAdminId(),
            entidadeId:   $id,
            acao:         'UPDATE',
            dados: [
                'antes'  => $snapshotAntes,
                'depois' => $viacaoAtualizada !== null ? $this->snapshot($viacaoAtualizada) : null,
            ],
            entidadeTipo: 'viacoes'
        );

        View::flash('success', 'Viação actualizada com sucesso.');
        View::redirect('/viacoes');
    }

    /** @return list<string> */
    private function validateName(string $nomeViacao): array
    {
        $errors = [];
        if ($nomeViacao === '') {
            $errors[] = 'O nome da viação é obrigatório.';
        }
        if (strlen($nomeViacao) > 255) {
            $errors[] = 'O nome da viação deve ter no máximo 255 caracteres.';
        }
        return $errors;
    }


    public function restore(int $id): void
    {
        $viacao = $this->viacaoService->find($id);

        if ($viacao === null) {
            http_response_code(404);
            echo 'Viação não encontrada.';
            return;
        }

        // 1. Grava no histórico a ação de RESTORE apontando quem fez (nicolas)
        // Passamos o estado atual no 'depois' para registrar como ela voltou a ficar ativa
        $this->historicoService->criar(
            usuarioId:    $this->getAdminId(),
            entidadeId:   $id,
            acao:         'RESTORE',
            dados: [
                'antes'  => null,
                'depois' => $this->snapshot($viacao)
            ],
            entidadeTipo: 'viacoes'
        );

        // 2. Executa a restauração no Service (limpando a data_exclusao e reativando o status)
        $this->viacaoService->restore($id);

        // 3. Define a mensagem de sucesso e redireciona
        View::flash('success', 'Viação restaurada com sucesso.');
        View::redirect('/viacoes');
    }

    /**
     * Executa a exclusão lógica de uma viação e grava a auditoria.
     */
    public function destroy(int $id): void
    {
        $viacao = $this->viacaoService->find($id);

        if ($viacao === null) {
            http_response_code(404);
            echo 'Viação não encontrada.';
            return;
        }

        $snapshotAntes = $this->snapshot($viacao);

        $dataExclusao = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');


        $this->viacaoService->update(
            id: $id,
            nome: $viacao->nome,
            cidade: $viacao->cidade,
            status: 0,
            url: $viacao->url,
            file: null,
            dataExclusao: $dataExclusao
        );

        $viacaoAtualizada = $this->viacaoService->find($id);

        $this->historicoService->criar(
            usuarioId:    $this->getAdminId(),
            entidadeId:   $id,
            acao:         'DELETE',
            dados: [
                'antes'  => $snapshotAntes,
                'depois' => $viacaoAtualizada !== null ? $this->snapshot($viacaoAtualizada) : null,
            ],
            entidadeTipo: 'viacoes'
        );

        View::flash('success', 'Viação excluída com sucesso.');
        View::redirect('/viacoes');
    }
}