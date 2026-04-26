<?php

namespace App\Domain\ServicePackage\Services;

use App\Domain\ServicePackage\Entities\ServicePackageEntity;
use App\Domain\ServicePackage\Interfaces\ServicePackageRepositoryInterface;
use App\Domain\ServicePackage\Repositories\ServicePackageSessionRepositoryInterface;

class ServicePackageService implements ServicePackageServiceInterface
{

    public function __construct(
        private ServicePackageRepositoryInterface $packageRepo,
        private ServicePackageSessionRepositoryInterface $sessionRepo
    ) {
    }

    public function findByCompany(int $companyId): array
    {
        return $this->packageRepo->findByCompany($companyId);
    }

    public function createPackage(ServicePackageEntity $package, array $sessions): int
    {
        $packageId = $this->packageRepo->create($package);
        foreach ($sessions as $session) {
            $session->service_package_id = $packageId;
            $this->sessionRepo->create($session);
        }
        return $packageId;
    }

    public function getPackageWithSessions(int $packageId): ?array
    {
        $package = $this->packageRepo->findById($packageId);
        if (!$package) return null;
        $sessions = $this->sessionRepo->findByPackageId($packageId);
        return [
            'package' => $package,
            'sessions' => $sessions
        ];
    }

    public function listPackagesByClient(int $clientId): array
    {
        return $this->packageRepo->findByClientId($clientId);
    }

    public function deletePackage(int $packageId): bool
    {
        return $this->packageRepo->delete($packageId);
    }

    public function cancelSession(int $sessionId): bool
    {
        // Busca, altera status e salva
        // Implementação depende do método de busca por id
        return false;
    }

    public function rescheduleSession(int $sessionId, string $newDateTime): bool
    {
        // Busca, altera data e salva
        // Implementação depende do método de busca por id
        return false;
    }
}
