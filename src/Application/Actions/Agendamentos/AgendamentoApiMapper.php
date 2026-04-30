<?php

declare(strict_types=1);

namespace App\Application\Actions\Agendamentos;

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
final class AgendamentoApiMapper
{
    public static function toApiArray(array $row): array
    {
        $startAt = (string) ($row['start_at'] ?? '');
        $duration = (int) ($row['duration_minutes'] ?? 0);
        $endAt = $startAt !== '' ? (new \DateTimeImmutable($startAt))->modify("+{$duration} minutes")->format('Y-m-d H:i:s') : null;
        $serviceIds = self::serviceIds($row);
        $primary = $serviceIds[0] ?? 0;
        if ($primary <= 0) {
            $primary = (int) ($row['service_id'] ?? 0);
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'companyId' => (int) ($row['company_id'] ?? 0),
            'professionalId' => (int) ($row['professional_id'] ?? 0),
            'clientId' => (int) ($row['client_id'] ?? 0),
            'clientFirstName' => $row['client_first_name'] ?? null,
            'clientLastName' => $row['client_last_name'] ?? null,
            'serviceId' => $primary,
            'serviceIds' => $serviceIds,
            'startAt' => $startAt,
            'endAt' => $endAt,
            'durationMinutes' => $duration,
            'notes' => $row['notes'] ?? null,
            'active' => isset($row['active']) ? (bool) $row['active'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return int[]
     */
    public static function serviceIds(array $row): array
    {
        if (isset($row['service_ids']) && is_array($row['service_ids'])) {
            $ids = array_map('intval', $row['service_ids']);

            return array_values(array_filter($ids, static fn (int $i) => $i > 0));
        }
        $s = (int) ($row['service_id'] ?? 0);

        return $s > 0 ? [$s] : [];
    }
}
