<?php

declare(strict_types=1);

namespace App\Application\Actions\Billing;

use App\Application\Actions\Action;
use App\Domain\Company\Repositories\CompanyRepository;
use App\Domain\CompanyPlan\Repositories\CompanyPlanRepository;
use App\Domain\Agendamentos\Repositories\AgendamentoRepository;
use App\Domain\Profissionals\Repositories\ProfissionalRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

final class BillingUsageAction extends Action
{
    private const LIMITS = [
        'trial' => 200,
        'basic' => 200,
        'medium' => 800,
        'advanced' => 2000,
    ];

    public function __construct(
        LoggerInterface $logger,
        private readonly CompanyRepository $companies,
        private readonly CompanyPlanRepository $plans,
        private readonly AgendamentoRepository $appointments,
        private readonly ProfissionalRepository $professionals
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $userId = (int) $this->request->getAttribute('userId');
        
        $companyId = $this->companies->findByUserId($userId);
        if (!$companyId) {
            return $this->respondWithData(['message' => 'Empresa não encontrada'], 404);
        }

        $plan = $this->plans->findByCompanyId((int) $companyId);
        $planCode = strtolower(trim((string) ($plan['plan_code'] ?? 'trial')));
        $limit = self::LIMITS[$planCode] ?? self::LIMITS['trial'];

        $now = new \DateTimeImmutable();
        $startOfMonth = $now->modify('first day of this month')->format('Y-m-d 00:00:00');
        $endOfMonth = $now->modify('last day of this month')->format('Y-m-d 23:59:59');

        $used = $this->appointments->countByCompanyAndPeriod((int) $companyId, $startOfMonth, $endOfMonth);
        $proCount = $this->professionals->countByCompanyId((int) $companyId);

        return $this->respondWithData([
            'plan' => $planCode,
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'percentage' => $limit > 0 ? round(($used / $limit) * 100, 2) : 0,
            'professionals' => [
                'limit' => $this->planProfessionalLimit($planCode),
                'used' => $proCount,
                'remaining' => max(0, $this->planProfessionalLimit($planCode) - $proCount),
            ],
        ]);
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
