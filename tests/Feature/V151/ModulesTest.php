<?php

namespace Tests\Feature\V151;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TenantTestCase;

/**
 * v1.5.1 Fase 3 (REQ-3.3): matriz de coherencia de módulos. Con un módulo apagado, sus
 * pantallas dan 404 y todas las demás cargan sin enlazar a ninguna de las suyas.
 */
class ModulesTest extends TenantTestCase
{
    use SetsUpPosWorkspace;

    /** Rutas que no son pantallas (descargas, JSON). */
    private const NOT_SCREENS = [
        'clients.template', 'finance.journal_entries.export', 'finance.ncf.logs.export.txt',
        'purchases.rnc-lookup', 'sales.pos.rnc-lookup', 'catalog.states', 'catalog.tax-types',
    ];

    /** Lo que se apaga junto: Compras no puede quedar encendido sin Inventario ni CxP. */
    private const OFF_TOGETHER = [
        'inventory.tracking' => ['inventory.tracking', 'purchases'],
        'sales.payables' => ['sales.payables', 'purchases'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPosWorkspace();
        $this->actingAs($this->cashier);

        foreach (array_keys(config('modules')) as $key) {
            $this->enableModule($key);
        }
    }

    /** @return array<string, Route> pantallas sin parámetros del negocio, por nombre. */
    private function screens(): array
    {
        $screens = [];
        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();
            if (in_array('GET', $route->methods()) && $route->getName() && ! str_contains($uri, '{')
                && (str_starts_with($uri, 'app/') || $uri === 'dashboard')
                && ! in_array($route->getName(), self::NOT_SCREENS, true)) {
                $screens[$route->getName()] = $route;
            }
        }

        return $screens;
    }

    private function gatedBy(Route $route, array $modules): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && in_array(substr($middleware, 7), $modules, true) && str_starts_with($middleware, 'module:')) {
                return true;
            }
        }

        return false;
    }

    public function test_sin_inventario_la_terminal_no_pide_almacen_y_la_venta_no_toca_existencias(): void
    {
        $this->enableModule('purchases', false);
        $this->enableModule('inventory.tracking', false);

        $this->get(route('sales.pos.terminals.create'))->assertOk()->assertDontSee('Almacén de Despacho');

        $this->post(route('sales.pos.terminals.store'), [
            'name' => 'ZZ Caja servicios', 'is_active' => 1, 'requires_pin' => 0,
            'allow_item_discount' => 0, 'allow_global_discount' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertNotNull(\App\Models\Sales\Pos\PosTerminal::where('name', 'ZZ Caja servicios')->value('warehouse_id'));

        // La venta de backoffice ofrece el producto aunque no tenga existencia y no pide almacén.
        \App\Models\Inventory\InventoryStock::query()->update(['quantity' => 0]);
        $this->get(route('sales.create'))->assertOk()
            ->assertViewHas('products', fn ($products) => collect($products)->contains('id', $this->product->id));

        $tipo = \App\Models\Configuration\TipoPago::where('slug', 'efectivo')->value('id');
        $this->post(route('sales.store'), [
            'client_id' => $this->walkinClientId, 'sale_date' => now()->format('Y-m-d'),
            'payment_type' => 'cash', 'tipo_pago_id' => $tipo, 'total_amount' => 150, 'discount_total' => 0,
            'cash_received' => 200, 'cash_change' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 150, 'discount_percentage' => 0, 'discount_amount' => 0]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, \App\Models\Sales\Sale::count());
        $this->assertSame(0.0, (float) \App\Models\Inventory\InventoryStock::where('product_id', $this->product->id)->sum('quantity'));

        // Ni pestaña de inventario en el producto ni tipos de documento de inventario.
        $this->get(route('inventory.products.edit', $this->product))->assertOk()->assertDontSee('name="stocks[', false);
        $this->get(route('configuration.document_types.index'))->assertOk()->assertDontSee('Toma física')->assertSee('Venta');
    }

    public function test_funcionalidades_agrupa_lo_que_hay_y_bloquea_apagar_una_dependencia(): void
    {
        $this->get(route('configuration.features'))->assertOk()
            ->assertSee('Inventario y compras')->assertSee('Al apagarlo se oculta')
            ->assertDontSee('Clientes de Ruta')->assertDontSee('Equipos en Préstamo');

        // Compras necesita Inventario: apagar Inventario con Compras encendido no se guarda.
        \Livewire\Livewire::test(\App\Livewire\Configuration\SystemFeatures::class)
            ->set('selections.inventory__tracking', false)
            ->call('save')
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'error');

        $this->assertTrue((bool) \Illuminate\Support\Facades\DB::table('installation_modules')
            ->where('module_key', 'inventory.tracking')->value('is_enabled'));
    }

    public function test_comentar_un_modulo_en_el_config_lo_apaga_aunque_la_base_diga_que_si(): void
    {
        $this->enableModule('sales.quotes');
        $this->assertTrue(module_enabled('sales.quotes'));

        config(['modules' => collect(config('modules'))->except('sales.quotes')->all()]);
        app()->forgetInstance('modules.enabled');

        $this->assertFalse(module_enabled('sales.quotes'));
        $this->get(route('clients.quotes.index'))->assertNotFound();
    }

    public static function modules(): array
    {
        return [
            'NCF' => ['sales.ncf'],
            'Inventario' => ['inventory.tracking'],
            'Cuentas por cobrar' => ['sales.receivables'],
            'Cuentas por pagar' => ['sales.payables'],
            'Compras' => ['purchases'],
            'Cotizaciones' => ['sales.quotes'],
        ];
    }

    #[DataProvider('modules')]
    public function test_con_el_modulo_apagado_sus_pantallas_dan_404_y_las_demas_no_lo_enlazan(string $module): void
    {
        $off = self::OFF_TOGETHER[$module] ?? [$module];
        foreach ($off as $key) {
            $this->enableModule($key, false);
        }

        $screens = $this->screens();

        // Lo comentado en config/modules.php (Rutas, Equipos, Contabilidad formal) está
        // apagado siempre: también da 404 y nadie lo enlaza.
        $offEverywhere = array_merge($off, ['sales.delivery_points', 'clients.field_assets', 'accounting.advanced']);
        $this->assertEmpty(array_intersect(['sales.delivery_points', 'clients.field_assets', 'accounting.advanced'], array_keys(config('modules'))));

        $hidden = collect($screens)->filter(fn (Route $r) => $this->gatedBy($r, $offEverywhere))
            ->map(fn (Route $r, string $name) => route($name));
        $this->assertNotEmpty($hidden, "{$module} no tiene pantallas gateadas");

        // Además, los show y edit que más cruzan módulos.
        $urls = collect($screens)->map(fn (Route $r, string $name) => route($name))->merge([
            'inventory.products.show' => route('inventory.products.show', $this->product),
            'inventory.products.edit' => route('inventory.products.edit', $this->product),
            'clients.show' => route('clients.show', $this->walkinClientId),
            'clients.edit' => route('clients.edit', $this->walkinClientId),
            'sales.pos.terminals.show' => route('sales.pos.terminals.show', $this->terminal),
            'sales.pos.terminals.edit' => route('sales.pos.terminals.edit', $this->terminal),
        ]);

        $problems = [];
        foreach ($urls as $name => $url) {
            $response = $this->get($url);

            if ($hidden->has($name)) {
                if ($response->status() !== 404) {
                    $problems[] = "{$name}: debía dar 404 y dio {$response->status()}";
                }

                continue;
            }

            if ($response->status() >= 400) {
                $problems[] = "{$name}: dio {$response->status()}";

                continue;
            }

            $html = $response->getContent();
            foreach ($hidden as $hiddenName => $url) {
                if (str_contains($html, 'href="'.$url.'"')) {
                    $problems[] = "{$name}: enlaza a {$hiddenName}";
                }
            }
        }

        $this->assertSame([], $problems);
    }
}
