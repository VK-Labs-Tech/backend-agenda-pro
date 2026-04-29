<?php

namespace App\Domain\Agendamentos\Repositories;

use App\Domain\Agendamentos\Entities\AgendamentoEntity;
use App\Domain\Agendamentos\Interfaces\AgendamentoInterface;
use Illuminate\Database\Connection;
use function Illuminate\Support\now;

final class AgendamentoRepository implements AgendamentoInterface
{
    public function __construct(protected Connection $connection)
    {
    }

    public function save(AgendamentoEntity $agendamento): array
    {
        $id = $this->connection->table('appointments')->insertGetId([
            'company_id' => $agendamento->getCompanyId(),
            'professional_id' => $agendamento->getProfessionalId(),
            'client_id' => $agendamento->getClientId(),
            'client_first_name' => $agendamento->getClientFirstName(),
            'client_last_name' => $agendamento->getClientLastName(),
            'service_id' => $agendamento->getServiceId(),
            'start_at' => $agendamento->getStartAt()->format('Y-m-d H:i:s'),
            'end_at' => $agendamento->getEndAt()->format('Y-m-d H:i:s'),
            'duration_minutes' => $agendamento->getDurationMinutes(),
            'notes' => $agendamento->getNotes(),
            'active' => $agendamento->isActive() ? 1 : 0,
        ]);

        $this->connection->table('appointment_status')->insert([
            'appointment_id' => $id,
            'status' => 'Agendado',
            'changed_at' => now(),
        ]);

        return $this->findById((int) $id) ?? [];
    }

    public function update(AgendamentoEntity $agendamento, int $id): bool
    {
        return $this->connection->table('appointments')
            ->where('id', $id)
            ->update([
                'company_id' => $agendamento->getCompanyId(),
                'professional_id' => $agendamento->getProfessionalId(),
                'client_id' => $agendamento->getClientId(),
                'client_first_name' => $agendamento->getClientFirstName(),
                'client_last_name' => $agendamento->getClientLastName(),
                'service_id' => $agendamento->getServiceId(),
                'start_at' => $agendamento->getStartAt()->format('Y-m-d H:i:s'),
                'end_at' => $agendamento->getEndAt()->format('Y-m-d H:i:s'),
                'duration_minutes' => $agendamento->getDurationMinutes(),
                'notes' => $agendamento->getNotes(),
                'active' => $agendamento->isActive() ? 1 : 0,
            ]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->connection->table('appointment_status')
            ->where('appointment_id', $id)
            ->update(['status' => 'Cancelado']) > 0;
    }

    public function findAll(): array
    {
        return $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->orderBy('start_at', 'asc')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function findAllByCompanyId(int $companyId): array
    {
        return $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointments.company_id', $companyId)
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->orderBy('start_at', 'asc')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function findById(int $id): ?array
    {
        $row = $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointments.id', $id)
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->first();

        return $row ? (array) $row : null;
    }

    public function hasConflictByCompany(int $companyId, string $startAt, string $endAt): bool
    {
        $count = $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointments.company_id', $companyId)
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->where('appointments.start_at', '<', $endAt)
            ->where('appointments.end_at', '>', $startAt)
            ->count();

        return $count > 0;
    }

    public function countByCompanyAndPeriod(int $companyId, string $startDate, string $endDate): int
    {
        return (int) $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointments.company_id', $companyId)
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->whereBetween('appointments.start_at', [$startDate, $endDate])
            ->count();
    }
}
