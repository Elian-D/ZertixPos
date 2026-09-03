<?php

namespace App\Console\Commands\Billing;

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
 */
class ReconcileSubscriptions extends Command
{
    protected $signature = 'subscriptions:reconcile';

    protected $description = 'Marca como past_due las suscripciones vencidas sin renovación registrada, arranca la retención de 90 días';

    public function handle(): int
    {
        $expired = Subscription::whereNotIn('status', ['past_due', 'cancelled'])
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->update(['status' => 'past_due']);

            $tenant = Tenant::find($subscription->tenant_id);

            if ($tenant && $tenant->scheduled_deletion_at === null) {
                $tenant->update(['scheduled_deletion_at' => now()->addDays(Subscription::RETENTION_DAYS)]);
            }
        }

        $this->info("Se marcaron {$expired->count()} suscripciones como past_due.");

        return self::SUCCESS;
    }
}
