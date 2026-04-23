<?php

namespace Application\Actions\ServicePackage;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Domain\ServicePackage\Repositories\ServicePackageSessionRepositoryInterface;

class RescheduleServicePackageSessionAction
{
    private ServicePackageSessionRepositoryInterface $sessionRepo;

    public function __construct(ServicePackageSessionRepositoryInterface $sessionRepo)
    {
        $this->sessionRepo = $sessionRepo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $sessionId = (int)$args['session_id'];
        $data = $request->getParsedBody();
        $newDate = $data['data'] ?? null;
        if (!$newDate) {
            $response->getBody()->write(json_encode(['error' => 'Nova data não informada']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
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
        $session->data = $newDate;
        $session->status = 'agendado';
        $this->sessionRepo->update($session);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
