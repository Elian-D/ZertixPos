<?php

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use App\Models\Landlord\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Events\WebhookEvent;
use Srmklive\PayPal\Facades\PayPal;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalGateway implements PaymentGatewayContract
{
    public function createSubscription(
        Tenant $tenant,
        Plan $plan,
        string $payerName,
        string $payerEmail,
        string $returnUrl,
        string $cancelUrl,
    ): array {
        if (empty($plan->gateway_plan_id)) {
            throw new \RuntimeException(
                "El plan «{$plan->name}» todavía no tiene un billing_plan_id de PayPal asignado ".
                '(columna plans.gateway_plan_id) — hay que crearlo primero en PayPal (Billing Plans) y guardarlo.'
            );
        }

        $response = $this->provider()
            ->addBillingPlanById($plan->gateway_plan_id)
            ->addCustomId((string) $tenant->getTenantKey())
            ->setReturnAndCancelUrl($returnUrl, $cancelUrl)
            ->setupSubscription($payerName, $payerEmail);

        $approvalUrl = collect($response['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (empty($response['id']) || $approvalUrl === null) {
            throw new \RuntimeException('PayPal no devolvió un id de suscripción o un link de aprobación: '.json_encode($response));
        }

        return [
            'gateway_subscription_id' => $response['id'],
            'approval_url' => $approvalUrl,
        ];
    }

    public function cancelSubscription(Subscription $subscription, string $reason): void
    {
        // Trial sin pago real todavía (REQ-3.8) — sin gateway_subscription_id
        // no hay nada que cancelar del lado de PayPal.
        if (! empty($subscription->gateway_subscription_id)) {
            $this->provider()->cancelSubscription($subscription->gateway_subscription_id, $reason);
        }

        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    public function handleWebhook(array $payload, array $headers, string $rawBody): bool
    {
        $webhookId = config('paypal.webhook_id');

        if (empty($webhookId)) {
            Log::warning('PayPalGateway: PAYPAL_WEBHOOK_ID no configurado — webhook rechazado sin verificar.');

            return false;
        }

        $provider = $this->provider();

        if (! $provider->verifyWebHookLocally($headers, $webhookId, $rawBody)) {
            Log::warning('PayPalGateway: firma de webhook inválida.', ['event_id' => $payload['id'] ?? null]);

            return false;
        }

        $event = WebhookEvent::fromArray($payload);

        match (true) {
            $event->is('BILLING.SUBSCRIPTION.ACTIVATED') => $this->onSubscriptionActivated($event),
            $event->is('BILLING.SUBSCRIPTION.CANCELLED') => $this->onSubscriptionCancelled($event),
            $event->is('BILLING.SUBSCRIPTION.SUSPENDED') => $this->onSubscriptionSuspended($event),
            $event->is('PAYMENT.SALE.COMPLETED') => $this->onPaymentCompleted($event, $provider),
            default => Log::info("PayPalGateway: evento sin manejar: {$event->eventType}"),
        };

        return true;
    }

    private function onSubscriptionActivated(WebhookEvent $event): void
    {
        $subscription = $this->findSubscription($event->resource['id'] ?? '');

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'active',
            'starts_at' => $subscription->starts_at ?? now(),
            'current_period_ends_at' => $this->nextBillingTime($event->resource) ?? $subscription->current_period_ends_at,
        ]);

        $this->clearScheduledDeletion($subscription->tenant_id);

        // REQ-3.11 — un cambio de plan crea una Subscription nueva con el
        // plan_id nuevo (ManageSubscription::subscribe()), pero tenants.plan_id
        // (lo que la propia vista muestra como "plan actual", y lo que
        // Plan::assignTo() del Wizard usa) no se movía solo — sin esto, la
        // pantalla seguiría mostrando el plan viejo como actual después de
        // pagar el cambio. No re-provisiona installation_modules del plan
        // nuevo (eso es lo que Plan::assignTo() hace, pero corre en contexto
        // de tenant y este webhook corre en contexto central) — queda como
        // limitación conocida, documentada en v1.3.0.md §3.11.
        Tenant::where('id', $subscription->tenant_id)->update(['plan_id' => $subscription->plan_id]);
    }

    private function onSubscriptionCancelled(WebhookEvent $event): void
    {
        $this->findSubscription($event->resource['id'] ?? '')
            ?->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    private function onSubscriptionSuspended(WebhookEvent $event): void
    {
        $this->findSubscription($event->resource['id'] ?? '')
            ?->update(['status' => 'paused']);
    }

    /**
     * El pago de una renovación no trae la próxima fecha de corte en su
     * propio payload — solo confirma que se cobró. La fecha autoritativa
     * (`current_period_ends_at`, la que el middleware de REQ-3.5 consulta)
     * se pide aparte con showSubscriptionDetails(), no se infiere del evento.
     */
    private function onPaymentCompleted(WebhookEvent $event, PayPalClient $provider): void
    {
        $gatewaySubscriptionId = $event->resource['billing_agreement_id'] ?? null;

        if (! $gatewaySubscriptionId) {
            return;
        }

        $subscription = $this->findSubscription($gatewaySubscriptionId);

        if (! $subscription) {
            return;
        }

        $amount = $event->resource['amount']['total']
            ?? $event->resource['amount']['value']
            ?? null;

        $currency = $event->resource['amount']['currency']
            ?? $event->resource['amount']['currency_code']
            ?? 'USD';

        SubscriptionInvoice::updateOrCreate(
            ['gateway_transaction_id' => $event->resource['id'] ?? $event->id],
            [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'paid',
                'paid_at' => now(),
            ],
        );

        $details = $provider->showSubscriptionDetails($gatewaySubscriptionId);
        $nextBilling = $this->nextBillingTime(is_array($details) ? $details : []);

        if ($nextBilling) {
            $subscription->update(['current_period_ends_at' => $nextBilling, 'status' => 'active']);
        }

        $this->clearScheduledDeletion($subscription->tenant_id);
    }

    /**
     * REQ-3.8 — "si el cliente paga antes de que se cumplan los 90 días,
     * scheduled_deletion_at se limpia al confirmarse el pago". Se llama desde
     * los dos webhooks que representan un pago confirmado (activación nueva
     * y renovación exitosa) — cualquiera de los dos cuenta como "pagó".
     */
    private function clearScheduledDeletion(string $tenantId): void
    {
        Tenant::where('id', $tenantId)
            ->whereNotNull('scheduled_deletion_at')
            ->update(['scheduled_deletion_at' => null]);
    }

    private function findSubscription(string $gatewaySubscriptionId): ?Subscription
    {
        if ($gatewaySubscriptionId === '') {
            return null;
        }

        $subscription = Subscription::where('gateway_subscription_id', $gatewaySubscriptionId)->first();

        if (! $subscription) {
            Log::warning("PayPalGateway: webhook para una suscripción desconocida: {$gatewaySubscriptionId}");
        }

        return $subscription;
    }

    /** @param array<string, mixed> $resource */
    private function nextBillingTime(array $resource): ?Carbon
    {
        $value = $resource['billing_info']['next_billing_time'] ?? null;

        return $value ? Carbon::parse($value) : null;
    }

    private function provider(): PayPalClient
    {
        $provider = PayPal::setProvider();
        $provider->withExceptions();
        $provider->getAccessToken();

        return $provider;
    }
}
