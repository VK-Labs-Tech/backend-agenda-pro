<?php

namespace Domain\ServicePackage\Repositories;

use Domain\ServicePackage\Entities\ServicePackageEntity;
use Illuminate\Database\Connection;

class ServicePackageRepository implements ServicePackageRepositoryInterface
{
    public function __construct(protected Connection $connection)
    {
    }

    public function findByCompany(int $companyId): array
    {
        return $this->connection->table('service_packages')
            ->where('service_packages.company_id', $companyId)
            ->leftJoin('services', 'services.id', '=', 'service_packages.service_id')
            ->leftJoin('clients', 'clients.id', '=', 'service_packages.client_id')
            ->select(
                'service_packages.id',
                'service_packages.client_id',
                'service_packages.company_id',
                'service_packages.service_id',
                'service_packages.quantidade_sessoes',
                'service_packages.frequencia',
                'service_packages.dia_semana',
                'service_packages.horario',
                'service_packages.data_inicio',
                'service_packages.data_fim',
                'clients.name as client_name',
                'services.name as service_name'
            )
            ->get()
            ->toArray();
    }
    public function create(ServicePackageEntity $package): int
    {
        $stmt = $this->connection->table('service_packages')->insertGetId([
            'client_id' => $package->client_id,
            'company_id' => $package->company_id,
            'service_id' => $package->service_id,
            'quantidade_sessoes' => $package->quantidade_sessoes,
            'frequencia' => $package->frequencia,
            'dia_semana' => $package->dia_semana,
            'horario' => $package->horario,
            'data_inicio' => $package->data_inicio,
            'data_fim' => $package->data_fim,
            'status' => $package->status,
        ]);
        return (int) $stmt;
    }

    public function findById(int $id): ?ServicePackageEntity
    {
        $stmt = $this->connection->table('service_packages')->where('id', $id)->first();
        return $stmt ? $this->hydrate((array) $stmt) : null;
    }

    public function findByClientId(int $clientId): array
    {
        $stmt = $this->connection->table('service_packages')->where('client_id', $clientId)->get()->toArray();
        return $stmt ? array_map(fn($item) => $this->hydrate($item), $stmt) : [];
    }

    public function update(ServicePackageEntity $package): bool
    {
        $stmt = $this->connection->table('service_packages')->where('id', $package->id)->update([
            'quantidade_sessoes' => $package->quantidade_sessoes,
            'frequencia' => $package->frequencia,
            'dia_semana' => $package->dia_semana,
            'horario' => $package->horario,
            'data_inicio' => $package->data_inicio,
            'data_fim' => $package->data_fim,
            'status' => $package->status,
        ]);
        return $stmt > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->connection->table('service_packages')->where('id', $id)->delete();
        return $stmt > 0;
    }

    private function hydrate(array $data): ServicePackageEntity
    {
        $entity = new ServicePackageEntity();
        foreach ($data as $key => $value) {
            if (property_exists($entity, $key)) {
                $entity->$key = $value;
            }
        }
        return $entity;
    }
}
