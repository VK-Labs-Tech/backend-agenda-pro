<?php

namespace App\Application\Actions\ServicePackage;

use App\Domain\Agendamentos\Entities\AgendamentoEntity;
use App\Domain\Agendamentos\Repositories\AgendamentoRepository;
use App\Domain\Profissionals\Repositories\ProfissionalRepository;
use App\Domain\ServicePackage\Entities\ServicePackageEntity;
use App\Domain\ServicePackage\Entities\ServicePackageSessionEntity;
use App\Domain\ServicePackage\Services\ServicePackageServiceInterface;
use App\Domain\Services\Repositories\ServiceRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CreateServicePackageAction
{
    private ServicePackageServiceInterface $service;
    private AgendamentoRepository $agendamentoRepository;
    private ServiceRepository $serviceRepository;
    private ProfissionalRepository $profissionalRepository;

    public function __construct(
        ServicePackageServiceInterface $service,
        AgendamentoRepository $agendamentoRepository,
        ServiceRepository $serviceRepository,
        ProfissionalRepository $profissionalRepository
    ) {
        $this->service = $service;
        $this->agendamentoRepository = $agendamentoRepository;
        $this->serviceRepository = $serviceRepository;
        $this->profissionalRepository = $profissionalRepository;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $package = new ServicePackageEntity();
        $package->client_id = $data['client_id'];
        $package->company_id = $data['company_id'];
        $package->service_id = $data['service_id'];
        $package->quantidade_sessoes = $data['quantidade_sessoes'];
        $package->frequencia = strtolower((string) ($data['frequencia'] ?? 'semanal'));
        $package->dia_semana = $data['dia_semana'] ?? $this->resolveWeekdayByDate((string) ($data['data_inicio'] ?? ''));
        $package->horario = (string) ($data['horario'] ?? '00:00');
        $package->data_inicio = $data['data_inicio'];
        $package->data_fim = $data['data_fim'] ?? null;
        $package->status = 'ativo';

        $service = $this->serviceRepository->findById((int) $package->service_id);
        if (!$service) {
            $response->getBody()->write(json_encode([
                'ok' => false,
                'message' => 'Serviço não encontrado para gerar os agendamentos do pacote.',
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $durationMinutes = (int) preg_replace('/\D+/', '', (string) $service->getDuration());
        if ($durationMinutes <= 0) {
            $durationMinutes = 60;
        }

        $professionals = $this->profissionalRepository->findAllByCompanyId((int) $package->company_id);
        $firstProfessional = is_array($professionals) ? ($professionals[0] ?? null) : null;
        $professionalId = (int) (($firstProfessional->id ?? 0));
        if ($professionalId <= 0) {
            $response->getBody()->write(json_encode([
                'ok' => false,
                'message' => 'Cadastre um profissional para criar agendamentos automáticos de pacote.',
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        // Gerar sessões recorrentes
        $sessions = $this->generateSessions($package);
        foreach ($sessions as $session) {
            $startAt = new \DateTimeImmutable($session->data);
            $endAt = $startAt->modify("+{$durationMinutes} minutes");
            $appointment = AgendamentoEntity::create(
                companyId: (int) $package->company_id,
                professionalId: $professionalId,
                clientId: (int) $package->client_id,
                clientFirstName: null,
                clientLastName: null,
                serviceId: (int) $package->service_id,
                startAt: $startAt,
                endAt: $endAt,
                durationMinutes: $durationMinutes,
                notes: sprintf('Sessão automática do pacote de serviço (%s).', $package->frequencia),
                active: true
            );

            $createdAppointment = $this->agendamentoRepository->save($appointment);
            $apptId = isset($createdAppointment['id']) ? (int) $createdAppointment['id'] : 0;
            if ($apptId > 0) {
                $this->agendamentoRepository->replaceAppointmentServices($apptId, [(int) $package->service_id]);
            }
            $session->agendamento_id = $apptId > 0 ? $apptId : null;
        }

        $packageId = $this->service->createPackage($package, $sessions);

        $response->getBody()->write(json_encode(['id' => $packageId]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function generateSessions(ServicePackageEntity $package): array
    {
        $sessions = [];
        $startDateRaw = trim((string) $package->data_inicio);
        $startTimeRaw = trim((string) $package->horario);
        $normalizedTime = preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTimeRaw) ? $startTimeRaw : ($startTimeRaw . ':00');
        $date = new \DateTime("{$startDateRaw} {$normalizedTime}");
        $initialDate = clone $date;
        $count = 0;

        $frequency = strtolower(trim((string) $package->frequencia));
        $isMonthly = $frequency === 'mensal';
        $anchorWeekday = (int) $initialDate->format('w');
        $anchorOccurrence = $this->resolveWeekdayOccurrenceInMonth($initialDate);
        $intervalSpec = '+1 week';

        while ($count < $package->quantidade_sessoes) {
            $session = new ServicePackageSessionEntity();
            $session->data = $date->format('Y-m-d H:i:s');
            $session->status = 'agendado';
            $sessions[] = $session;

            if ($isMonthly) {
                $date = $this->resolveNextMonthlyDateByWeekday(
                    $date,
                    $anchorWeekday,
                    $anchorOccurrence
                );
                $date->setTime(
                    (int) $initialDate->format('H'),
                    (int) $initialDate->format('i'),
                    (int) $initialDate->format('s')
                );
            } else {
                $date->modify($intervalSpec);
            }
            $count++;
        }
        return $sessions;
    }

    private function resolveWeekdayByDate(string $dateInput): string
    {
        $date = new \DateTime($dateInput);
        $days = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
        return $days[(int) $date->format('w')] ?? 'segunda';
    }

    private function resolveWeekdayOccurrenceInMonth(\DateTime $date): int
    {
        $day = (int) $date->format('j');
        return (int) floor(($day - 1) / 7) + 1;
    }

    private function resolveNextMonthlyDateByWeekday(\DateTime $current, int $weekday, int $occurrence): \DateTime
    {
        $base = (clone $current)->modify('first day of next month');
        $month = (int) $base->format('n');

        $firstWeekday = (int) $base->format('w');
        $offset = ($weekday - $firstWeekday + 7) % 7;
        $candidateDay = 1 + $offset + (($occurrence - 1) * 7);
        $daysInMonth = (int) $base->format('t');

        if ($candidateDay > $daysInMonth) {
            $lastDay = (clone $base)->modify('last day of this month');
            while ((int) $lastDay->format('w') !== $weekday) {
                $lastDay->modify('-1 day');
            }
            return $lastDay;
        }

        $resolved = clone $base;
        $resolved->setDate((int) $base->format('Y'), $month, $candidateDay);
        return $resolved;
    }
}
