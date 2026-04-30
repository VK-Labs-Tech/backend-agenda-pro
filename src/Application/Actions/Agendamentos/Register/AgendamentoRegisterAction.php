<?php 

namespace App\Application\Actions\Agendamentos\Register;

use App\Application\Actions\Action;
use App\Application\Actions\Agendamentos\AgendamentoApiMapper;
use App\Application\Actions\Agendamentos\AppointmentRequestEnricher;
use App\Domain\Agendamentos\Data\DTOs\Request\AgendamentoRequest;
use App\Domain\Agendamentos\Services\AgendamentoService;

final class AgendamentoRegisterAction extends Action
{
    public function __construct(
        private readonly AgendamentoService $service,
        private readonly AppointmentRequestEnricher $enricher,
    ) {
    }

    public function action(): \Psr\Http\Message\ResponseInterface
    {
        $data = (array) $this->request->getParsedBody();
        $data = $this->enricher->enrichParsedBody($data);
        $request = AgendamentoRequest::fromArray($data);
        $created = $this->service->register($request);
        if (empty($created['id'])) {
            return $this->respondWithData(['message' => 'Selecione ao menos um serviço.'], 422);
        }

        $payload = AgendamentoApiMapper::toApiArray($created);

        return $this->respondWithData($payload, 201);
    }
}
