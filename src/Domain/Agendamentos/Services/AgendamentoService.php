<?php 

namespace App\Domain\Agendamentos\Services;

use App\Domain\Agendamentos\Data\DTOs\Request\AgendamentoRequest;
use App\Domain\Agendamentos\Entities\AgendamentoEntity;
use App\Domain\Agendamentos\Repositories\AgendamentoRepository;

final class AgendamentoService
{
    public function __construct(private readonly AgendamentoRepository $repository) {}

    public function register(AgendamentoRequest $request): array
    {
        $duration = $this->resolveDuration($request);
        $agendamento = AgendamentoEntity::create(
            companyId: $request->companyId(),
            professionalId: $request->professionalId(),
            clientId: $request->clientId(),
            clientFirstName: $request->clientFirstName(),
            clientLastName: $request->clientLastName(),
            serviceId: $request->serviceId(),
            startAt: new \DateTimeImmutable($request->startAt()),
            endAt: new \DateTimeImmutable($request->endAt()),
            durationMinutes: $duration,
            notes: $request->notes(),
            active: $request->active() ?? true,
        );

        return $this->repository->save($agendamento);
    }

    public function update(int $id, AgendamentoRequest $request): bool
    {
        $duration = $this->resolveDuration($request);
        $agendamento = AgendamentoEntity::restore(
            id: $id,
            companyId: $request->companyId(),
            professionalId: $request->professionalId(),
            clientId: $request->clientId(),
            clientFirstName: $request->clientFirstName(),
            clientLastName: $request->clientLastName(),
            serviceId: $request->serviceId(),
            startAt: new \DateTimeImmutable($request->startAt()),
            endAt: new \DateTimeImmutable($request->endAt()),
            durationMinutes: $duration,
            notes: $request->notes(),
            active: $request->active() ?? true,
        );

        return $this->repository->update($agendamento, $id);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findAllByCompanyId(int $companyId): array
    {
        return $this->repository->findAllByCompanyId($companyId);
    }

    public function findById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function hasConflictByCompany(int $companyId, string $startAt, string $endAt): bool
    {
        return $this->repository->hasConflictByCompany($companyId, $startAt, $endAt);
    }

    private function resolveDuration(AgendamentoRequest $request): int
    {
        $duration = $request->durationMinutes();
        if ($duration > 0) {
            return $duration;
        }
        if ($request->endAt()) {
            $start = new \DateTimeImmutable($request->startAt());
            $end = new \DateTimeImmutable($request->endAt());
            $minutes = (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);
            return max(1, $minutes);
        }
        return 0;
    }
}
