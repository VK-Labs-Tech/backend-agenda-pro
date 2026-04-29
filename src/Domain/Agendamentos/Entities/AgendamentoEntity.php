<?php 

namespace App\Domain\Agendamentos\Entities;

final class AgendamentoEntity
{
    public function __construct(
        private ?int $id,
        private int $companyId,
        private int $professionalId,
        private int $clientId,
        private ?string $clientFirstName,
        private ?string $clientLastName,
        private int $serviceId,
        private \DateTimeImmutable $startAt,
        private \DateTimeImmutable $endAt,
        private int $durationMinutes,
        private ?string $notes,
        private bool $active,
    ) {}

    public static function create(
        int $companyId,
        int $professionalId,
        int $clientId,
        ?string $clientFirstName,
        ?string $clientLastName,
        int $serviceId,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        int $durationMinutes,
        ?string $notes = null,
        bool $active = true,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            professionalId: $professionalId,
            clientId: $clientId,
            clientFirstName: $clientFirstName,
            clientLastName: $clientLastName,
            serviceId: $serviceId,
            startAt: $startAt,
            endAt: $endAt,
            durationMinutes: $durationMinutes,
            notes: $notes,
            active: $active,
        );
    }

    public static function restore(
        int $id,
        int $companyId,
        int $professionalId,
        int $clientId,
        ?string $clientFirstName,
        ?string $clientLastName,
        int $serviceId,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        int $durationMinutes,
        ?string $notes,
        bool $active,
    ): self {
        return new self(
            id: $id,
            companyId: $companyId,
            professionalId: $professionalId,
            clientId: $clientId,
            clientFirstName: $clientFirstName,
            clientLastName: $clientLastName,
            serviceId: $serviceId,
            startAt: $startAt,
            endAt: $endAt,
            durationMinutes: $durationMinutes,
            notes: $notes,
            active: $active,
        );
    }

    public function getId(): ?int { return $this->id; }
    public function getCompanyId(): int { return $this->companyId; }
    public function getProfessionalId(): int { return $this->professionalId; }
    public function getClientId(): int { return $this->clientId; }
    public function getClientFirstName(): ?string { return $this->clientFirstName; }
    public function getClientLastName(): ?string { return $this->clientLastName; }
    public function getServiceId(): int { return $this->serviceId; }
    public function getStartAt(): \DateTimeImmutable { return $this->startAt; }
    public function getEndAt(): \DateTimeImmutable { return $this->endAt; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function getNotes(): ?string { return $this->notes; }
    public function isActive(): bool { return $this->active; }
}
