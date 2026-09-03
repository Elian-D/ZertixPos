<?php

namespace App\Models\Configuration;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de ZertixPOS, no dato propio de cada negocio (REQ-3.4, v1.3.0
 * Fase 3) — vive en landlord, no en la base de cada tenant. Fijo a
 * `landlord`, no al default: se lee/escribe desde dentro de un tenant
 * (Wizard, tarjeta de plan) donde `database.default` ya pasó a ser `tenant`.
 * Mismo patrón que `App\Models\Landlord\Subscription`/`SubscriptionInvoice`.
 */
class Plan extends Model
{
    protected $connection = 'landlord';

    protected $fillable = ['name', 'slug', 'description', 'price', 'currency', 'gateway_plan_id', 'features', 'users_limit'];

    protected $casts = [
        'features' => 'array',
    ];

    /**
     * null = sin techo (PyME/Pro). Emprendedor es 1 — pensado para un único
     * dueño/operador (REQ-05.6).
     */
    public function canCreateMoreUsers(): bool
    {
        if (is_null($this->users_limit)) {
            return true;
        }

        return User::count() < $this->users_limit;
    }

    /**
     * Precio bruto a cobrar para que, después de que PayPal descuente su
     * comisión, a ZertixPOS le lleguen los `price` netos de lista (REQ-4.5).
     * PayPal no tiene forma de "cobrar X y depositar Y" en un Billing Plan —
     * cobra literalmente el precio del plan, y de ahí deduce su comisión
     * (hallazgo real al sincronizar REQ-3.13, ver docs/features/v1.3.0.md §3.3).
     * Único punto de cálculo — usado tanto por `paypal:sync-plans` (lo que se
     * le manda a PayPal) como por la tarjeta de plan del Wizard (lo que se le
     * muestra al cliente), para que nunca queden desincronizados entre sí.
     */
    public function grossPrice(): float
    {
        $percentage = (float) config('paypal.fee_percentage');
        $fixed = (float) config('paypal.fee_fixed_usd');

        return round(((float) $this->price + $fixed) / (1 - $percentage), 2);
    }

    public function moduleKeys(): array
    {
        return DB::connection('landlord')->table('plan_module')->where('plan_id', $this->id)->pluck('module_key')->all();
    }

    public static function syncModules(int $planId, array $moduleKeys): void
    {
        DB::connection('landlord')->table('plan_module')->where('plan_id', $planId)->delete();

        DB::connection('landlord')->table('plan_module')->insert(
            collect($moduleKeys)->map(fn (string $key) => ['plan_id' => $planId, 'module_key' => $key])->all()
        );
    }

    /**
     * Copia explícita plan_module → installation_modules — no un join en vivo.
     * Si el plan cambia después (syncModules()), las instalaciones ya asignadas
     * no se ven afectadas hasta que alguien vuelva a llamar assignTo().
     *
     * Solo gestiona category === 'satellite' (REQ-10.8) — los 4 módulos flexibles
     * (inventory.tracking, sales.receivables, sales.payables, sales.quotes) no son
     * algo que el Plan venda, se inicializan en true una sola vez desde
     * InstallationModuleSeeder y de ahí en adelante solo los toca el dueño desde
     * "Funcionalidades del Sistema" — si este método los incluyera, reinvocarlo
     * pisaría cualquier toggle manual que el dueño ya haya hecho.
     *
     * `plan_id` se guarda en `tenants` (landlord), no en `ConfiguracionGeneral`
     * (REQ-3.4) — el Súper Admin necesita leerlo sin conectarse a la base de
     * cada tenant. `tenant()` resuelve al tenant activo (este método siempre
     * corre dentro de un `$tenant->run()`, ej. desde el Wizard).
     */
    public function assignTo(): void
    {
        $moduleKeys = $this->moduleKeys();

        collect(config('modules'))
            ->filter(fn (array $module) => $module['category'] === 'satellite')
            ->keys()
            ->each(function (string $key) use ($moduleKeys) {
                InstallationModule::updateOrCreate(
                    ['module_key' => $key],
                    ['is_enabled' => in_array($key, $moduleKeys, true)]
                );
            });

        tenant()?->update(['plan_id' => $this->id]);
    }
}
