<?php

declare(strict_types=1);

namespace App\Application\Actions\Agendamentos;

use App\Domain\Clients\Services\ClientService;
use App\Infrastructure\Exceptions\CustomException;

/**
 * Garante clientId válido e preenche snapshot nome/sobrenome no payload do agendamento.
 */
final class AppointmentRequestEnricher
{
    public function __construct(
        private readonly ClientService $clients,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichParsedBody(array $data): array
    {
        $companyId = (int) ($data['companyId'] ?? $data['company_id'] ?? 0);
        $clientId = (int) ($data['clientId'] ?? $data['client_id'] ?? 0);
        $first = trim((string) ($data['clientFirstName'] ?? $data['client_first_name'] ?? ''));
        $last = trim((string) ($data['clientLastName'] ?? $data['client_last_name'] ?? ''));

        if ($companyId <= 0) {
            throw new CustomException('Empresa inválida', 400);
        }

        if ($clientId > 0) {
            $parts = $this->clients->getNamePartsForClient($clientId, $companyId);
            if ($parts === null) {
                throw new CustomException('Cliente não encontrado nesta empresa', 404);
            }
            [$fn, $ln] = $parts;
            $data['clientId'] = $clientId;
            $data['client_id'] = $clientId;
            $data['clientFirstName'] = $fn;
            $data['clientLastName'] = $ln;
            $data['client_first_name'] = $fn;
            $data['client_last_name'] = $ln;

            return $data;
        }

        if ($first === '' || $last === '') {
            throw new CustomException(
                'Informe nome e sobrenome do cliente ou selecione um cliente cadastrado.',
                400
            );
        }

        try {
            $newId = $this->clients->findOrCreateByNameParts($first, $last, $companyId);
        } catch (\InvalidArgumentException $e) {
            throw new CustomException($e->getMessage(), 400);
        }

        $data['clientId'] = $newId;
        $data['client_id'] = $newId;
        $data['clientFirstName'] = $first;
        $data['clientLastName'] = $last;
        $data['client_first_name'] = $first;
        $data['client_last_name'] = $last;

        return $data;
    }
}
