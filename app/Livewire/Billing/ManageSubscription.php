<?php

namespace App\Livewire\Billing;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use App\Models\Landlord\SubscriptionInvoice;
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
 *
 * **Rediseño (2026-09-07, mockups Stitch "Resumen de Suscripción" ×3 +
 * "Facturación y Suscripción" + "Facturación y Pago PayPal"):** pasó de ser
 * solo un selector de plan a un flujo de 3 pantallas dentro del mismo
 * componente (`$view`) — resumen de estado (adapta su banner/CTA según
 * trialing/active/past_due, sin duplicar esa lógica en `billing.past-due`)
 * → elegir plan → confirmar pago con PayPal. `EnsureSubscriptionActive`
 * ahora manda acá a cualquier usuario AUTENTICADO con la suscripción
 * vencida (antes: a `billing.past-due`, que sigue existiendo solo para el
 * caso sin sesión — ver su propio docblock).
 */
class ManageSubscription extends Component
{
    public ?string $errorMessage = null;

    /** 'summary' (resumen de estado) | 'plans' (elegir/cambiar) | 'checkout' (confirmar pago PayPal). */
    public string $view = 'summary';

    /** Plan elegido en el paso 'plans', pendiente de confirmar en 'checkout'. */
    public ?int $selectedPlanId = null;

    /** REQ-4.7.1 — modal de confirmación de cancelación (resumen), `<x-modal name="cancel-subscription">`. */
    public ?string $cancelReason = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('config.billing'), 403);

        // Sin plan/suscripción real todavía (vencido sin haber pagado nunca,
        // o trial) — no tiene sentido mostrar un resumen vacío, se manda
        // directo a elegir plan.
        if (! $this->currentSubscription) {
            $this->view = 'plans';
        }
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
     * Mismas 3 categorías que REQ-3.5/REQ-3.10 ya usan en el middleware/banner
     * — replicado acá (no reutilizado de ahí, es una regla de 2 líneas) para
     * que el resumen adapte su banner/CTA sin duplicar la lógica de bloqueo
     * real, que sigue viviendo solo en `EnsureSubscriptionActive`.
     */
    public function getStatusProperty(): string
    {
        $subscription = $this->currentSubscription;
        $endsAt = $subscription?->current_period_ends_at;

        if (! $subscription || ! $endsAt || now()->gt($endsAt)) {
            return 'past_due';
        }

        return $subscription->status === 'trialing' ? 'trialing' : 'active';
    }

    /** Fin del período actual (trial o ciclo pago) — null si nunca hubo suscripción. */
    public function getPeriodEndsAtProperty(): ?\Illuminate\Support\Carbon
    {
        return $this->currentSubscription?->current_period_ends_at;
    }

    /** Días que faltan para `periodEndsAt` — 0 si ya venció o no hay fecha. */
    public function getDaysRemainingProperty(): int
    {
        $endsAt = $this->periodEndsAt;

        return $endsAt && $endsAt->isFuture() ? (int) now()->diffInDays($endsAt) : 0;
    }

    /**
     * Denominador de la barra de progreso "días restantes" — 15 en trial
     * (`Subscription::TRIAL_DAYS`), 30 en un ciclo pago (aproximado: PayPal
     * factura mensual, no guardamos la duración exacta del ciclo).
     */
    public function getTotalPeriodDaysProperty(): int
    {
        return $this->status === 'trialing' ? Subscription::TRIAL_DAYS : 30;
    }

    public function getLatestInvoiceProperty(): ?SubscriptionInvoice
    {
        return $this->currentSubscription?->invoices()->latest('id')->first();
    }

    /** Paso 'plans' — elegir/cambiar plan, siempre alcanzable desde el resumen. */
    public function showPlans(): void
    {
        $this->errorMessage = null;
        $this->view = 'plans';
    }

    public function backToSummary(): void
    {
        $this->errorMessage = null;
        $this->view = $this->currentSubscription ? 'summary' : 'plans';
    }

    /**
     * Elegir una tarjeta de plan en 'plans'. Bloquear re-seleccionar el
     * plan ya activo tiene sentido si la suscripción sigue viva (`active`/
     * `trialing`) — ahí sí sería pagar de nuevo por nada. Pero un tenant
     * `past_due`/`cancelled` puede querer **renovar exactamente el mismo
     * plan que ya tenía** — bug real reportado en vivo: el guard bloqueaba
     * ese caso también (`tenant.plan_id` no cambia solo al vencer/cancelar,
     * así que "el plan actual" seguía marcando el mismo aunque la
     * suscripción ya no sirva), dejando sin forma de renovar. El guard solo
     * aplica si de verdad hay algo vivo que perder.
     */
    public function selectPlan(int $planId): void
    {
        $isAlive = in_array($this->currentSubscription?->status, ['active', 'trialing'], true);

        if ($isAlive && $this->currentPlan?->id === $planId) {
            return;
        }

        $this->errorMessage = null;
        $this->selectedPlanId = $planId;
        $this->view = 'checkout';
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        return $this->selectedPlanId ? Plan::find($this->selectedPlanId) : null;
    }

    /**
     * REQ-4.7.1 — hay un acuerdo de PayPal YA activo del cual partir
     * (distinto de "nunca pagó" o "trial sin método de pago") → el checkout
     * tiene que revisar ESE acuerdo (`reviseSubscription()`), no crear uno
     * nuevo en paralelo. Es la causa real del bug de doble cobro reportado
     * en vivo (Plan PyME → Pro cobró $59 y $89 por separado, dos acuerdos
     * activos a la vez) — ver docs/features/v1.3.0.md REQ-4.7.1.
     *
     * **Solo si la suscripción sigue viva** (`active`/`trialing`), no
     * `past_due`/`cancelled` — corrección real sobre el primer intento:
     * `revise()` nunca dispara un cobro nuevo (el precio cambia recién en
     * el ciclo siguiente, ver docblock del contrato), así que usarlo para
     * "renovar" una suscripción vencida no resolvía nada — el estado
     * `past_due` seguía igual, sin ningún intento de cobro real. Un
     * `past_due`/`cancelled` siempre arranca un acuerdo de PayPal NUEVO
     * (`createSubscription()`), sin importar si el plan elegido es el mismo
     * que ya tenía — es la única forma de que haya un cobro de verdad.
     */
    public function getIsChangeOfPlanProperty(): bool
    {
        return (bool) $this->currentSubscription?->gateway_subscription_id
            && in_array($this->currentSubscription->status, ['active', 'trialing'], true);
    }

    /** El plan elegido en 'plans' cuesta menos que el que el tenant tiene activo ahora — mismo criterio de precio que `PayPalGateway::activate()`, para que el copy del checkout no contradiga lo que después decide el backend. */
    public function getIsDowngradeSelectionProperty(): bool
    {
        return $this->isChangeOfPlan
            && $this->currentPlan
            && $this->selectedPlan
            && $this->selectedPlan->price < $this->currentPlan->price;
    }

    /** REQ-4.7.1 — plan al que se agendó bajar, si el tenant tiene un downgrade pendiente de aplicarse al terminar el ciclo actual. */
    public function getScheduledPlanProperty(): ?Plan
    {
        return $this->currentSubscription?->scheduled_plan_id
            ? Plan::find($this->currentSubscription->scheduled_plan_id)
            : null;
    }

    /** REQ-4.7.1 — cancelación ya confirmada (PayPal no va a volver a cobrar) pero el acceso sigue activo hasta `current_period_ends_at`. */
    public function getIsCancellationScheduledProperty(): bool
    {
        return $this->currentSubscription?->status === 'cancelled'
            && $this->currentSubscription->current_period_ends_at?->isFuture();
    }

    /**
     * Redirige al comprador a PayPal para aprobar el cobro recurrente (primera
     * suscripción) o el cambio de plan (`isChangeOfPlan`, REQ-4.7.1) — mismo
     * botón de checkout para los dos casos, cada uno llama al método correcto
     * de la pasarela. La activación/confirmación real llega después: por el
     * webhook (`PayPalGateway::handleWebhook()`, REQ-3.13) o, más rápido en la
     * práctica, apenas el comprador vuelve del `return_url`
     * (`SubscriptionApproved`, REQ-3.11) — ninguno de los dos depende de que
     * este método haga algo más que redirigir.
     */
    /**
     * Fix real (2026-09-18, reportado por el usuario): nada bloqueaba a un
     * tenant `is_demo` de pagar/cambiar de plan de verdad — `EnsureSubscriptionActive`
     * lo deja pasar siempre (nunca vence, ver su propio docblock), así que
     * `billing.manage` era 100% alcanzable, y ni `subscribe()` ni
     * `confirmCancel()` tenían ningún guard. El botón deshabilitado en la
     * vista (docs/ui/buttons.md) es solo cosmético — cualquiera puede
     * disparar el método Livewire directo con un POST armado a mano a
     * `livewire/update`, sin pasar por el botón. El guard real vive acá,
     * mismo criterio que `ProfileUpdateRequest`/`UpdatePasswordRequest`
     * (REQ-3.9).
     */
    private function blockedInDemo(): bool
    {
        if (! tenant()?->is_demo) {
            return false;
        }

        $this->errorMessage = 'Esta es una cuenta de demostración compartida — no se puede pagar ni cambiar de plan de verdad.';

        return true;
    }

    public function subscribe(PaymentGatewayContract $gateway): void
    {
        $this->errorMessage = null;

        if ($this->blockedInDemo()) {
            return;
        }

        $plan = $this->selectedPlan;

        if (! $plan) {
            $this->errorMessage = 'Ese plan ya no está disponible.';
            $this->view = 'plans';

            return;
        }

        if ($this->isChangeOfPlan) {
            $this->reviseToPlan($gateway, $plan);

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

        // Bug real reportado en vivo: "Atrás" del navegador después de ser
        // redirigido a PayPal (sin llegar a aprobar) y volver a tocar
        // "Pagar" corría este método de nuevo — cada intento crea un
        // acuerdo de PayPal nuevo, en paralelo al que ya quedó pendiente.
        //
        // **Corrección sobre el primer intento (encontrado en vivo,
        // 2026-09-12):** la primera versión de este fix asumía "un intento
        // `pending` nunca se aprobó, no hay nada real que perder" y solo
        // borraba la fila local — FALSO en la práctica: se encontró un
        // acuerdo real, ACTIVO y cobrando en PayPal (`custom_id` = el tenant
        // real) sin ninguna fila local que lo rastreara, porque el
        // comprador terminó aprobando un link de intento abandonado
        // DESPUÉS de que este método ya había borrado la fila local — el
        // acuerdo de PayPal siguió vivo y huérfano, invisible para el resto
        // de la app (nunca se factura, nunca se puede cancelar desde acá).
        //
        // **Tercera corrección, mismo bug de fondo (encontrada probando la
        // renovación real de punta a punta, 2026-09-12):** el fix anterior
        // solo cubría `status='pending'` — pero renovar tras `past_due`
        // (Bug C, ya arreglado) crea un `createSubscription()` fresco
        // mientras la fila VIEJA (`past_due`) sigue teniendo su propio
        // acuerdo real, nunca cancelado en PayPal — confirmado en vivo que
        // seguía `ACTIVE` y cobrando en paralelo al nuevo. Cualquier
        // suscripción previa del tenant con un acuerdo real que no esté ya
        // `cancelled` (pending, past_due, lo que sea) se cancela del lado de
        // PayPal antes de crear la siguiente — nunca deben convivir dos
        // acuerdos reales vivos para el mismo tenant.
        Subscription::where('tenant_id', tenant()->getTenantKey())
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('gateway_subscription_id')
            ->get()
            ->each(function (Subscription $abandoned) use ($gateway) {
                try {
                    $gateway->cancelSubscription($abandoned, 'Reemplazado por un nuevo pago/renovación desde la app');
                } catch (Throwable $e) {
                    $abandoned->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                }
            });

        // Sin `current_period_ends_at` — ninguno de los dos es cierto
        // todavía (PayPal recién va a confirmar de forma asíncrona), así
        // que el middleware de REQ-3.5 sigue bloqueando hasta la activación
        // real. La fila tiene que existir ANTES de que el webhook/sync
        // llegue — la busca por `gateway_subscription_id`.
        Subscription::create([
            'tenant_id' => tenant()->getTenantKey(),
            'plan_id' => $plan->id,
            'gateway' => 'paypal',
            'gateway_subscription_id' => $result['gateway_subscription_id'],
            'status' => 'pending',
        ]);

        $this->redirect($result['approval_url']);
    }

    private function reviseToPlan(PaymentGatewayContract $gateway, Plan $plan): void
    {
        try {
            $result = $gateway->reviseSubscription(
                $this->currentSubscription,
                $plan,
                route('billing.approved'),
                route('billing.cancelled'),
            );
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->redirect($result['approval_url']);
    }

    /** REQ-4.7.1 — abre `<x-modal name="cancel-subscription">` (Alpine, `$dispatch('open-modal', ...)` desde la vista) con los datos reales de la suscripción actual. */
    public function openCancelModal(): void
    {
        $this->cancelReason = null;
        $this->dispatch('open-modal', 'cancel-subscription');
    }

    /**
     * Cancela el cobro recurrente en PayPal ya mismo (`PayPalGateway::cancelSubscription()`,
     * ya existía, nunca conectado a ninguna UI hasta REQ-4.7.1) — el acceso
     * sigue intacto hasta `current_period_ends_at` sin tocar nada más acá:
     * `EnsureSubscriptionActive` (REQ-3.5) ya solo bloquea por fecha, nunca
     * por `status`.
     */
    public function confirmCancel(PaymentGatewayContract $gateway): void
    {
        if ($this->blockedInDemo()) {
            $this->dispatch('close-modal', 'cancel-subscription');

            return;
        }

        $subscription = $this->currentSubscription;

        if (! $subscription) {
            return;
        }

        $gateway->cancelSubscription($subscription, $this->cancelReason ?: 'Cancelado por el cliente desde el panel');

        $this->dispatch('close-modal', 'cancel-subscription');
        $this->cancelReason = null;
    }

    public function render(): View
    {
        return view('livewire.billing.manage-subscription');
    }
}
