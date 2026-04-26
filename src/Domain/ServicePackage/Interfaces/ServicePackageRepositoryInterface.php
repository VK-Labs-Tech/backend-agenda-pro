<?php

namespace App\Domain\ServicePackage\Interfaces;

use App\Domain\ServicePackage\Entities\ServicePackageEntity;

interface ServicePackageRepositoryInterface
{
    public function create(ServicePackageEntity $package): int;
    public function findById(int $id): ?ServicePackageEntity;
    public function findByCompany(int $companyId): array;
    public function findByClientId(int $clientId): array;
    public function update(ServicePackageEntity $package): bool;
    public function delete(int $id): bool;
}
