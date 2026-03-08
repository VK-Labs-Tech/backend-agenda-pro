<?php

declare(strict_types=1);

namespace App\Domain\Agendamentos\Services;

use App\Domain\Agendamentos\Data\DTOs\Request\AgendamentoRequest;
use App\Domain\Agendamentos\Repositories\AppointmentRequestRepository;
use App\Infrastructure\Whatsapp\HttpWhatsappNotifier;
use Psr\Log\LoggerInterface;

final class AppointmentRequestService
{
    public function __construct(
        private readonly AppointmentRequestRepository $repository,
        private readonly AgendamentoService $agendamentoService,
        private readonly HttpWhatsappNotifier $whatsapp,
        private readonly LoggerInterface $logger
    ) {}

    public function create(array $data): array
    {
        return $this->repository->create($data);
    }

    public function listByCompanyId(int $companyId, ?string $status = null): array
    {
        return $this->repository->findByCompanyId($companyId, $status);
    }

    public function approve(int $requestId, int $companyId, array $payload): array
    {
        $request = $this->repository->findById($requestId);
        if (!$request || (int) ($request['company_id'] ?? 0) !== $companyId) {
            return [];
        }

        $requestDto = AgendamentoRequest::fromArray(array_merge($payload, [
            'companyId' => $companyId,
        ]));

        $appointment = $this->agendamentoService->register($requestDto);
        $appointmentId = (int) ($appointment['id'] ?? 0);

        $this->repository->updateStatus($requestId, 'APPROVED', $appointmentId ?: null);

        $this->notifyClientOnApproval($request, $appointment);

        return $appointment;
    }

    public function reject(int $requestId, int $companyId): bool
    {
        $request = $this->repository->findById($requestId);
        if (!$request || (int) ($request['company_id'] ?? 0) !== $companyId) {
            return false;
        }

        return $this->repository->updateStatus($requestId, 'REJECTED');
    }

    private function notifyClientOnApproval(array $request, array $appointment): void
    {
        $phone = trim((string) ($request['client_phone'] ?? ''));
        if ($phone === '') {
            $this->logger->warning('whatsapp_notification_skipped_missing_phone', [
                'request_id' => (int) ($request['id'] ?? 0),
                'company_id' => (int) ($request['company_id'] ?? 0),
                'appointment_id' => (int) ($appointment['id'] ?? 0),
            ]);
            return;
        }

        $clientName = trim((string) ($request['client_name'] ?? 'cliente'));
        $startAtRaw = (string) ($appointment['startAt'] ?? $appointment['start_at'] ?? '');

        $date = trim((string) ($request['preferred_date'] ?? ''));
        $time = trim((string) ($request['preferred_time'] ?? ''));

        if ($startAtRaw !== '') {
            $dateTime = $this->extractDateTime($startAtRaw);
            $date = $dateTime['date'] ?: $date;
            $time = $dateTime['time'] ?: $time;
        }

        $message = sprintf(
            'Olá %s! Seu agendamento foi aprovado para %s às %s. Obrigado!',
            $clientName !== '' ? $clientName : 'cliente',
            $date !== '' ? $date : 'a data informada',
            $time !== '' ? $time : 'o horário informado'
        );

        try {

            $sent = $this->whatsapp->sendText($phone, $message, [
                'companyId' => (int) ($request['company_id'] ?? 0),
            ]);

            if (!$sent) {
                $this->logger->warning('whatsapp_notification_not_sent', [
                    'phone' => $phone,
                    'request_id' => (int) ($request['id'] ?? 0),
                    'appointment_id' => (int) ($appointment['id'] ?? 0),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger->error('whatsapp_notification_error', [
                'message' => $exception->getMessage(),
                'phone' => $phone,
                'request_id' => (int) ($request['id'] ?? 0),
                'appointment_id' => (int) ($appointment['id'] ?? 0),
            ]);
        }
    }

    /**
     * @return array{date:string,time:string}
     */
    private function extractDateTime(string $startAt): array
    {
        if (str_contains($startAt, 'T')) {
            $startAt = str_replace('T', ' ', $startAt);
        }

        $parts = preg_split('/\s+/', trim($startAt));
        $date = $parts[0] ?? '';
        $timePart = $parts[1] ?? '';
        $time = $timePart !== '' ? substr($timePart, 0, 5) : '';

        return [
            'date' => $date,
            'time' => $time,
        ];
    }
}
