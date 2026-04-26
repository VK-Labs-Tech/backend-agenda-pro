<?php

namespace App\Domain\ServicePackage\Repositories;

use App\Domain\ServicePackage\Entities\ServicePackageSessionEntity;
use Illuminate\Database\Connection;

class ServicePackageSessionRepository implements ServicePackageSessionRepositoryInterface
{

    public function __construct(protected Connection $connection)
    {
    }

    public function create(ServicePackageSessionEntity $session): int
    {
        $stmt = $this->connection->table('service_package_sessions')->insertGetId([
            'service_package_id' => $session->service_package_id,
            'agendamento_id' => $session->agendamento_id,
            'data' => $session->data,
            'status' => $session->status
        ]);
        return (int) $stmt;
    }

    public function findByPackageId(int $packageId): array
    {
        $stmt = $this->connection->table('service_package_sessions')->where('service_package_id', $packageId)->get()->toArray();
        return $stmt ? array_map(fn($item) => $this->hydrate($item), $stmt) : [];
        return $result;
    }

    public function update(ServicePackageSessionEntity $session): bool
    {
        $stmt = $this->connection->table('service_package_sessions')->where('id', $session->id)->update([
            'agendamento_id' => $session->agendamento_id,
            'data' => $session->data,
            'status' => $session->status
        ]);
        return $stmt > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->connection->table('service_package_sessions')->where('id', $id)->delete();
        return $stmt > 0;
    }

    private function hydrate(array $data): ServicePackageSessionEntity
    {
        $entity = new ServicePackageSessionEntity();
        foreach ($data as $key => $value) {
            if (property_exists($entity, $key)) {
                $entity->$key = $value;
            }
        }
        return $entity;
    }
}
