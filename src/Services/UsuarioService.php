<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class UsuarioService
{
    private PDO $pdo;
    private HistoricoService $historicoService;

    public function __construct(?PDO $pdo = null, ?HistoricoService $historicoService = null)
    {
        $this->pdo = $pdo ?? \getPdo();
        $this->historicoService = $historicoService ?? new HistoricoService($this->pdo);
    }

    /** obter ID do administrador logado na sessão */
    private function getAdminId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return (int)($_SESSION['usuario_id'] ?? 1);
    }

    /** criar usuário */
    public function criar(string $nome, string $email, string $senha, string $status): int
    {
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            'INSERT INTO viacoes.usuarios (nome, email, senha, status, data_criacao) 
             VALUES (:nome, :email, :senha, :status, NOW())'
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senhaHash,
            'status' => $status? 1 : 0,
        ]);

        $novoId = (int)$this->pdo->lastInsertId();

        $dadosDepois = [
            'nome'   => $nome,
            'email'  => $email,
            'status' => $status
        ];

        $this->historicoService->criar(
            $this->getAdminId(),
            $novoId,
            'CREATE',
            ['depois' => $dadosDepois],
            'usuarios'
        );

        return $novoId;
    }

    public function listar(
        ?string $nome     = null,
        ?string $email    = null,
        ?string $status   = null,
        ?string $excluido = null,
        int $pagina       = 1,
        int $porPagina    = 10
    ): array {
        $where  = [];
        $params = [];

        if ($nome !== null && $nome !== '') {
            $where[]        = 'nome LIKE :nome';
            $params['nome'] = '%' . $nome . '%';
        }
        if ($email !== null && $email !== '') {
            $where[]         = 'email LIKE :email';
            $params['email'] = '%' . $email . '%';
        }
        if ($status !== null && $status !== '') {
            $where[]          = 'status = :status';
            $params['status'] = (int) $status;
        }
        if ($excluido !== null && $excluido !== '') {
            $where[]          = 'data_exclusao IS NOT NULL';
        } else {
            $where[]          = 'data_exclusao IS NULL';
        }

        $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $offset = ($pagina - 1) * $porPagina;

        $sql = "SELECT * FROM viacoes.usuarios {$whereClause} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return array_map(
            fn(array $row) => \App\Models\Usuario::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Conta a quantidade total de usuários baseado nos mesmos filtros da listagem.
     */
    public function contarTotal(
        ?string $nome     = null,
        ?string $email    = null,
        ?string $status   = null,
        ?string $excluido = null
    ): int {
        $where  = [];
        $params = [];

        if ($nome !== null && $nome !== '') {
            $where[]        = 'nome LIKE :nome';
            $params['nome'] = '%' . $nome . '%';
        }
        if ($email !== null && $email !== '') {
            $where[]         = 'email LIKE :email';
            $params['email'] = '%' . $email . '%';
        }
        if ($status !== null && $status !== '') {
            $where[]          = 'status = :status';
            $params['status'] = (int) $status;
        }
        if ($excluido !== null && $excluido !== '') {
            $where[]          = 'data_exclusao IS NOT NULL';
        } else {
            $where[]          = 'data_exclusao IS NULL';
        }

        $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM viacoes.usuarios {$whereClause}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** buscar um único Usuário pelo ID */
    public function find(int $id): ?\App\Models\Usuario
    {
        $stmt = $this->pdo->prepare('
        SELECT id, nome, email, senha, status, data_criacao, data_exclusao 
        FROM viacoes.usuarios 
        WHERE id = :id
    ');

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return \App\Models\Usuario::fromRow($row);
    }


    public function update(
        int $id,
        string $nome,
        string $email,
        int $status,
        ?string $novaSenha = null,
        ?string $dataExclusao = null
    ): bool {
        $stmtOld = $this->pdo->prepare("SELECT nome, email, status, data_exclusao FROM viacoes.usuarios WHERE id = :id");
        $stmtOld->execute(['id' => $id]);
        $usuarioAntigo = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if ($novaSenha !== null && trim($novaSenha) !== '') {
            $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("
                UPDATE viacoes.usuarios 
                SET nome = :nome, 
                    email = :email, 
                    status = :status, 
                    senha = :senha,
                    data_exclusao = :data_exclusao 
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':senha', $senhaHash, PDO::PARAM_STR);
            $stmt->bindValue(':data_exclusao', $dataExclusao, $dataExclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->bindValue(':status', (int) $status, PDO::PARAM_INT);

        } else {
            $stmt = $this->pdo->prepare("
                UPDATE viacoes.usuarios 
                SET nome = :nome, 
                    email = :email, 
                    status = :status,
                    data_exclusao = :data_exclusao
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':data_exclusao', $dataExclusao, $dataExclusao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->bindValue(':status', (int) $status, PDO::PARAM_INT);
        }

        $sucesso = $stmt->execute();

        if ($sucesso && $usuarioAntigo) {
            $statusAntesString = 'inativo';
            if ((int)$usuarioAntigo['status'] === 1) {
                $statusAntesString = 'ativo';
            } elseif (!empty($usuarioAntigo['data_exclusao'])) {
                $statusAntesString = 'deletado';
            }

            $statusDepoisString = 'inativo';
            if ($status === 1) {
                $statusDepoisString = 'ativo';
            } elseif ($dataExclusao !== null) {
                $statusDepoisString = 'deletado';
            }

            $dadosAntes = [
                'nome'          => $usuarioAntigo['nome'],
                'email'         => $usuarioAntigo['email'],
                'status'        => $statusAntesString,
                'data_exclusao' => $usuarioAntigo['data_exclusao']
            ];

            $dadosDepois = [
                'nome'          => $nome,
                'email'         => $email,
                'status'        => $statusDepoisString,
                'data_exclusao' => $dataExclusao
            ];

            $this->historicoService->criar(
                $this->getAdminId(),
                $id,
                'UPDATE',
                ['antes' => $dadosAntes, 'depois' => $dadosDepois],
                'usuarios'
            );
        }

        return $sucesso;
    }

    /** soft delete nos registros */
    public function delete(int $id): void
    {
        // Captura dados antes de remover
        $stmtOld = $this->pdo->prepare("SELECT nome, email, status FROM viacoes.usuarios WHERE id = :id");
        $stmtOld->execute(['id' => $id]);
        $usuarioAntigo = $stmtOld->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("UPDATE viacoes.usuarios SET data_exclusao = now() WHERE id = :id");
        $stmt = $stmt->execute(['id' => $id]);

        if ($usuarioAntigo) {
            $dadosAntes = [
                'nome'   => $usuarioAntigo['nome'],
                'email'  => $usuarioAntigo['email'],
                'status' => $usuarioAntigo['status']
            ];

            $this->historicoService->criar(
                $this->getAdminId(),
                $id,
                'DELETE',
                ['antes' => $dadosAntes],
                'usuarios'
            );
        }
    }

    /** poder restaurar registros marcados como deletados */
    public function restore(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE viacoes.usuarios SET data_exclusao = NULL WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $stmtNew = $this->pdo->prepare("SELECT nome, email, senha, status FROM viacoes.usuarios WHERE id = :id");
        $stmtNew->execute(['id' => $id]);
    }

}