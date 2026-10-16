<?php

namespace Tests\Feature\V150;

use App\Models\Accounting\Expense;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\InventoryMovement;
use App\Models\Purchases\Vendor;
use App\Models\Sales\Sale;
use App\Models\User;
use App\Services\Accounting\Payables\ExpenseService;
use App\Services\Accounting\Treasury\FinancialAccountService;
use App\Services\Inventory\InventoryWasteService;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\PurchaseReceptionService;
use App\Services\Reports\BusinessMetrics;
use App\Services\Sales\Returns\ReturnService;
use App\Services\Sales\SalesServices\SaleService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TenantTestCase;

/**
 * Tableros de v1.5.0 Fase 6: el costo congelado en el kardex, el estado de resultados, el
 * estado del negocio, que cada reporte cargue y que el inicio respete los permisos.
 * Producto base PRD-00001: precio 150, costo 45, ITBIS 18 %.
 */
class ReportsTest extends TenantTestCase
{
    use SetsUpPosWorkspace;

    private function setUpReports(int $stock = 20): void
    {
        $this->setUpPosWorkspace(stock: $stock);
        $this->actingAs($this->cashier);
        DB::table('installation_modules')->updateOrInsert(['module_key' => 'sales.receivables'], ['is_enabled' => true]);
    }

    private function sell(float $qty): Sale
    {
        $price = (float) $this->product->price;

        return app(SaleService::class)->create([
            'client_id' => $this->walkinClientId,
            'warehouse_id' => $this->warehouse->id,
            'payment_type' => Sale::PAYMENT_CASH,
            'tipo_pago_id' => TipoPago::where('slug', 'efectivo')->value('id'),
            'total_amount' => $qty * $price,
            'discount_total' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'price' => $price]],
        ]);
    }

    private function receive(float $qty, float $cost): void
    {
        $vendor = Vendor::firstOrCreate(['business_name' => 'Proveedor'], ['payment_condition' => 'credit', 'credit_days' => 30, 'is_active' => true]);
        $orders = app(PurchaseOrderService::class);
        $order = $orders->approve($orders->create(
            ['vendor_id' => $vendor->id, 'warehouse_id' => $this->warehouse->id, 'order_date' => now()->toDateString(), 'payment_condition' => 'credit'],
            [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_cost' => $cost]],
        ));
        app(PurchaseReceptionService::class)->receive($order, ['reception_date' => now()->toDateString()],
            [$order->items()->first()->id => ['received' => $qty, 'unit_cost' => $cost]]);
    }

    public function test_el_costo_de_una_venta_no_cambia_cuando_una_compra_recalcula_el_promedio(): void
    {
        $this->setUpReports(stock: 20);
        $metrics = app(BusinessMetrics::class);
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $this->sell(4);
        $this->assertEqualsWithDelta(180.0, $metrics->costOfSales($from, $to), 0.01, '4 × 45');

        // La compra sube el promedio: (16 × 45 + 16 × 200) / 32 = 122.50
        $this->receive(16, 200);
        $this->assertEqualsWithDelta(122.5, (float) $this->product->refresh()->cost, 0.001);
        $this->assertEqualsWithDelta(180.0, $metrics->costOfSales($from, $to), 0.01, 'la venta vieja sigue a 45');

        $purchase = InventoryMovement::where('type', InventoryMovement::TYPE_PURCHASE)->firstOrFail();
        $this->assertEqualsWithDelta(200.0, (float) $purchase->unit_cost, 0.001, 'la compra entra al costo de su factura');

        // Una venta nueva sale al costo nuevo.
        $this->sell(2);
        $this->assertEqualsWithDelta(180 + 2 * 122.5, $metrics->costOfSales($from, $to), 0.01);
    }

    public function test_estado_de_resultados_suma_ventas_costo_perdidas_y_gastos(): void
    {
        $this->setUpReports(stock: 20);
        $accounts = app(FinancialAccountService::class);
        $cash = $accounts->create(['name' => 'Caja', 'type' => 'cash'], 5000, now()->toDateString());

        $sale = $this->sell(4);                                    // 600 sin ITBIS, costo 180
        app(ReturnService::class)->create($sale, [                  // devuelve 1: −150 de venta, −45 de costo
            'lines' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => 1, 'restock' => true]],
            'reason' => 'changed_mind', 'refund_method' => 'cash',
        ]);
        app(InventoryWasteService::class)->create(['warehouse_id' => $this->warehouse->id, 'waste_date' => now()->toDateString()],
            [['product_id' => $this->product->id, 'quantity' => 2, 'reason' => 'damaged']]);   // pérdida 90
        app(ExpenseService::class)->create(['category' => Expense::CATEGORY_RENT, 'concept' => 'Alquiler', 'amount' => 100,
            'expense_date' => now()->toDateString(), 'payment_condition' => 'cash', 'financial_account_id' => $cash->id]);

        $s = app(BusinessMetrics::class)->incomeStatement(now()->startOfDay(), now()->endOfDay());

        $this->assertEqualsWithDelta(600, $s['sales'], 0.01);
        $this->assertEqualsWithDelta(150, $s['returns'], 0.01);
        $this->assertEqualsWithDelta(450, $s['net_sales'], 0.01);
        $this->assertEqualsWithDelta(135, $s['cost'], 0.01);
        $this->assertEqualsWithDelta(315, $s['gross_profit'], 0.01);
        $this->assertEqualsWithDelta(90, $s['losses']['total'], 0.01);
        $this->assertEqualsWithDelta(100, $s['expenses'], 0.01);
        $this->assertEqualsWithDelta(125, $s['result'], 0.01, '315 − 90 − 100');

        // Una compra no es gasto: no cambia el resultado.
        $this->receive(5, 50);
        $again = app(BusinessMetrics::class)->incomeStatement(now()->startOfDay(), now()->endOfDay());
        $this->assertEqualsWithDelta(125, $again['result'], 0.01);
    }

    public function test_estado_del_negocio_dinero_mas_mercancia_mas_me_deben_menos_debo(): void
    {
        $this->setUpReports(stock: 10);                             // mercancía 10 × 45 = 450
        app(FinancialAccountService::class)->create(['name' => 'Banco', 'type' => 'bank'], 1000, now()->toDateString());
        $this->receive(2, 100);                                     // +2 u. al promedio (12 → 54.17) y debo 200

        $response = $this->get(route('reports.business'))->assertOk();
        $totals = $response->viewData('totals');

        $this->assertEqualsWithDelta(1000, $totals['money'], 0.01);
        $this->assertEqualsWithDelta(12 * (float) $this->product->refresh()->cost, $totals['inventory'], 0.05);
        $this->assertEqualsWithDelta(200, $totals['payables'], 0.01);
        $this->assertEqualsWithDelta($totals['money'] + $totals['inventory'] + $totals['receivables'] - $totals['payables'], $totals['net'], 0.01);
    }

    public function test_cada_reporte_y_el_inicio_cargan(): void
    {
        $this->setUpReports();
        $this->sell(2);

        $this->get(route('dashboard'))->assertOk()->assertSee('Ventas de hoy')->assertSee('Ventas por día');
        $this->get(route('reports.sales'))->assertOk()->assertSee('Por método de pago');
        $this->get(route('reports.inventory'))->assertOk()->assertSee('Valor por almacén');
        $this->get(route('reports.results'))->assertOk()->assertSee('Resultado del período');
        $this->get(route('reports.business'))->assertOk()->assertSee('Estado del negocio');
        $this->get(route('reports.sales', ['from' => 'basura', 'to' => '2026-01-01']))->assertOk();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('finance.overview.index'), 'Ingresos y Gastos se movió a Reportes');
    }

    public function test_el_inicio_solo_muestra_lo_que_el_usuario_puede_ver(): void
    {
        $this->setUpReports();
        $cashier = User::factory()->create();
        $cashier->givePermissionTo('dashboard.view');

        $this->actingAs($cashier)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Ventas de hoy')->assertDontSee('Ganancia bruta')
            ->assertDontSee('Por cobrar')->assertDontSee('Stock bajo');

        $cashier->givePermissionTo('sales.view');
        $this->actingAs($cashier->refresh())->get(route('dashboard'))->assertOk()
            ->assertSee('Ventas de hoy')->assertDontSee('Ganancia bruta');

        $this->get(route('reports.results'))->assertForbidden();
    }
}
