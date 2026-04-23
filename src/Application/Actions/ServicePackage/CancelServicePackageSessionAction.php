<?php

namespace Application\Actions\ServicePackage;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Domain\ServicePackage\Repositories\ServicePackageSessionRepositoryInterface;

class CancelServicePackageSessionAction
{
    private ServicePackageSessionRepositoryInterface $sessionRepo;

    public function __construct(ServicePackageSessionRepositoryInterface $sessionRepo)
    {
        $this->sessionRepo = $sessionRepo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $sessionId = (int)$args['session_id'];
        // Busca a sessão
        $sessions = $this->sessionRepo->findByPackageId($args['package_id']);
        $session = null;
        foreach ($sessions as $s) {
            if ($s->id === $sessionId) {
                $session = $s;
                break;
            }
        }
        if (!$session) {
            $response->getBody()->write(json_encode(['error' => 'Sessão não encontrada']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $session->status = 'cancelado';
        $this->sessionRepo->update($session);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
