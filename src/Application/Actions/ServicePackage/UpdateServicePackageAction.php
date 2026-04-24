<?php

namespace Application\Actions\ServicePackage;

use Domain\ServicePackage\Repositories\ServicePackageRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UpdateServicePackageAction
{
    public function __construct(private ServicePackageRepositoryInterface $packageRepo)
    {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['package_id'] ?? 0);
        $package = $this->packageRepo->findById($id);

        if (!$package) {
            $response->getBody()->write(json_encode(['error' => 'Pacote não encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = (array) $request->getParsedBody();
        foreach (['quantidade_sessoes', 'frequencia', 'dia_semana', 'horario', 'data_inicio', 'data_fim', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $package->{$field} = $data[$field];
            }
        }

        $this->packageRepo->update($package);
        $response->getBody()->write(json_encode($package));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
