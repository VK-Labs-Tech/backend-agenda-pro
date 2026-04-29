<?php
namespace App\Domain\Clients\Services;

use App\Domain\Clients\Data\DTOs\Request\ClientRequest;
use App\Domain\Clients\Entities\ClientEntity;
use App\Domain\Clients\Repositories\ClientRepository;

class ClientService
{
  public function __construct(private readonly ClientRepository $repository)
  {
  }

  public function registerForCompany(ClientRequest $client, int $companyId): int
  {
    $entity = ClientEntity::create(
      name: $client->name,
      phone: $client->phone,
      origem: $client->origem
    );

    $existing = $this->repository->findByPhoneAndCompany($entity->getPhone(), $companyId);
    if ($existing) return (int) ($existing['id'] ?? 0);
    

    return $this->repository->register(
      name: $entity->getName(),
      phone: $entity->getPhone(),
      origem: $entity->getOrigem(),
      companyId: $companyId
    );
  }

  public function update(ClientRequest $client, int $id): bool
  {
    $clientEntity = ClientEntity::restore(
      id: $id,
      name: $client->name,
      phone: $client->phone,
      origem: $client->origem
    );
    return $this->repository->update(client: $clientEntity);
  }

  public function delete(int $id): bool
  {
    return $this->repository->delete($id);
  }

  public function findAll(): array
  {
    return $this->repository->findAll();
  }

  public function findAllByCompanyId(int $companyId): array
  {
    return $this->repository->findAllByCompanyId($companyId);
  }
  public function findById(int $id): ?ClientEntity
  {
    return $this->repository->findById($id);
  }

  /**
   * @return array{0: string, 1: string}
   */
  public static function splitFullName(string $full): array
  {
    $full = trim($full);
    if ($full === '') {
      return ['', ''];
    }
    if (function_exists('mb_strpos')) {
      $pos = mb_strpos($full, ' ');
      if ($pos === false) {
        return [$full, ''];
      }

      return [mb_substr($full, 0, $pos), trim(mb_substr($full, $pos + 1))];
    }

    $pos = strpos($full, ' ');
    if ($pos === false) {
      return [$full, ''];
    }

    return [substr($full, 0, $pos), trim(substr($full, $pos + 1))];
  }

  /**
   * Busca cliente por nome completo (mesma empresa) ou cadastra com origem automática.
   */
  public function findOrCreateByNameParts(string $firstName, string $lastName, int $companyId): int
  {
    $first = trim($firstName);
    $last = trim($lastName);
    $full = trim($first . ' ' . $last);
    if ($full === '') {
      throw new \InvalidArgumentException('Nome do cliente é obrigatório');
    }

    $existing = $this->repository->findByFullNameAndCompany($full, $companyId);
    if ($existing !== null) {
      return (int) ($existing['id'] ?? 0);
    }

    $request = ClientRequest::fromArray([
      'name' => $full,
      'phone' => '',
      'origem' => 'Cadastro automático (agendamento)',
    ]);

    return $this->registerForCompany($request, $companyId);
  }

  /** Partes do nome para snapshot no agendamento; valida empresa. */
  public function getNamePartsForClient(int $clientId, int $companyId): ?array
  {
    $row = $this->repository->findRowById($clientId);
    if ($row === null || (int) ($row['company_id'] ?? 0) !== $companyId) {
      return null;
    }

    return self::splitFullName((string) ($row['name'] ?? ''));
  }
}