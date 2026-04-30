<?php 

namespace App\Domain\Agendamentos\Data\DTOs\Request;

final class AgendamentoRequest
{
    /**
     * @param int[] $serviceIds
     */
    public function __construct(
        private int $companyId,
        private int $professionalId,
        private int $clientId,
        private ?string $clientFirstName,
        private ?string $clientLastName,
        private array $serviceIds,
        private string $startAt,
        private ?string $endAt,
        private int $durationMinutes,
        private ?string $notes,
        private ?bool $active,
    ) {}

    public static function fromArray(array $data): self
    {
        $cfRaw = $data['clientFirstName'] ?? $data['client_first_name'] ?? null;
        $clRaw = $data['clientLastName'] ?? $data['client_last_name'] ?? null;
        $cf = $cfRaw !== null && $cfRaw !== '' ? (string) $cfRaw : null;
        $cl = $clRaw !== null && $clRaw !== '' ? (string) $clRaw : null;

        $rawIds = $data['serviceIds'] ?? $data['service_ids'] ?? null;
        $serviceIds = self::parseServiceIds($rawIds);
        if ($serviceIds === []) {
            $one = (int) ($data['serviceId'] ?? $data['service_id'] ?? 0);
            if ($one > 0) {
                $serviceIds = [$one];
            }
        }

        return new self(
            companyId: (int) ($data['companyId'] ?? $data['company_id'] ?? 0),
            professionalId: (int) ($data['professionalId'] ?? $data['professional_id'] ?? 0),
            clientId: (int) ($data['clientId'] ?? $data['client_id'] ?? 0),
            clientFirstName: $cf,
            clientLastName: $cl,
            serviceIds: $serviceIds,
            startAt: (string) ($data['startAt'] ?? $data['start_at'] ?? ''),
            endAt: isset($data['endAt']) ? (string) $data['endAt'] : (isset($data['end_at']) ? (string) $data['end_at'] : null),
            durationMinutes: (int) ($data['durationMinutes'] ?? $data['duration_minutes'] ?? 0),
            notes: $data['notes'] ?? $data['observation'] ?? null,
            active: isset($data['active']) ? (bool) $data['active'] : null,
        );
    }

    /**
     * @return int[]
     */
    private static function parseServiceIds(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $out[] = $n;
            }
        }

        return $out;
    }

    public function companyId(): int { return $this->companyId; }
    public function professionalId(): int { return $this->professionalId; }
    public function clientId(): int { return $this->clientId; }
    public function clientFirstName(): ?string { return $this->clientFirstName; }
    public function clientLastName(): ?string { return $this->clientLastName; }

    /** Id do primeiro serviço (legado: coluna service_id) */
    public function serviceId(): int { return (int) ($this->serviceIds[0] ?? 0); }

    /**
     * @return int[]
     */
    public function serviceIds(): array
    {
        return $this->serviceIds;
    }

    public function startAt(): string { return $this->startAt; }
    public function endAt(): ?string { return $this->endAt; }
    public function durationMinutes(): int { return $this->durationMinutes; }
    public function notes(): ?string { return $this->notes; }
    public function active(): ?bool { return $this->active; }
}
