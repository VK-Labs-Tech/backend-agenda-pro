<?php 

namespace App\Application\Actions\Agendamentos\List;

use App\Application\Actions\Action;
use App\Domain\Agendamentos\Services\AgendamentoService;
use App\Domain\Company\Repositories\CompanyRepository;

final class AgendamentoListAction extends Action
{
    public function __construct(
        private readonly AgendamentoService $service,
        private readonly CompanyRepository $companies
    ){}

    public function action(): \Psr\Http\Message\ResponseInterface
    {
        $userId = (int) ($this->request->getAttribute('userId') ?? 0);
        $companyId = $this->companies->findByUserId($userId);
        if (!$companyId) {
            return $this->respondWithData([]);
        }

        $rows = $this->service->findAllByCompanyId($companyId);
        $payload = array_map([$this, 'mapToResponse'], $rows);

        return $this->respondWithData($payload);
    }

    private function mapToResponse(array $row): array
    {
        $startAt = (string) ($row['start_at'] ?? '');
        $duration = (int) ($row['duration_minutes'] ?? 0);
        $endAt = $startAt !== '' ? (new \DateTimeImmutable($startAt))->modify("+{$duration} minutes")->format('Y-m-d H:i:s') : null;

        return [
            'id' => (int) ($row['id'] ?? 0),
            'companyId' => (int) ($row['company_id'] ?? 0),
            'professionalId' => (int) ($row['professional_id'] ?? 0),
            'clientId' => (int) ($row['client_id'] ?? 0),
            'clientFirstName' => $row['client_first_name'] ?? null,
            'clientLastName' => $row['client_last_name'] ?? null,
            'serviceId' => (int) ($row['service_id'] ?? 0),
            'startAt' => $startAt,
            'endAt' => $endAt,
            'durationMinutes' => $duration,
            'notes' => $row['notes'] ?? null,
            'active' => isset($row['active']) ? (bool) $row['active'] : null,
        ];
    }
}
