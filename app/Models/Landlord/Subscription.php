<?php

namespace App\Models\Landlord;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /**
     * Fijo a `landlord`, no al default: el middleware (REQ-3.5) y la vista
     * de renovación (REQ-3.11) leen esta tabla mientras la request ya está
     * en contexto de un tenant, donde `database.default` pasa a ser `tenant`.
     */
    protected $connection = 'landlord';

    /** REQ-3.8 — duración del trial al aprovisionar (REQ-4.6), en días. */
    public const TRIAL_DAYS = 15;

    /** REQ-3.8 — retención antes del borrado automático (REQ-3.12), en días. */
    public const RETENTION_DAYS = 90;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'gateway',
        'gateway_subscription_id',
        'status',
        'starts_at',
        'current_period_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * REQ-3.8, v1.3.0 Fase 3 — el trial no es un mecanismo nuevo, es el mismo
     * modelo de fechas de REQ-3.1/3.5 con un origen distinto: sin
     * `gateway_subscription_id` (no hay suscripción real en PayPal todavía,
     * no se pidió tarjeta) y `current_period_ends_at` a 15 días. El middleware
     * de REQ-3.5 no necesita saber que es trial — la misma regla
     * (`now() > current_period_ends_at` → bloquea) ya funciona igual.
     *
     * Pensado para que lo llame REQ-4.6 ("Finalizar" del Wizard) al terminar
     * de aprovisionar un tenant — todavía no tiene ningún llamador real
     * porque REQ-4 sigue Pendiente.
     */
    public static function startTrial(Tenant $tenant, int $planId): self
    {
        return self::create([
            'tenant_id' => $tenant->getTenantKey(),
            'plan_id' => $planId,
            'gateway' => null,
            'gateway_subscription_id' => null,
            'status' => 'trialing',
            'starts_at' => now(),
            'current_period_ends_at' => now()->addDays(self::TRIAL_DAYS),
        ]);
    }
}
