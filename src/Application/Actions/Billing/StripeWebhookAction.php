<?php

declare(strict_types=1);

namespace App\Application\Actions\Billing;

use App\Application\Actions\Action;
use App\Domain\CompanyPlan\Services\CompanyPlanService;
use Stripe\StripeClient;
use Stripe\Webhook;

final class StripeWebhookAction extends Action
{
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        private CompanyPlanService $companyPlans
    ) {
        parent::__construct($logger);
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $payload = (string) $this->request->getBody();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');
        $secret = (string) ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

        if ($secret === '') {
            return $this->respondWithData(['message' => 'Webhook não configurado'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            return $this->respondWithData(['message' => 'Assinatura inválida'], 400);
        }

        $type = (string) ($event->type ?? '');

        if ($type === 'checkout.session.completed') {
            $session = $event->data->object;
            $companyId = (int) ($session->metadata->company_id ?? $session->client_reference_id ?? 0);
            $plan = (string) ($session->metadata->plan ?? '');
            $subscriptionId = (string) ($session->subscription ?? '');
            $customerId = (string) ($session->customer ?? '');

            if ($companyId > 0 && $plan !== '') {
                $this->upsertFromSubscription($companyId, $plan, $subscriptionId, $customerId);
            }
        }

        if ($type === 'customer.subscription.created' || $type === 'customer.subscription.updated' || $type === 'customer.subscription.deleted') {
            $subscription = $event->data->object;
            $companyId = (int) ($subscription->metadata->company_id ?? 0);
            $plan = (string) ($subscription->metadata->plan ?? '');
            $subscriptionId = (string) ($subscription->id ?? '');
            $customerId = (string) ($subscription->customer ?? '');

            if ($companyId > 0 && $plan !== '') {
                $status = (string) ($subscription->status ?? '');
                $periodEnd = isset($subscription->current_period_end)
                    ? (new \DateTimeImmutable())->setTimestamp((int) $subscription->current_period_end)
                    : null;

                $this->companyPlans->upsert($companyId, [
                    'plan_code' => $plan,
                    'status' => $status,
                    'stripe_customer_id' => $customerId,
                    'stripe_subscription_id' => $subscriptionId,
                    'current_period_end' => $periodEnd?->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return $this->respondWithData(['received' => true]);
    }

    private function upsertFromSubscription(int $companyId, string $plan, string $subscriptionId, string $customerId): void
    {
        if ($subscriptionId === '') {
            $this->companyPlans->upsert($companyId, [
                'plan_code' => $plan,
                'status' => 'pending',
                'stripe_customer_id' => $customerId ?: null,
                'stripe_subscription_id' => null,
                'current_period_end' => null,
            ]);
            return;
        }

        $secret = (string) ($_ENV['STRIPE_SECRET_KEY'] ?? '');
        if ($secret === '') {
            $this->companyPlans->upsert($companyId, [
                'plan_code' => $plan,
                'status' => 'pending',
                'stripe_customer_id' => $customerId ?: null,
                'stripe_subscription_id' => $subscriptionId,
                'current_period_end' => null,
            ]);
            return;
        }

        try {
            $stripe = new StripeClient($secret);
            $subscription = $stripe->subscriptions->retrieve($subscriptionId, []);

            $status = (string) ($subscription->status ?? '');
            $periodEnd = isset($subscription->current_period_end)
                ? (new \DateTimeImmutable())->setTimestamp((int) $subscription->current_period_end)
                : null;

            $this->companyPlans->upsert($companyId, [
                'plan_code' => $plan,
                'status' => $status,
                'stripe_customer_id' => $customerId ?: null,
                'stripe_subscription_id' => $subscriptionId,
                'current_period_end' => $periodEnd?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Stripe subscription retrieve error', [
                'message' => $e->getMessage(),
                'company_id' => $companyId,
                'plan' => $plan,
                'subscription_id' => $subscriptionId,
            ]);

            $this->companyPlans->upsert($companyId, [
                'plan_code' => $plan,
                'status' => 'pending',
                'stripe_customer_id' => $customerId ?: null,
                'stripe_subscription_id' => $subscriptionId,
                'current_period_end' => null,
            ]);
        }
    }
}
