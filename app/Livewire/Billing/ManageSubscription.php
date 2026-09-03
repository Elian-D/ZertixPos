<?php

namespace App\Livewire\Billing;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * REQ-3.11, v1.3.0 Fase 3 — vista propia del tenant para pagar por primera
 * vez al salir del trial (REQ-3.8), renovar un ciclo vencido, o cambiar de
 * plan. No es el Wizard (REQ-4, corre una sola vez al aprovisionar) ni el
 * Súper Admin (REQ-5.2, solo lectura). Ya estaba referenciada sin existir
 * desde el Centro de Configuración (REQ-7.4, todavía Pendiente) — esta es la
 * vista real detrás de esa tarjeta.
 *
 * Ruta protegida por `config.billing` (nueva, REQ-3.11) — no por una ruta
 * middleware como el resto de módulos migrados, porque esta pantalla no
 * nace de un `Route::resource` sino de un componente Livewire standalone
 * (mismo criterio que InstallWizard/PermissionSelector): el permiso se
 * verifica a mano en mount().
 */
class ManageSubscription extends Component
{
    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('config.billing'), 403);
    }

    public function getCurrentSubscriptionProperty(): ?Subscription
    {
        return Subscription::where('tenant_id', tenant()->getTenantKey())
            ->latest('id')
            ->first();
    }

    public function getCurrentPlanProperty(): ?Plan
    {
        return tenant()?->plan;
    }

    public function getPlansProperty()
    {
        return Plan::orderBy('price')->get();
    }

    /**
     * Redirige al comprador a PayPal para aprobar el cobro recurrente. La
     * activación real (status pasa a active, current_period_ends_at se
     * completa) llega después, vía el webhook que procesa
     * PayPalGateway::handleWebhook() (REQ-3.13) — pero ese webhook busca la
     * `Subscription` por `gateway_subscription_id`
     * (`PayPalGateway::findSubscription()`), así que la fila tiene que existir
     * ANTES de que el webhook llegue. Se crea acá mismo, en `pending`
     * (ni `active` ni `trialing` — ninguno de los dos es cierto todavía,
     * PayPal recién va a confirmar la aprobación de forma asíncrona) y sin
     * `current_period_ends_at`, así que el middleware de REQ-3.5 sigue
     * bloqueando hasta que la activación real llegue.
     */
    public function subscribe(int $planId, PaymentGatewayContract $gateway): void
    {
        $this->errorMessage = null;

        $plan = Plan::find($planId);

        if (! $plan) {
            $this->errorMessage = 'Ese plan ya no está disponible.';

            return;
        }

        try {
            $result = $gateway->createSubscription(
                tenant(),
                $plan,
                auth()->user()->name,
                auth()->user()->email,
                route('billing.approved'),
                route('billing.cancelled'),
            );
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        Subscription::create([
            'tenant_id' => tenant()->getTenantKey(),
            'plan_id' => $plan->id,
            'gateway' => 'paypal',
            'gateway_subscription_id' => $result['gateway_subscription_id'],
            'status' => 'pending',
        ]);

        $this->redirect($result['approval_url']);
    }

    public function render(): View
    {
        return view('livewire.billing.manage-subscription');
    }
}
