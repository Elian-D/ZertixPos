<?php

namespace App\Console\Commands\Billing;

use App\Models\Configuration\Plan;
use Illuminate\Console\Command;
use Srmklive\PayPal\Builders\BillingPlanBuilder;
use Srmklive\PayPal\Facades\PayPal;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

/**
 * REQ-3.13, v1.3.0 Fase 3 — aprovisiona en PayPal lo que `plans.gateway_plan_id`
 * necesita para existir antes de que PayPalGateway::createSubscription() pueda
 * cobrar algo real (ver docs/features/v1.3.0.md §3.13).
 *
 * `Plan` vive en landlord (REQ-3.4) — un solo set de filas, no por-tenant, así
 * que este comando ya no itera tenants ni necesita el mapeo slug->id que
 * tenía antes de esa migración: cada Billing Plan de PayPal es 1:1 con una
 * fila de `plans`.
 *
 * El precio que se le manda a PayPal es siempre `Plan::grossPrice()` (bruto,
 * con la comisión ya sumada), nunca `plans.price` tal cual (neto, el número
 * de marketing) — PayPal no tiene forma de "cobrar X y depositar Y", cobra
 * literalmente el precio del Billing Plan. Un Billing Plan que ya existe se
 * re-precia (`updatePlanPricing()`, no se recrea) cada corrida, así que
 * correr este comando de nuevo después de tocar `plans.price` o la tasa en
 * `config/paypal.php` alcanza para mantenerlo sincronizado.
 */
class SyncPayPalPlans extends Command
{
    protected $signature = 'paypal:sync-plans';

    protected $description = 'Crea/re-precia en PayPal (Producto + Billing Plans) lo que falte para que plans.gateway_plan_id quede lleno';

    public function handle(): int
    {
        $provider = PayPal::setProvider();
        $provider->withExceptions();
        $provider->getAccessToken();

        $productId = $this->resolveProductId($provider);

        $plans = Plan::query()->get();

        if ($plans->isEmpty()) {
            $this->warn('No hay planes todavía — nada que sincronizar.');

            return self::SUCCESS;
        }

        foreach ($plans as $plan) {
            $this->syncPlan($plan, $provider, $productId);
        }

        $this->info('Listo.');

        return self::SUCCESS;
    }

    private function syncPlan(Plan $plan, PayPalClient $provider, string $productId): void
    {
        $grossPrice = $plan->grossPrice();

        if (! empty($plan->gateway_plan_id)) {
            $this->reprice($plan, $grossPrice, $provider);

            return;
        }

        $response = BillingPlanBuilder::make()
            ->forProduct($productId)
            ->named($plan->name, (string) ($plan->description ?? ''))
            ->withCurrency($plan->currency ?? 'USD')
            ->monthly($grossPrice)
            ->create($provider);

        if (empty($response['id'])) {
            $this->error("Fallo creando el billing plan de «{$plan->name}»: ".json_encode($response));

            return;
        }

        $plan->update(['gateway_plan_id' => $response['id']]);

        $this->info("{$plan->name} -> creado {$response['id']} (\${$grossPrice} bruto, neto \${$plan->price})");
    }

    private function reprice(Plan $plan, float $grossPrice, PayPalClient $provider): void
    {
        // PayPal rechaza el PATCH con UNPROCESSABLE_ENTITY/PRICING_SCHEME_INVALID_AMOUNT
        // si el precio nuevo es idéntico al que ya tiene — confirmado con la
        // API real corriendo este comando dos veces seguidas sin cambiar nada.
        // Sin este chequeo, "sincronizar" dejaría de ser idempotente.
        $current = $provider->showPlanDetails($plan->gateway_plan_id);
        $currentPrice = (float) ($current['billing_cycles'][0]['pricing_scheme']['fixed_price']['value'] ?? null);

        if (abs($currentPrice - $grossPrice) < 0.005) {
            $this->line("{$plan->name} -> ya está en \${$grossPrice} bruto, sin cambios.");

            return;
        }

        // No se usa el helper fluido addPricingScheme()/processBillingPlanPricingUpdates()
        // del paquete: arma el payload con la forma de createPlan() (sequence/
        // frequency/tenure_type), pero el endpoint real de update-pricing-schemes
        // espera billing_cycle_sequence + pricing_scheme, nada más — confirmado
        // al recibir INVALID_REQUEST/MISSING_REQUIRED_PARAMETER de la API real
        // usando el helper. Se arma el payload correcto a mano.
        $provider->updatePlanPricing($plan->gateway_plan_id, [
            [
                'billing_cycle_sequence' => 1,
                'pricing_scheme' => [
                    'fixed_price' => [
                        'value' => number_format($grossPrice, 2, '.', ''),
                        'currency_code' => $plan->currency ?? 'USD',
                    ],
                ],
            ],
        ]);

        $this->info("{$plan->name} -> precio actualizado a \${$grossPrice} bruto (neto \${$plan->price}) en {$plan->gateway_plan_id}");
    }

    /**
     * El producto ("ZertixPOS SaaS") se crea una sola vez en toda la vida de
     * la cuenta de PayPal — no hay una tabla propia para guardarlo (sería una
     * sola fila), así que se pide guardar el ID en PAYPAL_PRODUCT_ID a mano
     * la primera vez, igual que cualquier otro secreto/ID de infraestructura.
     */
    private function resolveProductId(PayPalClient $provider): string
    {
        $productId = config('paypal.product_id');

        if (! empty($productId)) {
            return $productId;
        }

        $response = $provider->createProduct([
            'name' => 'ZertixPOS SaaS',
            'description' => 'Suscripción mensual a la plataforma ZertixPOS',
            'type' => 'SERVICE',
            'category' => 'SOFTWARE',
        ]);

        if (empty($response['id'])) {
            throw new \RuntimeException('PayPal no devolvió un id de producto: '.json_encode($response));
        }

        $this->warn("Producto creado en PayPal: {$response['id']}");
        $this->warn('Agregalo a .env como PAYPAL_PRODUCT_ID='.$response['id'].' para no crear uno nuevo la próxima corrida.');

        return $response['id'];
    }
}
