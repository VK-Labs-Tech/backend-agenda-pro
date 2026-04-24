<?php

namespace Application\Actions\ServicePackage;

use Domain\ServicePackage\Repositories\ServicePackageRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteServicePackageAction
{
    public function __construct(private ServicePackageRepositoryInterface $packageRepo)
    {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['package_id'] ?? 0);
        $deleted = $this->packageRepo->delete($id);

        if (!$deleted) {
            $response->getBody()->write(json_encode(['error' => 'Pacote não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
