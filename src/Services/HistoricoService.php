<?php

//Grava e recupera o histórico de quem fez o quê e quando nas viações e usuários.
declare(strict_types=1);

namespace App\Services;

use App\Models\Historico;
use PDO;

final class HistoricoService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \getPdo();
    }

    /**
     * Insere um registro de auditoria distribuindo os dados nas colunas corretas.
     */
    public function criar(
        int    $usuarioId,
        int    $entidadeId,
        string $acao,
        array  $dados,
        string $entidadeTipo = 'viacoes'
    ): void
    {
        $valorAntigo = null;
        $valorNovo = null;

        $acaoLimpa = strtolower($acao);

        if ($acaoLimpa === 'editar' || $acaoLimpa === 'update') {
            $valorAntigo = isset($dados['antes']) ? json_encode($dados['antes'], JSON_UNESCAPED_UNICODE) : null;
            $valorNovo = isset($dados['depois']) ? json_encode($dados['depois'], JSON_UNESCAPED_UNICODE) : null;
        } elseif ($acaoLimpa === 'criar' || $acaoLimpa === 'create') {
            $valorNovo = isset($dados['depois']) ? json_encode($dados['depois'], JSON_UNESCAPED_UNICODE) : json_encode($dados, JSON_UNESCAPED_UNICODE);
        } elseif ($acaoLimpa === 'deletar' || $acaoLimpa === 'delete') {
            $valorAntigo = isset($dados['antes']) ? json_encode($dados['antes'], JSON_UNESCAPED_UNICODE) : json_encode($dados, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO viacoes.historico_alteracoes 
                (entidade_id, entidade_tipo, campo_alterado, valor_antigo, valor_novo, alterado_por)
            VALUES 
                (:entidade_id, :entidade_tipo, :campo_alterado, :valor_antigo, :valor_novo, :alterado_por)
        ");

        $stmt->execute([
            'entidade_id' => $entidadeId,
            'entidade_tipo' => $entidadeTipo,
            'campo_alterado' => $acao,
            'valor_antigo' => $valorAntigo,
            'valor_novo' => $valorNovo,
            'alterado_por' => $usuarioId
        ]);
    }


    public function getHistory(array $filters = []): array
    {
        $tabAtual = $filters['tab'] ?? '';
        $filtroUsuario = $filters['usuario'] ?? '';
        $filtroAlvo = $filters['alvo'] ?? '';

        $pagina = max(1, (int)($filters['pagina'] ?? 1));
        $porPagina = max(1, (int)($filters['porPagina'] ?? 10));
        $offset = ($pagina - 1) * $porPagina;

        $sql = "
            SELECT 
                h.id,
                h.entidade_id,
                h.entidade_tipo,
                h.campo_alterado,
                h.valor_antigo,
                h.valor_novo,
                h.alterado_por,
                h.data_alteracao,
                u.nome AS usuario_nome
            FROM viacoes.historico_alteracoes h
            LEFT JOIN viacoes.usuarios u ON u.id = h.alterado_por
            WHERE 1=1
        ";

        $params = [];

        if ($tabAtual === 'usuarios' || $tabAtual === 'viacoes') {
            $sql .= " AND h.entidade_tipo = :entidade_tipo";
            $params['entidade_tipo'] = $tabAtual;
        }

        if ($filtroUsuario !== '') {
            $sql .= " AND u.nome LIKE :filtroUsuario";
            $params['filtroUsuario'] = '%' . $filtroUsuario . '%';
        }

        if ($filtroAlvo !== '') {
            if (ctype_digit((string)$filtroAlvo)) {
                $sql .= " AND h.entidade_id = :entidade_id_direto";
                $params['entidade_id_direto'] = (int)$filtroAlvo;
            } else {
                $sql .= " AND (h.valor_novo LIKE :filtroAlvo OR h.valor_antigo LIKE :filtroAlvo)";
                $params['filtroAlvo'] = '%' . $filtroAlvo . '%';
            }
        }

        $sql .= " ORDER BY h.id DESC";

        $sql .= " LIMIT :porPagina OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('porPagina', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $historico = [];
        foreach ($rows as $row) {
            $historico[] = (object)[
                'id' => $row['id'],
                'alterado_por' => $row['alterado_por'],
                'usuario_nome' => $row['usuario_nome'] ?? 'Sistema/Admin',
                'entidade_id' => $row['entidade_id'],
                'entidade_tipo' => $row['entidade_tipo'],
                'campo_alterado' => $row['campo_alterado'],
                'valor_antigo' => $row['valor_antigo'],
                'valor_novo' => $row['valor_novo'],
                'data_alteracao' => $row['data_alteracao']
            ];
        }

        return $historico;
    }


    public function contarTotal(array $filters = []): int
    {
        $tabAtual = $filters['tab'] ?? '';
        $filtroUsuario = $filters['usuario'] ?? '';
        $filtroAlvo = $filters['alvo'] ?? '';

        $sql = "
            SELECT COUNT(*) 
            FROM viacoes.historico_alteracoes h
            LEFT JOIN viacoes.usuarios u ON u.id = h.alterado_por
            WHERE 1=1
        ";

        $params = [];

        if ($tabAtual === 'usuarios' || $tabAtual === 'viacoes') {
            $sql .= " AND h.entidade_tipo = :entidade_tipo";
            $params['entidade_tipo'] = $tabAtual;
        }

        if ($filtroUsuario !== '') {
            $sql .= " AND u.nome LIKE :filtroUsuario";
            $params['filtroUsuario'] = '%' . $filtroUsuario . '%';
        }

        if ($filtroAlvo !== '') {
            if (ctype_digit((string)$filtroAlvo)) {
                $sql .= " AND h.entidade_id = :entidade_id_direto";
                $params['entidade_id_direto'] = (int)$filtroAlvo;
            } else {
                $sql .= " AND (h.valor_novo LIKE :filtroAlvo OR h.valor_antigo LIKE :filtroAlvo)";
                $params['filtroAlvo'] = '%' . $filtroAlvo . '%';
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function listar(?string $usuario = null, ?string $viacao = null, ?string $acao = null): array
    {
        $where = [];
        $params = [];
        if ($usuario !== null && trim($usuario) !== '') {
            $where[] = 'u.nome LIKE :usuario';
            $params['usuario'] = '%' . trim($usuario) . '%';
        }
        if ($viacao !== null && trim($viacao) !== '') {
            $where[] = 'v.nome LIKE :viacao';
            $params['viacao'] = '%' . trim($viacao) . '%';
        }
        if ($acao !== null && trim($acao) !== '') {
            $where[] = 'h.campo_alterado = :acao';
            $params['acao'] = trim($acao);
        }
        $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT h.id, h.alterado_por AS usuario_id, h.entidade_id AS viacao_id, h.campo_alterado AS acao, h.valor_novo AS dados, h.data_alteracao AS data_criacao, u.nome AS usuario_nome, v.nome AS viacao_nome FROM viacoes.historico_alteracoes h LEFT JOIN viacoes.usuarios u ON u.id = h.alterado_por LEFT JOIN viacoes.cad_viacoes v ON v.id = h.entidade_id {$whereClause} ORDER BY h.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn(array $row) => Historico::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listarUsuarios(?string $usuarioLogado = null, ?string $usuarioAfetado = null): array
    {
        $where = ["h.entidade_tipo = 'usuarios'"];
        $params = [];
        if ($usuarioLogado !== null && trim($usuarioLogado) !== '') {
            $where[] = 'u_autor.nome LIKE :autor';
            $params['autor'] = '%' . trim($usuarioLogado) . '%';
        }
        if ($usuarioAfetado !== null && trim($usuarioAfetado) !== '') {
            $where[] = 'u_afetado.nome LIKE :afetado';
            $params['afetado'] = '%' . trim($usuarioAfetado) . '%';
        }
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT h.id, h.alterado_por AS usuario_id, h.entidade_id AS viacao_id, h.campo_alterado AS acao, h.valor_antigo, h.valor_novo, h.data_alteracao AS data_criacao, u_autor.nome AS usuario_nome, u_afetado.nome AS viacao_nome FROM viacoes.historico_alteracoes h LEFT JOIN viacoes.usuarios u_autor ON u_autor.id = h.alterado_por LEFT JOIN viacoes.usuarios u_afetado ON u_afetado.id = h.entidade_id {$whereClause} ORDER BY h.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn(array $row) => Historico::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}