<?php
namespace App\Domain\Clients\Repositories;  

use App\Domain\Clients\Interfaces\ClientInterface;
use App\Domain\Clients\Entities\ClientEntity;
use Illuminate\Database\Connection;


class ClientRepository implements ClientInterface
{

    public function __construct(
        protected Connection $connection,
    ) {}

    /**
     * Telefone opcional: grava string vazia quando ausente (compatível com coluna NOT NULL sem migração nullable).
     */
    private function phoneForStorage(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        return $phone;
    }

    private function origemForStorage(?string $origem): string
    {
        return $origem ?? '';
    }

    public function register(string $name, ?string $phone, ?string $origem, int $companyId): bool
    {
        return $this->connection->table('clients')->insertGetId([
            'tenant_id' => $companyId,
            'company_id' => $companyId,
            'name' => $name,
            'phone' => $this->phoneForStorage($phone),
            'origem' => $this->origemForStorage($origem),
            'active' => 1,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function findRowById(int $id): ?array
    {
        $row = $this->connection->table('clients')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function findByFullNameAndCompany(string $fullName, int $companyId): ?array
    {
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower(trim($fullName))
            : strtolower(trim($fullName));
        if ($normalized === '') {
            return null;
        }

        $row = $this->connection->table('clients')
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->first();

        return $row ? (array) $row : null;
    }

    public function findByPhoneAndCompany(?string $phone, int $companyId): ?array
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $row = $this->connection->table('clients')
            ->where('company_id', $companyId)
            ->where('phone', $phone)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * Register a client for a specific company and return the inserted id.
     */
    public function registerForCompany(string $name, ?string $phone, ?string $origem, int $companyId): int
    {
        return (int) $this->connection->table('clients')->insertGetId([
            'tenant_id' => $companyId,
            'company_id' => $companyId,
            'name' => $name,
            'phone' => $this->phoneForStorage($phone),
            'origem' => $this->origemForStorage($origem),
            'active' => 1,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(ClientEntity $client): bool
    {
        return $this->connection->table('clients')
            ->where('id', $client->getId())
            ->update([
                'name' => $client->getName(),
                'phone' => $this->phoneForStorage($client->getPhone()),
                'origem' => $this->origemForStorage($client->getOrigem()),
            ]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->connection->table('clients')
            ->where('id', $id)
            ->delete() > 0;
    }

    public function findAll(): array
    {
        return $this->connection->table('clients')->get()->toArray();
    }

    public function findAllByCompanyId(int $companyId): array
    {
        return $this->connection->table('clients')
            ->where('company_id', $companyId)
            ->get()
            ->toArray();
    }

    public function findById(int $id): ?self
    {
        $client = $this->connection->table('clients')
            ->where('id', $id)
            ->first();

        if (!$client) {
            return null;
        }

        return new self($this->connection);
    }


}