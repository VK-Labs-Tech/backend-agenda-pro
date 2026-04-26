<?php

namespace App\Domain\ServicePackage\Repositories;

use App\Domain\ServicePackage\Entities\ServicePackageSessionEntity;

interface ServicePackageSessionRepositoryInterface
{
    public function create(ServicePackageSessionEntity $session): int;
    public function findByPackageId(int $packageId): array;
    public function update(ServicePackageSessionEntity $session): bool;
    public function delete(int $id): bool;
}
