<?php

namespace App\Console\Commands\Billing;

use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * REQ-3.6, v1.3.0 Fase 3 — no bloquea nada (eso ya lo hace
 * EnsureSubscriptionActive por fecha, REQ-3.5, sin depender de este comando).
 * Solo deja `status` explícito para que el listado del Súper Admin (REQ-5.1)
 * muestre quién está vencido sin calcularlo al vuelo contra `now()` cada vez.
 *
 * `Subscription` es landlord (conexión fija, ver el modelo) — corre una sola
 * vez contra esa tabla, no itera tenants.
 *
 * REQ-3.8 — al marcar `past_due` por primera vez, arranca la retención de 90
 * días (`tenants.scheduled_deletion_at`). Por eso ya no es un `update()`
 * masivo: hace falta iterar para tocar `tenants` una vez por cada suscripción
 * que recién cruzó la fecha, y solo si `scheduled_deletion_at` todavía está
 * vacío — si se recalculara en cada corrida, la cuenta nunca llegaría a la
 * fecha de borrado.
 *
 * **Corrección real sobre el alcance de la retención (2026-09-12, aclarado
 * por el usuario):** los 90 días + borrado son SOLO para un trial que nunca
 * llegó a pagar — no para un cliente que pagó alguna vez y dejó de hacerlo.
 * Por ley, a quien pagó hay que conservarle su base de datos (algún día
 * exportable, todavía no construido) — no se borra nunca por falta de pago,
 * solo se le bloquea el acceso (REQ-3.5, ya independiente de este comando).
 * La señal correcta NO es el `status` (un intento anterior de este archivo
 * usó `status==='cancelled'`, incorrecto: un cliente real que cancela sigue
 * siendo alguien que pagó) sino si el TENANT llegó a tener, alguna vez,
 * algún acuerdo real de PayPal (`gateway_subscription_id` no nulo en
 * cualquiera de sus filas de `Subscription`) — eso sí distingue "nunca pagó"
 * de "pagó y dejó de pagar", sin importar el `status` actual.
 */
class ReconcileSubscriptions extends Command
{
    protected $signature = 'subscriptions:reconcile';

    protected $description = 'Marca como past_due las suscripciones vencidas sin renovación registrada, arranca la retención de 90 días solo para trials nunca pagados';

    public function handle(): int
    {
        $applied = $this->applyScheduledDowngrades();

        $expired = Subscription::where('status', '!=', 'past_due')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<', now())
            ->get();

        $markedPastDue = 0;

        foreach ($expired as $subscription) {
            // `cancelled` es un final tan válido como `past_due` para
            // efectos de bloqueo (por fecha, REQ-3.5) — no hace falta
            // pisarle el status a una cancelación real ya registrada.
            if ($subscription->status !== 'cancelled') {
                $subscription->update(['status' => 'past_due']);
                $markedPastDue++;
            }

            $tenant = Tenant::find($subscription->tenant_id);

            if (! $tenant || $tenant->scheduled_deletion_at !== null) {
                continue;
            }

            if ($this->everHadRealSubscription($subscription->tenant_id)) {
                continue;
            }

            $tenant->update(['scheduled_deletion_at' => now()->addDays(Subscription::RETENTION_DAYS)]);
        }

        $this->info("Downgrades agendados aplicados: {$applied}. Suscripciones marcadas past_due: {$markedPastDue}.");

        return self::SUCCESS;
    }

    /** @see self::handle() docblock — la señal real de "alguna vez fue cliente pagando". */
    private function everHadRealSubscription(string $tenantId): bool
    {
        return Subscription::where('tenant_id', $tenantId)
            ->whereNotNull('gateway_subscription_id')
            ->exists();
    }

    /**
     * REQ-4.7.1 — la otra mitad de `PayPalGateway::activate()`: cuando un
     * downgrade se aprobó, PayPal ya está facturando el plan nuevo (más
     * barato) desde ese momento (`Subscription::plan_id` ya lo refleja,
     * ver `activate()`), pero `tenants.plan_id` (las funcionalidades reales)
     * se dejó a propósito en el plan viejo hasta que el período ya pagado
     * termine — acá es donde se cumple esa promesa, ni un día antes.
     *
     * Corre en contexto de tenant (`Tenant::run()`), a diferencia del
     * webhook de PayPal (contexto central puro) — por eso este es el único
     * lugar del sistema que puede llamar `Plan::assignTo()` para un cambio
     * de plan y que de verdad reprovisione `installation_modules`, no solo
     * `tenants.plan_id` (limitación conocida documentada en v1.3.0.md §3.11
     * para el camino del webhook).
     */
    private function applyScheduledDowngrades(): int
    {
        $due = Subscription::whereNotNull('scheduled_plan_id')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<=', now())
            ->get();

        foreach ($due as $subscription) {
            $planId = $subscription->scheduled_plan_id;
            $tenant = Tenant::find($subscription->tenant_id);

            if ($tenant && $planId) {
                $tenant->run(function () use ($planId) {
                    Plan::find($planId)?->assignTo();
                });
            }

            $subscription->update(['scheduled_plan_id' => null]);
        }

        return $due->count();
    }
}
