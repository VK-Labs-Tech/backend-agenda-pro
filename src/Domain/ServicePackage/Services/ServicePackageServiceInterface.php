<?php

namespace App\Domain\ServicePackage\Services;

use App\Domain\ServicePackage\Entities\ServicePackageEntity;
use App\Domain\ServicePackage\Entities\ServicePackageSessionEntity;

interface ServicePackageServiceInterface
{
    public function createPackage(ServicePackageEntity $package, array $sessions): int;
    public function getPackageWithSessions(int $packageId): ?array;
    public function listPackagesByClient(int $clientId): array;
    public function findByCompany(int $companyId): array;
    public function deletePackage(int $packageId): bool;
    public function cancelSession(int $sessionId): bool;
    public function rescheduleSession(int $sessionId, string $newDateTime): bool;
}
