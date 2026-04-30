<?php 

namespace App\Application\Actions\Agendamentos\Update;

use App\Application\Actions\Action;
use App\Application\Actions\Agendamentos\AgendamentoApiMapper;
use App\Application\Actions\Agendamentos\AppointmentRequestEnricher;
use App\Domain\Agendamentos\Data\DTOs\Request\AgendamentoRequest;
use App\Domain\Agendamentos\Services\AgendamentoService;

final class AgendamentoUpdateAction extends Action
{
    public function __construct(
        private readonly AgendamentoService $service,
        private readonly AppointmentRequestEnricher $enricher,
    ) {
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $id = (int) $this->resolveArg('id');
        $data = (array) $this->request->getParsedBody();
        $data = $this->enricher->enrichParsedBody($data);
        $request = AgendamentoRequest::fromArray($data);
        $updated = $this->service->update($id, $request);
        if (!$updated) {
            return $this->respondWithData(['message' => 'Não foi possível salvar. Verifique os serviços selecionados.'], 422);
        }

        $row = $this->service->findById($id);
        if ($row) {
            return $this->respondWithData(AgendamentoApiMapper::toApiArray($row));
        }

        return $this->respondWithData(['success' => true]);
    }
}
