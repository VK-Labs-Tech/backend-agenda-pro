<?php

declare(strict_types=1);

use App\Domain\Agendamentos\Repositories\AppointmentRequestRepository;
use App\Domain\Clients\Repositories\ClientRepository;
use App\Domain\Company\Repositories\CompanyRepository;
use App\Domain\Products\Repositories\ProductRepository;
use App\Domain\Profissionals\Repositories\ProfissionalRepository;
use App\Domain\Services\Repositories\ServiceRepository;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\Auth\Repositories\LoginAttemptRepository;
use App\Domain\Auth\Repositories\UserTokenRepository;
use App\Domain\Agendamentos\Repositories\AgendamentoRepository;
use App\Domain\Roles\Repositories\RoleRepository;
use App\Domain\Roles\Repositories\RolePermissionRepository;
use App\Domain\Permissions\Repositories\PermissionRepository;
use App\Domain\Settings\Repositories\SettingsRepository;
use App\Domain\Dashboard\Repositories\DashboardRepository;
use App\Domain\StockMovements\Repositories\StockMovementRepository;
use App\Domain\Cases\Repositories\CaseRepository;
use App\Domain\CompanyPlan\Repositories\CompanyPlanRepository;
use App\Domain\ServicePackage\Interfaces\ServicePackageRepositoryInterface;
use App\Domain\ServicePackage\Repositories\ServicePackageRepository;
use App\Domain\ServicePackage\Repositories\ServicePackageSessionRepository;
use App\Domain\ServicePackage\Repositories\ServicePackageSessionRepositoryInterface;
use App\Domain\ServicePackage\Services\ServicePackageService;
use App\Domain\ServicePackage\Services\ServicePackageServiceInterface;
use DI\ContainerBuilder;

use function DI\autowire;
use function DI\get;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        ClientRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        ProfissionalRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        UserRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        CompanyRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        LoginAttemptRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        UserTokenRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        ProductRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        ServiceRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        ServicePackageRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        ServicePackageSessionRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        ServicePackageRepositoryInterface::class => get(ServicePackageRepository::class),
        ServicePackageSessionRepositoryInterface::class => get(ServicePackageSessionRepository::class),
        ServicePackageServiceInterface::class => get(ServicePackageService::class),

        AgendamentoRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        RoleRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        RolePermissionRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        PermissionRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        SettingsRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        DashboardRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        StockMovementRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        AppointmentRequestRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        CaseRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

        CompanyPlanRepository::class => autowire()
            ->constructorParameter('connection', get('db.agendapro')),

    ]);
};
