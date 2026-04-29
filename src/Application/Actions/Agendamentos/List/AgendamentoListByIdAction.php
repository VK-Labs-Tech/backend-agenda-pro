<?php 

namespace App\Application\Actions\Agendamentos\List;

use App\Application\Actions\Action;
use App\Domain\Agendamentos\Services\AgendamentoService;

final class AgendamentoListByIdAction extends Action
{
    public function __construct(private readonly AgendamentoService $service){}

    public function action(): \Psr\Http\Message\ResponseInterface
    {
        $id = (int) $this->resolveArg('id');
        $row = $this->service->findById($id);
        $payload = $row ? $this->mapToResponse($row) : null;

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
