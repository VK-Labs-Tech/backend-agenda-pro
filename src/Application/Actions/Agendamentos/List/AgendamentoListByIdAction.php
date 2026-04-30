<?php 

namespace App\Application\Actions\Agendamentos\List;

use App\Application\Actions\Action;
use App\Application\Actions\Agendamentos\AgendamentoApiMapper;
use App\Domain\Agendamentos\Services\AgendamentoService;

final class AgendamentoListByIdAction extends Action
{
    public function __construct(private readonly AgendamentoService $service){}

    public function action(): \Psr\Http\Message\ResponseInterface
    {
        $id = (int) $this->resolveArg('id');
        $row = $this->service->findById($id);
        $payload = $row ? AgendamentoApiMapper::toApiArray($row) : null;

        return $this->respondWithData($payload);
    }
}
