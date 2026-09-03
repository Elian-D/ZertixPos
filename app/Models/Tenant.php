<?php

namespace App\Models;

use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Columnas reales de facturación (REQ-3.4, v1.3.0 Fase 3, migración
     * 2026_09_01_100000_add_billing_fields_to_tenants_table.php). Sin este
     * override, `VirtualColumn::getCustomColumns()` solo trae `['id']` — un
     * `$tenant->plan_id = X; $tenant->save();` habría escrito en silencio
     * dentro del JSON `data` en vez de la columna real, y cualquier
     * `Tenant::where('plan_id', ...)` no la habría encontrado ahí.
     */
    public static function getCustomColumns(): array
    {
        return array_merge(parent::getCustomColumns(), [
            'plan_id',
            'payment_gateway',
            'gateway_customer_id',
            'gateway_subscription_id',
            'business_name',
            'billing_contact_name',
            'billing_contact_email',
            'is_demo',
            'scheduled_deletion_at', // REQ-3.8
        ]);
    }

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'scheduled_deletion_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }
}
