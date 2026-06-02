<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Usuario;
use App\Services\UsuarioService;
use App\Services\HistoricoService;


final class UsuarioController
{
    private UsuarioService $usuarioService;
    private HistoricoService $historicoService;


    public function __construct(?UsuarioService $usuarioService = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->verificarLogin();
        $this->usuarioService = $usuarioService ?? new UsuarioService();
        $this->historicoService = new HistoricoService();
    }

    private function verificarLogin(): void
    {
        if (empty($_SESSION['user_id']) && empty($_SESSION['usuario_id'])) {
            View::redirect('/login');
            exit;
        }
    }


    public function show(int $id): void
    {
        $usuario = $this->usuarioService->find($id);

        if ($usuario === null) {
            http_response_code(404);
            echo 'Usuário não encontrado.';
            return;
        }

        $historico = $this->historicoService->getHistory([
            'tab'  => 'usuarios',
            'alvo' => (string) $id
        ]);

        View::render('usuarios/show', [
            'title'     => 'Detalhes do Usuário - ' . $usuario->nome,
            'usuario'   => $usuario,
            'historico' => $historico
        ]);
    }

    private function getAdminId(): int
    {
        return (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 1);
    }



    public function index(): void
    {
        $nome     = trim((string) ($_GET['nome']   ?? ''));
        $email    = trim((string) ($_GET['email']  ?? ''));
        $status   = $_GET['status'] ?? '';
        $excluido = $_GET['excluido'] ?? '';

        $paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina   = 3;

        $usuarios = $this->usuarioService->listar(
            nome:     $nome     !== '' ? $nome     : null,
            email:    $email    !== '' ? $email    : null,
            status:   $status   !== '' ? $status   : null,
            excluido: $excluido !== '' ? $excluido : null,
            pagina:   $paginaAtual,
            porPagina: $porPagina
        );

        $totalUsuarios = $this->usuarioService->contarTotal(
            nome:     $nome     !== '' ? $nome     : null,
            email:    $email    !== '' ? $email    : null,
            status:   $status   !== '' ? $status   : null,
            excluido: $excluido !== '' ? $excluido : null
        );

        $totalPaginas = (int) ceil($totalUsuarios / $porPagina);
        $temFiltro    = $nome !== '' || $email !== '' || $status !== '' || $excluido !== '';

        View::render('usuarios/index', [
            'title'        => 'Usuários',
            'usuarios'     => $usuarios,
            'temFiltro'    => $temFiltro,
            'filtros'      => compact('nome', 'email', 'status', 'excluido'),
            'paginaAtual'  => $paginaAtual,
            'totalPaginas' => $totalPaginas
        ]);
    }
    public function create(): void
    {
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/usuarios/create.php';
    }

    public function store(): void
    {
        $nome   = trim((string)($_POST['nome'] ?? ''));
        $email  = trim((string)($_POST['email'] ?? ''));
        $senha  = $_POST['senha'] ?? '';
        $status = $_POST['status'] ?? 'ativo';

        if ($nome === '' || $email === '' || $senha === '') {
            $errors = ['Preencha todos os campos obrigatórios.'];
            $old = ['nome' => $nome, 'email' => $email, 'status' => $status];
            require __DIR__ . '/../views/usuarios/create.php';
            return;
        }

        $this->usuarioService->criar($nome, $email, $senha, $status);

        $usuariosEncontrados = $this->usuarioService->listar(email: $email);
        $usuarioCriado = !empty($usuariosEncontrados) ? $usuariosEncontrados[0] : null;

        if ($usuarioCriado !== null) {
            $this->historicoService->criar(
                usuarioId:    $this->getAdminId(),
                entidadeId:   (int)$usuarioCriado->id,
                acao:         'CREATE',
                dados: [
                    'antes'  => null,
                    'depois' => $this->snapshot($usuarioCriado),
                ],
                entidadeTipo: 'usuarios'
            );
        }

        header('Location: /usuarios');
        exit;
    }

    public function edit(int $id): void
    {
        $usuario = $this->usuarioService->find($id);

        if (!$usuario) {
            header('Location: /usuarios');
            exit;
        }

        $errors = [];

        require __DIR__ . '/../views/usuarios/edit.php';
    }

    public function update(int $id): void
    {
        $usuarioEditar = $this->usuarioService->find($id);

        if (!$usuarioEditar) {
            header('Location: /usuarios');
            exit;
        }

        $snapshotAntes = $this->snapshot($usuarioEditar);

        $nome  = trim((string)($_POST['nome'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';

        $statusForm = $_POST['status'] ?? '1';
        $status = ($statusForm === '1' || $statusForm === 'ativo') ? 1 : 0;

        $dataExclusao = null;
        if ($statusForm === 'deletado') {
            $dataExclusao = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');
        }

        if ($nome === '' || $email === '') {
            $errors  = ['Nome e E-mail não podem ficar vazios.'];
            $old = ['nome' => $nome, 'email' => $email, 'status' => $statusForm];
            require __DIR__ . '/../views/usuarios/edit.php';
            return;
        }

        $this->usuarioService->update(
            id: $id,
            nome: $nome,
            email: $email,
            status: $status,
            novaSenha: ($senha !== '' ? $senha : null),
            dataExclusao: $dataExclusao
        );

        $usuarioAtualizado = $this->usuarioService->find($id);

        $this->historicoService->criar(
            usuarioId:    $this->getAdminId(),
            entidadeId:   $id,
            acao:         'UPDATE',
            dados: [
                'antes'  => $snapshotAntes,
                'depois' => $usuarioAtualizado !== null ? $this->snapshot($usuarioAtualizado) : null,
            ],
            entidadeTipo: 'usuarios'
        );

        header('Location: /usuarios');
        exit;
    }
    public function destroy(int $id): void
    {
        $this->usuarioService->delete($id);

        header('Location: /usuarios');
        exit;
    }

    private function snapshot(\App\Models\Usuario $usuario): array
    {
        // Se houver data de exclusão, o status textual vira 'deletado'
        if (!empty($usuario->data_exclusao)) {
            $statusString = 'deletado';
        } else {
            $statusString = ($usuario->status == 1 || $usuario->status === true || $usuario->status === 'ativo') ? 'ativo' : 'inativo';
        }

        return [
            'id'            => $usuario->id,
            'nome'          => $usuario->nome,
            'email'         => $usuario->email,
            'status'        => $statusString, // Agora vai salvo como texto explicativo no histórico
            'data_criacao'  => $usuario->data_criacao,
            'data_exclusao' => $usuario->data_exclusao,
        ];
    }

    public function restore(int $id): void
    {
        $usuario = $this->usuarioService->find($id);

        if ($usuario === null) {
            http_response_code(404);
            echo 'Usuário não encontrado.';
            return;
        }

        $this->historicoService->criar(
            usuarioId:    $this->getAdminId(),
            entidadeId:   $id,
            acao:         'RESTORE',
            dados: [
                'antes'  => null,
                'depois' => $this->snapshot($usuario)
            ],
            entidadeTipo: 'usuarios'
        );

        $this->usuarioService->restore($id);

        View::flash('success', 'Usuário restaurado com sucesso.');
        View::redirect('/usuarios');
    }
}