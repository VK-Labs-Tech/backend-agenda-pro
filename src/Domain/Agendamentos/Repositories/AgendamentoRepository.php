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
        $rows = $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->orderBy('start_at', 'asc')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->attachServiceIdsList($rows);
    }

    public function findAllByCompanyId(int $companyId): array
    {
        $rows = $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointments.company_id', $companyId)
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->orderBy('start_at', 'asc')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return $this->attachServiceIdsList($rows);
    }

    public function findById(int $id): ?array
    {
        $row = $this->connection->table('appointments')
            ->join('appointment_status', 'appointments.id', '=', 'appointment_status.appointment_id')
            ->where('appointments.id', $id)
            ->where('appointment_status.status', '!=', 'Cancelado')
            ->first();

        if (!$row) {
            return null;
        }

        $row = (array) $row;
        $row['service_ids'] = $this->resolveServiceIdsForRow($row);

        return $row;
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

    public function replaceAppointmentServices(int $appointmentId, array $serviceIds): void
    {
        $this->connection->table('appointment_services')->where('appointment_id', $appointmentId)->delete();
        $position = 0;
        foreach ($serviceIds as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) {
                continue;
            }
            $this->connection->table('appointment_services')->insert([
                'appointment_id' => $appointmentId,
                'service_id' => $sid,
                'sort_order' => $position,
            ]);
            $position++;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function attachServiceIdsList(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $ids = [];
        foreach ($rows as $r) {
            if (isset($r['id'])) {
                $ids[] = (int) $r['id'];
            }
        }
        $byAppointment = $this->loadServiceIdsByAppointmentIds($ids);
        $out = [];
        foreach ($rows as $r) {
            $aid = isset($r['id']) ? (int) $r['id'] : 0;
            $r['service_ids'] = $byAppointment[$aid] ?? $this->fallbackServiceIds($r);
            $out[] = $r;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return int[]
     */
    private function resolveServiceIdsForRow(array $row): array
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) {
            $map = $this->loadServiceIdsByAppointmentIds([$id]);
            if (isset($map[$id]) && $map[$id] !== []) {
                return $map[$id];
            }
        }

        return $this->fallbackServiceIds($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return int[]
     */
    private function fallbackServiceIds(array $row): array
    {
        $single = (int) ($row['service_id'] ?? 0);

        return $single > 0 ? [$single] : [];
    }

    /**
     * @param int[] $appointmentIds
     * @return array<int, int[]>
     */
    private function loadServiceIdsByAppointmentIds(array $appointmentIds): array
    {
        $appointmentIds = array_values(array_filter(array_map('intval', $appointmentIds)));
        if ($appointmentIds === []) {
            return [];
        }
        $map = [];
        $rows = $this->connection->table('appointment_services')
            ->whereIn('appointment_id', $appointmentIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        foreach ($rows as $r) {
            $a = (int) $r->appointment_id;
            $s = (int) $r->service_id;
            if (!isset($map[$a])) {
                $map[$a] = [];
            }
            if ($s > 0) {
                $map[$a][] = $s;
            }
        }

        return $map;
    }
}
