<?php

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Mail\Billing\InvoicePaid;
use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use App\Models\Landlord\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    /**
     * REQ-4.7.1 — ver docblock del contrato sobre por qué PayPal siempre
     * exige reconsentimiento acá, sin excepción. `application_context` con
     * `return_url`/`cancel_url` sigue el mismo patrón que `createSubscription()`
     * (`setReturnAndCancelUrl()`) — probado en vivo contra el sandbox real
     * que el endpoint `revise` los acepta en el payload igual que `create`.
     */
    public function reviseSubscription(
        Subscription $subscription,
        Plan $newPlan,
        string $returnUrl,
        string $cancelUrl,
    ): array {
        if (empty($subscription->gateway_subscription_id)) {
            throw new \RuntimeException('Esta suscripción no tiene un acuerdo de PayPal activo del cual partir — no hay nada que revisar.');
        }

        if (empty($newPlan->gateway_plan_id)) {
            throw new \RuntimeException("El plan «{$newPlan->name}» todavía no tiene un billing_plan_id de PayPal asignado.");
        }

        $response = $this->provider()->reviseSubscription($subscription->gateway_subscription_id, [
            'plan_id' => $newPlan->gateway_plan_id,
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        $approvalUrl = collect($response['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if ($approvalUrl === null) {
            throw new \RuntimeException('PayPal no devolvió un link de aprobación para el cambio de plan: '.json_encode($response));
        }

        return ['approval_url' => $approvalUrl];
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

    /**
     * REQ-3.11 — contraparte SÍNCRONA de `onSubscriptionActivated()`, para
     * cuando no hay ningún webhook del que colgarse todavía (el comprador
     * acaba de volver del `return_url` de PayPal). Pregunta el estado
     * directo a la API (`showSubscriptionDetails()`, mismo endpoint que ya
     * usa `onPaymentCompleted()` para la fecha de renovación) en vez de
     * confiar en que el webhook haya llegado o vaya a llegar — hallazgo real
     * (2026-09-06): en el sandbox de PayPal, los 4 eventos de prueba
     * (`BILLING.SUBSCRIPTION.ACTIVATED`/`PAYMENT.SALE.COMPLETED`) nunca
     * llegaron a tocar la app — confirmado con `curl` directo contra la
     * misma URL pública (esa sí llegó y se logueó) y revisando
     * `storage/logs/*` sin un solo rastro de los intentos reales de PayPal.
     * No es un bug de esta app — es una falla de entrega del lado de
     * PayPal (documentada como riesgo real ~8% incluso en producción, ver
     * REQ-4.8 Escenario C) — pero significa que ESPERAR el webhook para
     * activar deja al comprador pagando sin acceso, indefinidamente. Esta
     * ruta arregla eso sin dejar de tener el webhook como mecanismo real
     * (sigue sirviendo para cancelaciones/renovaciones que pasan sin que el
     * usuario esté mirando la pantalla de vuelta).
     */
    /**
     * Sin atajo "ya está `active`, no hay nada que hacer" (versión anterior a
     * REQ-4.7.1) — ese atajo rompía el retorno de un `reviseSubscription()`:
     * una suscripción ACTIVA que vuelve de cambiar de plan sigue ACTIVA de
     * principio a fin, así que el atajo se salteaba `activate()` entero y el
     * cambio de plan nunca se reflejaba localmente. Siempre pregunta a la
     * API y deja que `activate()` decida qué cambió — el costo es una
     * llamada de más quando no cambió nada, aceptable frente al bug real.
     */
    public function syncSubscriptionStatus(Subscription $subscription): bool
    {
        if (empty($subscription->gateway_subscription_id)) {
            return $subscription->status === 'active';
        }

        $details = $this->provider()->showSubscriptionDetails($subscription->gateway_subscription_id);

        if (! is_array($details) || strtoupper($details['status'] ?? '') !== 'ACTIVE') {
            return false;
        }

        $this->activate($subscription, $details);

        return true;
    }

    /**
     * Lógica de activación compartida entre `onSubscriptionActivated()`
     * (webhook), `onPaymentCompleted()` y `syncSubscriptionStatus()`
     * (return_url síncrono, incluida la vuelta de un `reviseSubscription()`)
     * — mismo efecto sin importar por cuál de los caminos se confirmó.
     *
     * **REQ-4.7.1 — de dónde sale el plan, y el criterio upgrade/downgrade:**
     * el plan real se resuelve del lado de PayPal (`resource.plan_id`), no
     * se asume el que ya tenía la fila local — así este mismo método sirve
     * tanto para una activación inicial (coincide con lo que se pidió) como
     * para la vuelta de una revisión de plan (coincide con lo que PayPal
     * aprobó). `Subscription.plan_id` siempre se actualiza a esa verdad (es
     * el registro de "qué paga esta suscripción", útil para facturación) —
     * pero `tenants.plan_id` (lo que de verdad prende/apaga funcionalidades)
     * solo se mueve YA si el plan nuevo cuesta igual o más que el que el
     * tenant tiene activo ahora mismo. Si cuesta menos (downgrade), se
     * agenda en `scheduled_plan_id` en vez de aplicarse — `ReconcileSubscriptions`
     * (REQ-3.6) lo aplica recién cuando el período actual termina, nunca
     * antes, para no apagarle a nadie algo que ya pagó.
     *
     * @param  array<string, mixed>  $resource  `event->resource` (webhook) o la respuesta completa de `showSubscriptionDetails()` (sync) — ambos traen `plan_id`/`billing_info.next_billing_time` en la misma forma.
     */
    private function activate(Subscription $subscription, array $resource): void
    {
        $resolvedPlanId = $this->resolvePlanId($resource) ?? $subscription->plan_id;
        $tenant = Tenant::find($subscription->tenant_id);
        $currentTenantPlan = $tenant?->plan_id ? Plan::find($tenant->plan_id) : null;
        $newPlan = Plan::find($resolvedPlanId);

        // Bug real encontrado en vivo (2026-09-12): un tenant en trial (plan
        // gratis, asignado por el Wizard sin que nadie lo haya pagado) que
        // paga por primera vez un plan más barato que el del trial quedaba
        // clasificado como "downgrade" — `tenants.plan_id` se quedaba en el
        // plan del trial (nunca pagado) y el plan real pagado se agendaba
        // para dentro de un mes. `$currentTenantPlan` por sí solo no alcanza
        // para decidir esto: existe desde el Wizard, pagado o no. Un
        // downgrade de verdad solo protege algo que el tenant YA PAGÓ antes.
        //
        // **Segunda corrección sobre el mismo bug (encontrada probando el
        // downgrade real de punta a punta, 2026-09-12):** el primer intento
        // solo miraba si existía OTRA fila de `Subscription` con
        // `gateway_subscription_id` — pero un cambio de plan real
        // (`reviseSubscription()`) reutiliza la MISMA fila, nunca crea una
        // nueva. Con ese chequeo, un downgrade sobre una suscripción activa
        // de verdad (revise → aprobar) se aplicaba de inmediato en vez de
        // agendarse — el bug opuesto al original. La señal correcta es si
        // ESTA MISMA fila ya estaba `active` (con acuerdo real) antes de
        // esta llamada — eso sí prueba un pago ya en curso que proteger. Se
        // mantiene también el chequeo de otra fila, por si alguna vez esta
        // función corre sobre una fila que nunca pasó por `pending`.
        $hadPriorRealSubscription = $subscription->status === 'active'
            || Subscription::where('tenant_id', $subscription->tenant_id)
                ->where('id', '!=', $subscription->id)
                ->whereNotNull('gateway_subscription_id')
                ->exists();

        // Downgrade real: el plan nuevo cuesta MENOS que el que el tenant
        // tiene activo ahora mismo (no que el que la fila de Subscription
        // tenía guardado — lo que importa es contra qué está operando el
        // tenant hoy), Y hubo un pago real anterior que proteger.
        $isDowngrade = $hadPriorRealSubscription && $currentTenantPlan && $newPlan && $newPlan->price < $currentTenantPlan->price;

        $subscription->update([
            'status' => 'active',
            'plan_id' => $resolvedPlanId,
            // Un upgrade (o precio igual) cancela cualquier downgrade agendado
            // previo — no tendría sentido aplicarlo más tarde si el tenant ya
            // volvió a subir.
            'scheduled_plan_id' => $isDowngrade ? $resolvedPlanId : null,
            'starts_at' => $subscription->starts_at ?? now(),
            'current_period_ends_at' => $this->nextBillingTime($resource) ?? $subscription->current_period_ends_at,
        ]);

        $this->clearScheduledDeletion($subscription->tenant_id);

        if ($isDowngrade) {
            return;
        }

        // Upgrade, primera activación, o mismo precio — se refleja ya.
        Tenant::where('id', $subscription->tenant_id)->update(['plan_id' => $resolvedPlanId]);
    }

    private function resolvePlanId(array $resource): ?int
    {
        $gatewayPlanId = $resource['plan_id'] ?? null;

        return $gatewayPlanId ? Plan::where('gateway_plan_id', $gatewayPlanId)->value('id') : null;
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

        // Ver `activate()` — misma lógica que usa `syncSubscriptionStatus()`
        // (REQ-4.7.1). Nota: acá (webhook, contexto central puro) un upgrade
        // mueve `tenants.plan_id` pero NO re-provisiona `installation_modules`
        // del plan nuevo (eso es lo que `Plan::assignTo()` hace, y necesita
        // contexto de tenant) — limitación conocida, sigue documentada en
        // v1.3.0.md §3.11. `ReconcileSubscriptions` (REQ-3.6/4.7.1), que sí
        // corre en contexto de tenant vía `Tenant::run()`, es quien aplica
        // esto correctamente para los downgrades agendados.
        $this->activate($subscription, $event->resource);
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

        $invoice = SubscriptionInvoice::updateOrCreate(
            ['gateway_transaction_id' => $event->resource['id'] ?? $event->id],
            [
                'subscription_id' => $subscription->id,
                // El plan de HOY, no el que la suscripción termine teniendo
                // más adelante — ver migración `add_plan_id_to_subscription_invoices_table`.
                'plan_id' => $subscription->plan_id,
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
        $this->sendInvoiceEmail($invoice);
    }

    /**
     * Fase 4.9, REQ-4.9 — único punto de disparo del correo de factura: acá
     * es donde de verdad se confirmó un cobro (webhook `PAYMENT.SALE.COMPLETED`),
     * no en `syncSubscriptionStatus()` (esa vía nunca confirma un pago nuevo,
     * ver docblock de `activate()` — REQ-4.7.1 ya la sacó de la contabilidad
     * de facturas por el mismo motivo). El destinatario se resuelve entrando
     * en contexto de tenant (`Tenant::run()`, mismo patrón que
     * `ReconcileSubscriptions::applyScheduledDowngrades()`) porque `User` es
     * un modelo por-tenant — el rol protegido (`UserController::PROTECTED_ROLE`)
     * es la única cuenta que siempre existe, sin depender de a quién le tocó
     * pagar. `ShouldQueue` en `InvoicePaid` — si no hay ningún admin con
     * correo (no debería pasar nunca) simplemente no se encola nada.
     */
    private function sendInvoiceEmail(SubscriptionInvoice $invoice): void
    {
        $tenant = Tenant::find($invoice->tenant_id);

        if (! $tenant) {
            return;
        }

        $email = $tenant->run(fn () => User::role('admin')->first()?->email);

        if (! $email) {
            return;
        }

        Mail::to($email)->queue(new InvoicePaid($invoice, $tenant->business_name ?: $tenant->getTenantKey()));
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
