<?php 
namespace App\Domain\Clients\Entities;

class ClientEntity 
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $phone,
        private ?string $origem
    ) {}

    public static function restore(
        int $id,
        string $name,
        ?string $phone,
        ?string $origem
    ): self {
        return new self(
            id: $id,
            name: $name,
            phone: $phone,
            origem: $origem
        );
    }  

    public static function create(
        string $name,
        ?string $phone,
        ?string $origem
    ) {
        return new self(
            id: 0,
            name: $name,
            phone: $phone,
            origem: $origem
        );
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPhone(): ?string {
        return $this->phone;
    }

    public function getOrigem(): ?string {
        return $this->origem;
    }
}