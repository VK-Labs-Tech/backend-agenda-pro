<?php 

namespace App\Domain\Clients\Data\DTOs\Request;

use App\Infrastructure\Exceptions\CustomException;


final class ClientRequest 
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $origem
    ) {
        if ($this->phone !== null && $this->phone !== '') {
            $this->assertPhoneFormat($this->phone);
        }
    }

    private function assertPhoneFormat(string $phone): void
    {
        if (strlen($phone) < 11) {
            throw new CustomException("Contato deve ter 11 dígitos", 400);
        }
    }

    public static function fromArray(array $data): self
    {
        $rawPhone = $data['phone'] ?? '';
        $digits = preg_replace('/\D+/', '', (string) $rawPhone);
        $phone = $digits === '' ? null : $digits;

        return new self(
            name: (string) ($data['name'] ?? ''),
            phone: $phone,
            origem: $data['origem'] ?? null,
        );
    }
}