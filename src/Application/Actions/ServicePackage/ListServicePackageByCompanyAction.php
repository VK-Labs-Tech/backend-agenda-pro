<?php

namespace Application\Actions\ServicePackage;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Domain\ServicePackage\Services\ServicePackageServiceInterface;

class ListServicePackagesByCompanyAction
{
    private ServicePackageServiceInterface $service;

    public function __construct(ServicePackageServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $companyId = (int)$args['company_id'];
        $packages = $this->service->findByCompany($companyId);
        $response->getBody()->write(json_encode($packages));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
