<?php

namespace Tests\Feature\Products;

use App\DTOs\Sales\PosContext;
use App\Models\Configuration\TipoPago;
use App\Models\Products\Product;
use App\Models\Sales\Sale;
use App\Services\Sales\SalesServices\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TestCase;

/**
 * v1.4.0 Fase 1, REQ-1.1 — regresión del rename `is_stockable` (boolean) →
 * `type` (string, constantes en el modelo). Ver docs/features/v1.4.0.md §1.1.
 */
class ProductTypeTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpPosWorkspace;

    protected function migrateFreshUsing()
    {
        return [
            '--path' => ['database/migrations/tenant'],
            '--realpath' => false,
            '--seed' => $this->shouldSeed(),
        ];
    }

    public function test_la_columna_is_stockable_ya_no_existe(): void
    {
        $this->setUpPosWorkspace();

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('products', 'is_stockable'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('products', 'type'));
    }

    public function test_is_product_e_is_service_reflejan_el_campo_type(): void
    {
        // Product no tiene factory propia (ver database/factories/) — instancias
        // en memoria, sin persistir, bastan para probar los helpers del modelo.
        $product = new Product(['type' => Product::TYPE_PRODUCT]);
        $service = new Product(['type' => Product::TYPE_SERVICE]);

        $this->assertTrue($product->isProduct());
        $this->assertFalse($product->isService());
        $this->assertTrue($service->isService());
        $this->assertFalse($service->isProduct());
    }

    public function test_scope_stockable_solo_devuelve_productos_tipo_product(): void
    {
        $this->setUpPosWorkspace();

        Product::create([
            'category_id' => $this->product->category_id,
            'unit_id' => $this->product->unit_id,
            'name' => 'Servicio de prueba',
            'slug' => 'servicio-de-prueba-test',
            'sku' => 'SRV-00002',
            'price' => 100,
            'cost' => 0,
            'is_active' => true,
            'type' => Product::TYPE_SERVICE,
        ]);

        $stockables = Product::stockable()->pluck('type')->unique();

        $this->assertTrue($stockables->every(fn ($type) => $type === Product::TYPE_PRODUCT));
        $this->assertGreaterThan(0, Product::stockable()->count());
    }

    public function test_vender_un_producto_tipo_servicio_no_genera_ni_valida_stock(): void
    {
        // Llama a SaleService::create() directo (no vía HTTP) — el checkout real del
        // TPV vive detrás de tenancy multi-dominio, fuera de alcance de esta fase.
        $this->setUpPosWorkspace(stock: 0); // el producto físico de referencia arranca en 0 a propósito
        $this->actingAs($this->cashier);

        $service = Product::create([
            'category_id' => $this->product->category_id,
            'unit_id' => $this->product->unit_id,
            'name' => 'Instalación a domicilio',
            'slug' => 'instalacion-a-domicilio-checkout-test',
            'sku' => 'SRV-00003',
            'price' => 750,
            'cost' => 0,
            'is_active' => true,
            'type' => Product::TYPE_SERVICE,
        ]);

        $session = $this->openPosSession();
        $tipoPago = TipoPago::where('slug', 'efectivo')->firstOrFail();

        $data = [
            'client_id' => $this->walkinClientId,
            'sale_date' => now()->format('Y-m-d'),
            'payment_type' => Sale::PAYMENT_CASH,
            'tipo_pago_id' => $tipoPago->id,
            'total_amount' => 750,
            'discount_total' => 0,
            'cash_received' => 750,
            'cash_change' => 0,
            'items' => [[
                'product_id' => $service->id,
                'quantity' => 1,
                'price' => 750,
                'discount_percentage' => 0,
                'discount_amount' => 0,
            ]],
        ];

        $context = PosContext::fromSession($session);

        // Si el guard de tipo fallara, esto reventaría intentando registrar un
        // InventoryMovement de salida para el servicio (o, en el flujo HTTP real,
        // StoreSaleRequest lo rechazaría con "Stock insuficiente" contra el
        // producto físico de referencia, que arrancó en 0).
        $sale = app(SaleService::class)->create($data, $context);

        $this->assertEquals(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertDatabaseMissing('inventory_stocks', [
            'product_id' => $service->id,
        ]);
    }
}
