<?php

namespace App\Application\Actions\ServicePackage;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\ServicePackage\Services\ServicePackageServiceInterface;

class ListServicePackagesByClientAction
{
    private ServicePackageServiceInterface $service;

    public function __construct(ServicePackageServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $clientId = (int)$args['client_id'];
        $packages = $this->service->listPackagesByClient($clientId);
        $response->getBody()->write(json_encode($packages));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
