<?php

declare(strict_types=1);

namespace App\Models;

/** Representa uma marca de viação carregada do banco. */
final class Viacao
{
    public function __construct(
        public int $id,
        public string $nome,
        public ?string $logo,
        public string $url,
        public string $cidade,
        public bool $status,
        public ?string $data_criacao,
        public ?string $data_exclusao,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            logo: $row['logo'],
            url: (string) $row['url'],
            cidade: (string) $row['cidade'],
            status: (int) $row['status'] === 1,

            data_criacao: $row['data_criacao'] !== null
                ? (new \DateTime($row['data_criacao']))
                    ->setTimezone(new \DateTimeZone('America/Sao_Paulo'))
                    ->format('d/m/Y H:i:s')
                : null,
            data_exclusao: $row['data_exclusao'] !== null
                    ? (new \DateTime($row['data_exclusao']))
                        ->setTimezone(new \DateTimeZone('America/Sao_Paulo'))
                        ->format('d/m/Y H:i:s')
                    : null,
            );
    }
}