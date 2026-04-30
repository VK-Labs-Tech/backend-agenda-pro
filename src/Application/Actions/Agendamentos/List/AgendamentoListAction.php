<?php 

namespace App\Application\Actions\Agendamentos\List;

use App\Application\Actions\Action;
use App\Application\Actions\Agendamentos\AgendamentoApiMapper;
use App\Domain\Agendamentos\Services\AgendamentoService;
use App\Domain\Company\Repositories\CompanyRepository;

final class AgendamentoListAction extends Action
{
    public function __construct(
        private readonly AgendamentoService $service,
        private readonly CompanyRepository $companies
    ){}

    public function action(): \Psr\Http\Message\ResponseInterface
    {
        $userId = (int) ($this->request->getAttribute('userId') ?? 0);
        $companyId = $this->companies->findByUserId($userId);
        if (!$companyId) {
            return $this->respondWithData([]);
        }

        $rows = $this->service->findAllByCompanyId($companyId);
        $payload = array_map(static function (array $r): array {
            return AgendamentoApiMapper::toApiArray($r);
        }, $rows);

        return $this->respondWithData($payload);
    }
}
