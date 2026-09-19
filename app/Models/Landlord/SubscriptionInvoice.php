<?php

namespace App\Models\Landlord;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    /** Ver nota en Subscription — misma razón, misma fijación. */
    protected $connection = 'landlord';

    protected $fillable = [
        'subscription_id',
        'plan_id',
        'tenant_id',
        'amount',
        'currency',
        'gateway_transaction_id',
        'status',
        'period_start',
        'period_end',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Fase 4.9 — plan tal como estaba el día de este pago, no el actual de la
     * suscripción (que puede haber cambiado desde entonces). Ver migración
     * `add_plan_id_to_subscription_invoices_table`.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Configuration\Plan::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
