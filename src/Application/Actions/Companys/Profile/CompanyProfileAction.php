<?php

declare(strict_types=1);

namespace App\Application\Actions\Companys\Profile;

use App\Application\Actions\Action;
use App\Domain\Company\Repositories\CompanyRepository;
use App\Domain\Settings\Repositories\SettingsRepository;
use App\Domain\CompanyPlan\Repositories\CompanyPlanRepository;
use App\Domain\Agendamentos\Repositories\AgendamentoRepository;
use App\Domain\Profissionals\Repositories\ProfissionalRepository;

final class CompanyProfileAction extends Action
{
    private const LIMITS = [
        'trial' => 200,
        'basic' => 200,
        'medium' => 800,
        'advanced' => 2000,
    ];

    public function __construct(
        private CompanyRepository $companies,
        private SettingsRepository $settings,
        private CompanyPlanRepository $plans,
        private AgendamentoRepository $appointments,
        private ProfissionalRepository $professionals
    ) {}

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $id = (int) $this->resolveArg('id');

        $company = $this->companies->findById($id);
        if (!$company) {
            return $this->respondWithData(['message' => 'Empresa não encontrada'], 404);
        }

        $userId = (int) ($this->request->getAttribute('userId') ?? 0);
        $ownerCompanyId = $this->companies->findByUserId($userId);
        if ($userId > 0 && $ownerCompanyId !== $id) {
            return $this->respondWithData(['message' => 'Acesso negado'], 403);
        }

        $settings = $this->settings->findByCompanyId($id) ?? [];
        $plan = $this->plans->findByCompanyId($id) ?? [];

        $planCode = strtolower(trim((string) ($plan['plan_code'] ?? 'trial')));
        $limit = self::LIMITS[$planCode] ?? self::LIMITS['trial'];

        $now = new \DateTimeImmutable();
        $startOfMonth = $now->modify('first day of this month')->format('Y-m-d 00:00:00');
        $endOfMonth = $now->modify('last day of this month')->format('Y-m-d 23:59:59');

        $used = $this->appointments->countByCompanyAndPeriod($id, $startOfMonth, $endOfMonth);
        $proCount = $this->professionals->countByCompanyId($id);

        $response = [
            'company' => [
                'id' => $company->id(),
                'name' => $company->name(),
                'cnpj' => $company->cnpj(),
                'address' => $company->address(),
                'city' => $company->city(),
                'state' => $company->state(),
                'active' => $company->isActive(),
                'created_at' => $company->createdAt()->format('c'),
            ],
            'settings' => [
                'brand_name' => $settings['brand_name'] ?? null,
                'phone' => $settings['phone'] ?? null,
                'email' => $settings['email'] ?? null,
                'public_start_time' => $settings['public_start_time'] ?? null,
                'public_end_time' => $settings['public_end_time'] ?? null,
                'public_slot_minutes' => $settings['public_slot_minutes'] ?? null,
                'public_working_days' => $settings['public_working_days'] ?? null,
            ],
            'plan' => array_merge($plan, [
                'calculated' => [
                    'plan_code' => $planCode,
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => max(0, $limit - $used),
                    'percentage' => $limit > 0 ? round(($used / $limit) * 100, 2) : 0,
                    'professionals' => [
                        'limit' => $this->planProfessionalLimit($planCode),
                        'used' => $proCount,
                        'remaining' => max(0, $this->planProfessionalLimit($planCode) - $proCount),
                    ],
                ],
            ]),
        ];

        return $this->respondWithData($response);
    }

    private function planProfessionalLimit(string $planCode): int
    {
        return match ($planCode) {
            'trial' => 2,
            'basic' => 2,
            'medium' => 10,
            'advanced' => PHP_INT_MAX,
            default => 0,
        };
    }
}
