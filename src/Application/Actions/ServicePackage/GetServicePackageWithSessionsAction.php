<?php

namespace App\Application\Actions\ServicePackage;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\ServicePackage\Services\ServicePackageServiceInterface;

class GetServicePackageWithSessionsAction
{
    private ServicePackageServiceInterface $service;

    public function __construct(ServicePackageServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $packageId = (int)$args['package_id'];
        $result = $this->service->getPackageWithSessions($packageId);
        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Pacote não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
