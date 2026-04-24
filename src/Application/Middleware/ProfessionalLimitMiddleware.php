<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Company\Repositories\CompanyRepository;
use App\Domain\CompanyPlan\Repositories\CompanyPlanRepository;
use App\Domain\Profissionals\Repositories\ProfissionalRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

final class ProfessionalLimitMiddleware implements Middleware
{
    private const LIMITS = [
        'trial' => 2,
        'basic' => 2,
        'medium' => 10,
        'advanced' => PHP_INT_MAX, // ilimitado
    ];

    public function __construct(
        private readonly CompanyRepository $companies,
        private readonly CompanyPlanRepository $plans,
        private readonly ProfissionalRepository $professionals
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = (int) ($request->getAttribute('userId') ?? 0);
        if ($userId <= 0) {
            return $this->jsonError('Usuário não encontrado', 401);
        }

        $companyId = $this->companies->findByUserId($userId);
        if (!$companyId) {
            return $this->jsonError('Empresa não encontrada', 404);
        }

        $plan = $this->plans->findByCompanyId((int) $companyId);
        $planCode = strtolower(trim((string) ($plan['plan_code'] ?? '')));
        $planStatus = strtolower(trim((string) ($plan['status'] ?? '')));
        $hasPlan = $planCode !== '';

        if (!$hasPlan || ($planStatus !== 'active' && $planStatus !== 'trialing')) {
            return $this->jsonError('Plano inativo', 403);
        }

        if ($planCode === 'trial' && $planStatus === 'trialing') {
            $now = new \DateTimeImmutable();
            $trialEnd = isset($plan['current_period_end']) ? new \DateTimeImmutable($plan['current_period_end']) : null;
            if ($trialEnd && $now > $trialEnd) {
                return $this->jsonError('Seu período de avaliação expirou. Contrate um plano para continuar usando.', 403);
            }
        }

        $limit = self::LIMITS[$planCode] ?? 0;
        if ($limit <= 0) {
            return $this->jsonError('Plano inválido ou sem limite definido', 403);
        }

        $count = $this->professionals->countByCompanyId((int) $companyId);
        if ($count >= $limit) {
            return $this->jsonError("Limite de {$limit} profissionais atingido", 403);
        }

        return $handler->handle($request);
    }

    private function jsonError(string $message, int $status): Response
    {
        $response = new SlimResponse();
        $payload = json_encode(['statusCode' => $status, 'data' => ['message' => $message]]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
