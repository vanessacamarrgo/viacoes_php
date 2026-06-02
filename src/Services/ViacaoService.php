<?php
// Toda query SQL relacionada a viações vive aqui. Recebe dados simples (strings, ints), faz as operações no banco e retorna arrays associativos.
// O Controller nunca toca no PDO diretamente.
declare(strict_types=1);

namespace App\Services;

use PDO;

/** Concentra operacoes de CRUD e mapeamento de viacao usando Arrays. */
final class ViacaoService
{
    private PDO $pdo;
    private HistoricoService $historicoService;

    /** @param PDO|null $pdo Permite injetar a conexao em testes. */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \getPdo();
        $this->historicoService = new HistoricoService($this->pdo);
    }

    public function historico(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM viacoes.historico_viacoes");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array> Retorna todas as marcas não deletadas, da mais nova para a mais antiga. */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM viacoes.viacoes WHERE data_exclusao IS NOT NULL ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Lista viações com filtros opcionais por nome, cidade, url e status. */
    public function listar(
        ?string $nome     = null,
        ?string $cidade   = null,
        ?string $url      = null,
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
        if ($cidade !== null && $cidade !== '') {
            $where[]          = 'cidade LIKE :cidade';
            $params['cidade'] = '%' . $cidade . '%';
        }
        if ($url !== null && $url !== '') {
            $where[]       = 'url LIKE :url';
            $params['url'] = '%' . $url . '%';
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

        $sql = "SELECT * FROM viacoes.viacoes {$whereClause} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return array_map(
            fn(array $row) => \App\Models\Viacao::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Conta a quantidade total de viações baseado nos mesmos filtros da listagem.
     */
    public function contarTotal(
        ?string $nome     = null,
        ?string $cidade   = null,
        ?string $url      = null,
        ?string $status   = null,
        ?string $excluido = null
    ): int {
        $where  = [];
        $params = [];

        if ($nome !== null && $nome !== '') {
            $where[]        = 'nome LIKE :nome';
            $params['nome'] = '%' . $nome . '%';
        }
        if ($cidade !== null && $cidade !== '') {
            $where[]          = 'cidade LIKE :cidade';
            $params['cidade'] = '%' . $cidade . '%';
        }
        if ($url !== null && $url !== '') {
            $where[]       = 'url LIKE :url';
            $params['url'] = '%' . $url . '%';
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

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM viacoes.viacoes {$whereClause}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
    /** @return list<array> */
    public function ativas(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM viacoes.viacoes WHERE status = 'ativo' ORDER BY id DESC");
        return array_map(
            fn(array $row) => \App\Models\Viacao::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $id): ?\App\Models\Viacao
    {
        $stmt = $this->pdo->prepare('
        SELECT id, nome, logo, url, cidade, status, data_criacao, data_exclusao 
        FROM viacoes.viacoes 
        WHERE id = :id
    ');

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return \App\Models\Viacao::fromRow($row);
    }
    /** Cria uma marca e retorna o id gerado. */
    public function create(
        ?string $nome,
        string $url,
        ?string $cidade,
        string $status,
        ?array $file = null
    ): int {
        $logo = null;

        if ($file && isset($file['name']) && $file['name'] !== '') {
            $logo = $this->validateFile($file);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO viacoes.viacoes 
            (nome, url, cidade, status, logo)
            VALUES
            (:nome, :url, :cidade, :status, :logo)'
        );

        $stmt->execute([
            'nome'   => $nome,
            'url'    => $url,
            'cidade' => $cidade,
            'status' => $status ? 1 : 0,
            'logo'   => $logo
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** Atualiza os campos de uma marca existente. */
    public function update(
        int $id,
        string $nome,
        ?string $cidade,
        int $status,
        ?string $url,
        ?array $file = null,
        ?string $dataExclusao = null
    ): void {
        $stmt = $this->pdo->prepare('SELECT logo FROM viacoes.viacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $registro  = $stmt->fetch(PDO::FETCH_ASSOC);
        $logoAtual = $registro ? $registro['logo'] : null;

        $logo = $logoAtual;
        if ($file && isset($file['name']) && $file['name'] !== '') {
            $logo = $this->validateFile($file);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE viacoes.viacoes 
         SET nome = :nome, 
             url = :url, 
             cidade = :cidade, 
             logo = :logo, 
             status = :status, 
             data_exclusao = :data_exclusao 
         WHERE id = :id'
        );

        $stmt->execute([
            'id'            => $id,
            'nome'          => $nome,
            'url'           => $url,
            'cidade'        => $cidade,
            'logo'          => $logo,
            'status'        => $status,
            'data_exclusao' => $dataExclusao,
        ]);
    }

    /** Remove uma marca pelo id. */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE viacoes.viacoes SET data_exclusao = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /** Poder restaurar registros marcados como deletados */
    public function restore(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE viacoes.viacoes SET data_exclusao = NULL WHERE id = :id");
        $stmt->execute(['id' => $id]);

        // Captura o estado pós-restauração
        $stmtNew = $this->pdo->prepare("SELECT nome, url, cidade, logo, status FROM viacoes.viacoes WHERE id = :id");
        $stmtNew->execute(['id' => $id]);
    }


    public function view(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM viacoes.viacoes WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return \App\Models\Viacao::fromRow($row);
    }

    private function validateFile(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erro no upload do arquivo.');
        }

        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('Arquivo muito grande.');
        }

        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensoesPermitidas, true)) {
            throw new \RuntimeException('Extensão não permitida.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

        if (!in_array($mime, $mimesPermitidos, true)) {
            throw new \RuntimeException('Tipo de arquivo inválido.');
        }

        if ($mime === 'image/svg+xml') {
            $this->validateSvg($file['tmp_name']);
        } else {
            if (getimagesize($file['tmp_name']) === false) {
                throw new \RuntimeException('Arquivo não é uma imagem válida.');
            }
        }

        $nomeNovo = bin2hex(random_bytes(16)) . '.' . $extension;
        $pasta    = __DIR__ . '/../public/uploads/';

        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $pasta . $nomeNovo)) {
            throw new \RuntimeException('Falha ao mover o arquivo.');
        }

        return $nomeNovo;
    }

    private function validateSvg(string $tmpPath): void
    {
        $content = file_get_contents($tmpPath);
        if ($content === false) {
            throw new \RuntimeException('Não foi possível ler o arquivo SVG.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_clear_errors();

        if ($xml === false) {
            throw new \RuntimeException('SVG inválido ou malformado.');
        }

        $padroesBloqueados = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<foreignObject/i',
        ];

        foreach ($padroesBloqueados as $padrao) {
            if (preg_match($padrao, $content)) {
                throw new \RuntimeException('SVG contém conteúdo não permitido.');
            }
        }
    }
}
