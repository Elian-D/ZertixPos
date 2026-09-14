<?php

namespace App\Livewire\Billing;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Models\Landlord\Subscription;
use Livewire\Component;

/**
 * REQ-3.11, v1.3.0 Fase 3 — destino real del `return_url` de PayPal
 * (`PayPalGateway::createSubscription()`), reemplaza la vista estática
 * `billing.approved` que antes solo decía "esperá al webhook".
 *
 * **Por qué existe (hallazgo real, 2026-09-06):** depender solo del webhook
 * para activar dejaba al comprador pagando sin acceso — probado en el
 * sandbox de PayPal, los eventos `BILLING.SUBSCRIPTION.ACTIVATED`/
 * `PAYMENT.SALE.COMPLETED` nunca llegaron a tocar la app (confirmado con
 * `curl` directo contra la misma URL pública, que sí llegó y quedó
 * logueado — el endpoint funciona, PayPal simplemente no entregó). No es
 * algo que se arregle con más código de nuestro lado — es un riesgo
 * documentado del propio PayPal (~8% incluso en producción, ver REQ-4.8
 * Escenario C) — así que en vez de seguir esperando, este componente
 * pregunta el estado DIRECTO a la API (`PayPalGateway::syncSubscriptionStatus()`)
 * apenas el comprador vuelve, que es el único momento donde tenemos la
 * certeza de que "algo pasó" sin depender de una notificación async.
 *
 * El webhook (REQ-3.13) sigue existiendo y sigue siendo el mecanismo real
 * para todo lo que pasa CUANDO EL USUARIO NO ESTÁ MIRANDO esta pantalla
 * (renovaciones automáticas, cancelaciones desde el lado de PayPal) — acá
 * solo se adelanta la activación inicial para no dejar al comprador
 * esperando algo que quizás nunca llegue.
 */
class SubscriptionApproved extends Component
{
    public bool $activated = false;

    public bool $notFound = false;

    /** Reintentos automáticos vía wire:poll — PayPal puede tardar unos segundos en reflejar ACTIVE del otro lado, aunque el comprador ya aprobó. */
    public int $attempts = 0;

    public const MAX_ATTEMPTS = 5;

    public function mount(PaymentGatewayContract $gateway): void
    {
        $this->check($gateway);
    }

    public function retry(PaymentGatewayContract $gateway): void
    {
        $this->check($gateway);
    }

    private function check(PaymentGatewayContract $gateway): void
    {
        $gatewaySubscriptionId = request()->query('subscription_id');

        if (! $gatewaySubscriptionId) {
            $this->notFound = true;

            return;
        }

        $subscription = Subscription::where('gateway_subscription_id', $gatewaySubscriptionId)->first();

        if (! $subscription) {
            $this->notFound = true;

            return;
        }

        $this->attempts++;
        $this->activated = $gateway->syncSubscriptionStatus($subscription);
    }

    public function render()
    {
        return view('livewire.billing.subscription-approved');
    }
}
