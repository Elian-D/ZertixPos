<?php

namespace Tests\Feature\Sales;

use App\Models\Inventory\InventoryStock;
use App\Models\Products\Product;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Sale;
use App\Services\Sales\SalesServices\SaleService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TestCase;

/**
 * v1.4.0 Fase 1, REQ-1.2 — regresión del guard de Anulación. Antes de esta
 * versión, `SaleService::cancel()` no verificaba el estado de la `PosSession`
 * de la venta en ningún punto: una venta 100% en efectivo se podía anular sin
 * restricción aunque su turno ya hubiera cerrado, o aunque la venta ni
 * siquiera tuviera turno (creada desde backoffice). Ver docs/features/v1.4.0.md §1.2.
 */
class SaleCancelTest extends TestCase
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

    private function makeSale(array $overrides = []): Sale
    {
        return Sale::factory()->create(array_merge([
            'warehouse_id' => $this->warehouse->id,
        ], $overrides));
    }

    public function test_no_se_puede_anular_una_venta_creada_desde_backoffice(): void
    {
        $this->setUpPosWorkspace();

        $sale = $this->makeSale(['pos_session_id' => null]);
        $sale->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => $this->product->price,
            'subtotal' => $this->product->price,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('backoffice');

        app(SaleService::class)->cancel($sale);
    }

    public function test_no_se_puede_anular_una_venta_de_un_turno_ya_cerrado(): void
    {
        $this->setUpPosWorkspace();
        $session = $this->openPosSession();
        $session->update(['status' => PosSession::STATUS_CLOSED, 'closed_at' => now()]);

        $sale = $this->makeSale([
            'pos_session_id' => $session->id,
            'pos_terminal_id' => $this->terminal->id,
        ]);
        $sale->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => $this->product->price,
            'subtotal' => $this->product->price,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('turno de caja ya cerrado');

        app(SaleService::class)->cancel($sale);
    }

    public function test_se_puede_anular_una_venta_con_turno_abierto_y_revierte_el_stock(): void
    {
        $this->setUpPosWorkspace(stock: 50);
        $this->actingAs($this->cashier); // InventoryMovementService::register() necesita Auth::id()
        $session = $this->openPosSession();

        $sale = $this->makeSale([
            'pos_session_id' => $session->id,
            'pos_terminal_id' => $this->terminal->id,
        ]);
        $sale->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => $this->product->price,
            'subtotal' => 3 * $this->product->price,
        ]);

        // La venta se armó directo (sin pasar por SaleService::create()), así que
        // simulamos acá la salida física que esa venta real habría generado.
        InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->decrement('quantity', 3);

        app(SaleService::class)->cancel($sale);

        $sale->refresh();
        $this->assertEquals(Sale::STATUS_CANCELED, $sale->status);

        $stock = InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(50, $stock->quantity);
    }

    public function test_anular_una_venta_con_un_producto_tipo_servicio_no_reingresa_stock(): void
    {
        $this->setUpPosWorkspace();
        $this->actingAs($this->cashier);
        $session = $this->openPosSession();

        $service = Product::create([
            'category_id' => $this->product->category_id,
            'unit_id' => $this->product->unit_id,
            'name' => 'Instalación a domicilio',
            'slug' => 'instalacion-a-domicilio-test',
            'sku' => 'SRV-00001',
            'price' => 500,
            'cost' => 0,
            'is_active' => true,
            'type' => Product::TYPE_SERVICE,
        ]);

        $sale = $this->makeSale([
            'pos_session_id' => $session->id,
            'pos_terminal_id' => $this->terminal->id,
        ]);
        $sale->items()->create([
            'product_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
        ]);

        // Sin fila de InventoryStock para el servicio a propósito — si el guard de
        // tipo fallara e intentara reingresar stock, esto crearía una fila espuria
        // para algo que nunca tuvo cantidad física real.
        app(SaleService::class)->cancel($sale);

        $sale->refresh();
        $this->assertEquals(Sale::STATUS_CANCELED, $sale->status);
        $this->assertDatabaseMissing('inventory_stocks', [
            'product_id' => $service->id,
        ]);
    }
}
