<?php 

namespace App\Domain\Agendamentos\Interfaces;

use App\Domain\Agendamentos\Entities\AgendamentoEntity;

interface AgendamentoInterface
{
    public function save(AgendamentoEntity $agendamento): array;
    public function update(AgendamentoEntity $agendamento, int $id): bool;
    public function delete(int $id): bool;
    public function findAll(): array;
    public function findAllByCompanyId(int $companyId): array;
    public function findById(int $id): ?array;

    /**
     * @param int[] $serviceIds ordem preservada, ids > 0
     */
    public function replaceAppointmentServices(int $appointmentId, array $serviceIds): void;
}
