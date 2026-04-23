<?php

namespace Application\Actions\ServicePackage;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Domain\ServicePackage\Entities\ServicePackageEntity;
use Domain\ServicePackage\Entities\ServicePackageSessionEntity;
use Domain\ServicePackage\Services\ServicePackageServiceInterface;

class CreateServicePackageAction
{
    private ServicePackageServiceInterface $service;

    public function __construct(ServicePackageServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $package = new ServicePackageEntity();
        $package->client_id = $data['client_id'];
        $package->company_id = $data['company_id'];
        $package->service_id = $data['service_id'];
        $package->quantidade_sessoes = $data['quantidade_sessoes'];
        $package->frequencia = $data['frequencia'];
        $package->dia_semana = $data['dia_semana'];
        $package->horario = $data['horario'];
        $package->data_inicio = $data['data_inicio'];
        $package->data_fim = $data['data_fim'] ?? null;
        $package->status = 'ativo';

        // Gerar sessões recorrentes
        $sessions = $this->generateSessions($package);

        $packageId = $this->service->createPackage($package, $sessions);

        $response->getBody()->write(json_encode(['id' => $packageId]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function generateSessions(ServicePackageEntity $package): array
    {
        $sessions = [];
        $date = new \DateTime($package->data_inicio);
        $count = 0;
        $diasSemana = [
            'domingo' => 0,
            'segunda' => 1,
            'terca' => 2,
            'quarta' => 3,
            'quinta' => 4,
            'sexta' => 5,
            'sabado' => 6
        ];
        $targetDay = $diasSemana[strtolower($package->dia_semana)] ?? 0;
        // Ajusta para o próximo dia da semana desejado
        while ((int)$date->format('w') !== $targetDay) {
            $date->modify('+1 day');
        }
        while ($count < $package->quantidade_sessoes) {
            $session = new ServicePackageSessionEntity();
            $session->data = $date->format('Y-m-d') . ' ' . $package->horario;
            $session->status = 'agendado';
            $sessions[] = $session;
            $date->modify('+1 week');
            $count++;
        }
        return $sessions;
    }
}
