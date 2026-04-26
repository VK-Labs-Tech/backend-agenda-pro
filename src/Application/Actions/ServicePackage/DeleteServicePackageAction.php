<?php

namespace App\Application\Actions\ServicePackage;

use App\Domain\ServicePackage\Services\ServicePackageServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteServicePackageAction
{
    private ServicePackageServiceInterface $service;

    public function __construct(ServicePackageServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $packageId = (int) ($args['package_id'] ?? 0);
        if ($packageId <= 0) {
            $response->getBody()->write(json_encode([
                'ok' => false,
                'message' => 'package_id inválido',
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $deleted = $this->service->deletePackage($packageId);
        if (!$deleted) {
            $response->getBody()->write(json_encode([
                'ok' => false,
                'message' => 'Pacote não encontrado',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
