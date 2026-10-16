<?php

namespace Tests\Feature\V150;

use App\DTOs\Sales\PosContext;
use App\Models\Accounting\Expense;
use App\Models\Accounting\FinancialAccount;
use App\Models\Accounting\FinancialMovement;
use App\Models\Accounting\Payable;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryWaste;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\Vendor;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosSetting;
use App\Models\Sales\Sale;
use App\Services\Accounting\Collection\CollectionService;
use App\Services\Accounting\Payables\ExpenseService;
use App\Services\Accounting\Payables\PayableService;
use App\Services\Accounting\Treasury\FinancialAccountService;
use App\Services\Inventory\InventoryCountService;
use App\Services\Inventory\InventoryTransferService;
use App\Services\Inventory\InventoryWasteService;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\PurchaseReceptionService;
use App\Services\Sales\Returns\ReturnService;
use App\Services\Sales\SalesServices\SaleService;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TenantTestCase;

/**
 * Flujo completo de v1.5.0 (docs/features/v1.5.0-flujo.md): inventario, venta, devolución,
 * crédito, dinero, compras y gastos conectados. Cada test arma su escenario sobre el mismo
 * fixture del TPV (producto PRD-00001: precio 150, costo 45, ITBIS 18 %).
 */
class BusinessFlowTest extends TenantTestCase
{
    use SetsUpPosWorkspace;

    protected FinancialAccount $bank;

    protected FinancialAccount $cash;

    private function setUpFlow(int $stock = 50): void
    {
        $this->setUpPosWorkspace(stock: $stock);
        $this->actingAs($this->cashier);

        $this->enableModule('sales.receivables');

        $accounts = app(FinancialAccountService::class);
        $this->bank = $accounts->create(['name' => 'Banco', 'type' => 'bank'], 1000, now()->toDateString());
        $this->cash = $accounts->create(['name' => 'Caja', 'type' => 'cash'], 500, now()->toDateString());
        PosSetting::query()->update(['default_financial_account_id' => $this->bank->id]);
    }

    private function tipo(string $slug): int
    {
        return TipoPago::where('slug', $slug)->value('id');
    }

    private function stock(?int $warehouseId = null, ?int $productId = null): float
    {
        return (float) InventoryStock::where('warehouse_id', $warehouseId ?? $this->warehouse->id)
            ->where('product_id', $productId ?? $this->product->id)->value('quantity');
    }

    private function balance(FinancialAccount $account): float
    {
        return (float) $account->refresh()->current_balance;
    }

    /** Venta de $qty unidades del producto base. Con $session es del TPV; sin él, de backoffice. */
    private function sell(float $qty, string $method = 'efectivo', ?PosSession $session = null, string $payment = Sale::PAYMENT_CASH, ?int $clientId = null): Sale
    {
        $price = (float) $this->product->price;

        return app(SaleService::class)->create([
            'client_id' => $clientId ?? $this->walkinClientId,
            'warehouse_id' => $this->warehouse->id,
            'payment_type' => $payment,
            'tipo_pago_id' => $payment === Sale::PAYMENT_CASH ? $this->tipo($method) : null,
            'total_amount' => $qty * $price,
            'discount_total' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'price' => $price]],
        ], $session ? PosContext::fromSession($session->load('terminal')) : null);
    }

    // ---------------------------------------------------------------- Dinero

    public function test_conciliar_deja_la_cuenta_en_el_saldo_real_y_depositar_suma(): void
    {
        $this->setUpFlow();
        $accounts = app(FinancialAccountService::class);

        // La cuenta queda en −250 por un pago de contado de una recepción (fromDocument).
        $accounts->move(FinancialAccountService::MOVE_WITHDRAWAL, $this->cash, 500, now()->toDateString(), null);
        app(\App\Services\Accounting\Treasury\FinancialMovementService::class)->register(
            $this->cash, FinancialMovement::DIRECTION_OUT, 250, FinancialMovement::ORIGIN_SUPPLIER_PAYMENT, now()->toDateString(), [], true);
        $this->assertSame(-250.0, $this->balance($this->cash));

        // Conciliar = "hay 2500": la cuenta queda en 2500 y la diferencia es +2750.
        $rec = $accounts->reconcile($this->cash, 2500, now()->toDateString(), 'unrecorded_income', null);
        $this->assertSame(2750.0, (float) $rec->difference);
        $this->assertSame(2500.0, $this->balance($this->cash));

        // Depositar = "entraron 2500": se suma.
        $accounts->move(FinancialAccountService::MOVE_DEPOSIT, $this->bank, 2500, now()->toDateString(), null);
        $this->assertSame(3500.0, $this->balance($this->bank));

        // Lo manual no deja en negativo.
        $this->expectException(DomainException::class);
        $accounts->move(FinancialAccountService::MOVE_WITHDRAWAL, $this->bank, 999999, now()->toDateString(), null);
    }

    public function test_conciliar_hacia_abajo_y_transferir_no_cambia_el_total(): void
    {
        $this->setUpFlow();
        $accounts = app(FinancialAccountService::class);

        $rec = $accounts->reconcile($this->bank, 950, now()->toDateString(), 'bank_fee', 'Comisión');
        $this->assertSame(-50.0, (float) $rec->difference);
        $this->assertSame(950.0, $this->balance($this->bank));

        $accounts->transfer($this->cash, $this->bank, 200, now()->toDateString(), null);
        $this->assertSame(300.0, $this->balance($this->cash));
        $this->assertSame(1150.0, $this->balance($this->bank));
    }

    // ---------------------------------------------------------------- Ventas

    public function test_tarjeta_entra_al_banco_efectivo_se_queda_en_el_turno_y_anular_lo_reversa(): void
    {
        $this->setUpFlow(stock: 50);
        $session = $this->openPosSession();

        $card = $this->sell(2, 'tarjeta', $session);
        $this->sell(1, 'efectivo', $session);

        $this->assertSame(47.0, $this->stock());
        $this->assertSame(round(1000 + (float) $card->grand_total, 2), $this->balance($this->bank));
        $this->assertSame(500.0, $this->balance($this->cash), 'el efectivo no entra a Cajas y Bancos');
        $this->assertNotNull($card->invoice ?? $card->refresh()->invoice, 'toda venta genera factura');
        $this->assertSame(2, InventoryMovement::where('reference_type', Sale::class)->where('type', InventoryMovement::TYPE_SALE)->count());

        // Turno abierto: se anula, vuelve el stock y sale del banco lo que entró.
        app(SaleService::class)->cancel($card, 'Prueba');
        $this->assertSame(49.0, $this->stock());
        $this->assertSame(1000.0, $this->balance($this->bank));
        $this->assertSame(1, InventoryMovement::where('type', InventoryMovement::TYPE_SALE_VOID)->count());
    }

    public function test_turno_cerrado_no_se_anula_se_devuelve(): void
    {
        $this->setUpFlow(stock: 50);
        $session = $this->openPosSession();
        $sale = $this->sell(3, 'efectivo', $session);
        $session->update(['status' => PosSession::STATUS_CLOSED, 'closed_at' => now()]);

        try {
            app(SaleService::class)->cancel($sale->refresh(), 'Prueba');
            $this->fail('No debió anular una venta de turno cerrado.');
        } catch (Exception $e) {
            $this->assertStringContainsString('cerrado', $e->getMessage());
        }

        // Devolución: 1 regresa al inventario y 1 dañada sale como merma.
        $item = $sale->items()->first();
        app(ReturnService::class)->create($sale, [
            'lines' => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => true]],
            'reason' => 'changed_mind',
            'refund_method' => 'cash',
        ]);
        $this->assertSame(48.0, $this->stock());

        app(ReturnService::class)->create($sale->refresh(), [
            'lines' => [['sale_item_id' => $item->id, 'quantity' => 1, 'restock' => false, 'waste' => true]],
            'reason' => 'defective',
            'refund_method' => 'cash',
        ]);
        $this->assertSame(48.0, $this->stock(), 'la dañada entra y sale como merma: el stock no cambia');
        $this->assertSame(1, InventoryWaste::count());
        $this->assertSame(500.0, $this->balance($this->cash), 'el efectivo devuelto solo se registra');
    }

    public function test_cambio_por_producto_mas_caro_crea_una_venta_nueva(): void
    {
        $this->setUpFlow(stock: 50);
        $other = Product::where('type', Product::TYPE_PRODUCT)->where('id', '!=', $this->product->id)
            ->where('price', '>', $this->product->price)->firstOrFail();
        InventoryStock::create(['warehouse_id' => $this->warehouse->id, 'product_id' => $other->id, 'quantity' => 10]);

        $sale = $this->sell(1); // backoffice: se devuelve, no se anula
        $return = app(ReturnService::class)->create($sale, [
            'lines' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => 1, 'restock' => true]],
            'reason' => 'wrong_product',
            'refund_method' => 'exchange',
            'replacement_product_id' => $other->id,
            'replacement_quantity' => 1,
        ]);

        $exchange = $return->exchangeSale;
        $this->assertNotNull($exchange);
        $this->assertSame(9.0, $this->stock(null, $other->id));
        $this->assertSame(50.0, $this->stock(), 'lo devuelto regresó');
        $paid = $exchange->payments()->with('tipoPago')->get()->mapWithKeys(fn ($p) => [$p->tipoPago->slug => (float) $p->amount]);
        $this->assertEqualsWithDelta((float) $sale->grand_total, $paid['devolucion'], 0.01);
        $this->assertGreaterThan(0, $paid['efectivo'], 'la diferencia se cobra en efectivo');
    }

    public function test_credito_crea_cxc_cobro_por_transferencia_entra_al_banco_y_devolver_baja_la_deuda(): void
    {
        $this->setUpFlow(stock: 50);
        $client = Client::factory()->create(['credit_limit' => 10000, 'balance' => 0, 'tax_id' => '101000001']);

        $sale = $this->sell(4, payment: Sale::PAYMENT_CREDIT, clientId: $client->id);
        $receivable = $sale->receivable()->first() ?? \App\Models\Accounting\Receivable::where('reference_type', Sale::class)->where('reference_id', $sale->id)->firstOrFail();
        $this->assertEqualsWithDelta((float) $sale->grand_total, (float) $receivable->current_balance, 0.01);
        $this->assertSame(1000.0, $this->balance($this->bank), 'a crédito no entra dinero');

        app(CollectionService::class)->createCollection([
            'receivable_id' => $receivable->id, 'tipo_pago_id' => $this->tipo('transferencia'),
            'amount' => 200, 'payment_date' => now()->toDateString(),
        ]);
        $this->assertSame(1200.0, $this->balance($this->bank));

        $before = (float) $receivable->refresh()->current_balance;
        app(ReturnService::class)->create($sale->refresh(), [
            'lines' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => 1, 'restock' => true]],
            'reason' => 'other',
            'refund_method' => 'cash',
        ]);
        $this->assertLessThan($before, (float) $receivable->refresh()->current_balance, 'la devolución reduce la CxC');
    }

    // ---------------------------------------------------------------- Inventario

    public function test_toma_fisica_ajusta_sin_crear_merma_y_transferencia_con_perdida(): void
    {
        $this->setUpFlow(stock: 20);
        $count = app(InventoryCountService::class)->create(['warehouse_id' => $this->warehouse->id]);
        $item = $count->items()->where('product_id', $this->product->id)->firstOrFail();
        app(InventoryCountService::class)->saveProgress($count, [$item->id => 17]);
        app(InventoryCountService::class)->apply($count->refresh());

        $this->assertSame(17.0, $this->stock());
        $this->assertSame(0, InventoryWaste::count(), 'el faltante de una toma no es merma');
        $this->assertSame(-3.0, (float) InventoryMovement::where('type', InventoryMovement::TYPE_COUNT)->value('quantity'));

        app(InventoryWasteService::class)->create(['warehouse_id' => $this->warehouse->id, 'waste_date' => now()->toDateString()],
            [['product_id' => $this->product->id, 'quantity' => 2, 'reason' => 'damaged']]);
        $this->assertSame(15.0, $this->stock());

        $to = Warehouse::where('id', '!=', $this->warehouse->id)->first() ?? Warehouse::create(['name' => 'Destino', 'is_active' => true]);
        $transfers = app(InventoryTransferService::class);
        $t = $transfers->create(['from_warehouse_id' => $this->warehouse->id, 'to_warehouse_id' => $to->id],
            [['product_id' => $this->product->id, 'quantity' => 5]]);
        $transfers->send($t);
        $this->assertSame(10.0, $this->stock(), 'sale del origen al enviar');
        $transfers->receive($t->refresh(), [$t->items()->first()->id => 4]);
        $this->assertSame(4.0, $this->stock($to->id), 'llegó 4: entra 5 y 1 sale como pérdida en tránsito');
        $this->assertSame(2, InventoryWaste::count());
    }

    // ---------------------------------------------------------------- Compras y gastos

    public function test_compra_de_contado_y_a_credito_de_punta_a_punta(): void
    {
        $this->setUpFlow(stock: 10);
        $vendor = Vendor::create(['business_name' => 'Proveedor', 'payment_condition' => 'credit', 'credit_days' => 30, 'is_active' => true]);
        $orders = app(PurchaseOrderService::class);
        $receptions = app(PurchaseReceptionService::class);
        $header = fn (string $cond) => ['vendor_id' => $vendor->id, 'warehouse_id' => $this->warehouse->id, 'order_date' => now()->toDateString(), 'payment_condition' => $cond, 'financial_account_id' => $this->bank->id];

        // Contado: 10 × 100, se reciben 6 con 1 rechazado, a costo de factura 100.
        $o = $orders->approve($orders->create($header('cash'), [['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 100]]));
        $line = $o->items()->first();
        $r = $receptions->receive($o, ['reception_date' => now()->toDateString(), 'financial_account_id' => $this->bank->id, 'rating' => 'A'],
            [$line->id => ['received' => 6, 'rejected' => 1, 'reason' => 'damaged', 'unit_cost' => 100]]);

        $this->assertSame(16.0, $this->stock());
        $this->assertEqualsWithDelta((10 * 45 + 6 * 100) / 16, (float) $this->product->refresh()->cost, 0.001, 'costo promedio');
        $this->assertSame(Payable::STATUS_PAID, $r->payable->status);
        $this->assertSame(400.0, $this->balance($this->bank));
        $this->assertSame(PurchaseOrder::STATUS_PARTIAL, $o->refresh()->status);

        // Lo que falta (3) llega a 200 y deja la cuenta en negativo: se permite.
        $receptions->receive($o, ['reception_date' => now()->toDateString(), 'financial_account_id' => $this->bank->id, 'rating' => 'C'],
            [$line->id => ['received' => 3, 'unit_cost' => 200]]);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $o->refresh()->status);
        $this->assertSame(-200.0, $this->balance($this->bank));
        $this->assertSame('B', $vendor->refresh()->rating);

        try {
            $orders->cancel($o);
            $this->fail('No debió cancelar una orden con recepciones.');
        } catch (DomainException) {
        }

        // Crédito: CxP pendiente a 30 días, pagada en dos abonos; anular uno lo devuelve.
        $this->cash->refresh();
        $c = $orders->approve($orders->create($header('credit'), [['product_id' => $this->product->id, 'quantity' => 4, 'unit_cost' => 50]]));
        $rc = $receptions->receive($c, ['reception_date' => now()->toDateString()], [$c->items()->first()->id => ['received' => 4, 'unit_cost' => 50]]);
        $payable = $rc->payable;
        $this->assertSame(Payable::STATUS_PENDING, $payable->status);
        $this->assertSame(now()->addDays(30)->toDateString(), $payable->due_date->toDateString());

        $payables = app(PayableService::class);
        $p1 = $payables->pay($payable, $this->cash, 100, now()->toDateString(), null, null);
        $payables->pay($payable->refresh(), $this->cash, 100, now()->toDateString(), null, null);
        $this->assertSame(Payable::STATUS_PAID, $payable->refresh()->status);
        $this->assertSame(300.0, $this->balance($this->cash));
        $payables->voidPayment($p1, 'Prueba');
        $this->assertSame(Payable::STATUS_PARTIAL, $payable->refresh()->status);
        $this->assertSame(400.0, $this->balance($this->cash));

        // Un pago a mano no deja la cuenta en negativo.
        $this->expectException(DomainException::class);
        $payables->pay($payable->refresh(), $this->bank, 100, now()->toDateString(), null, null);
    }

    public function test_recepcion_que_solo_rechaza_cierra_la_orden_sin_deuda(): void
    {
        $this->setUpFlow(stock: 10);
        $vendor = Vendor::create(['business_name' => 'Proveedor', 'payment_condition' => 'credit', 'credit_days' => 0, 'is_active' => true]);
        $orders = app(PurchaseOrderService::class);
        $o = $orders->approve($orders->create(['vendor_id' => $vendor->id, 'warehouse_id' => $this->warehouse->id, 'order_date' => now()->toDateString(), 'payment_condition' => 'credit'],
            [['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 10]]));

        $r = app(PurchaseReceptionService::class)->receive($o, ['reception_date' => now()->toDateString(), 'rating' => ''],
            [$o->items()->first()->id => ['received' => 0, 'rejected' => 5, 'reason' => 'not_available', 'unit_cost' => 10]]);

        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $o->refresh()->status);
        $this->assertNull($r->payable);
        $this->assertNull($r->rating, 'omitir la calificación la deja en null');
        $this->assertSame(10.0, $this->stock());
        $this->assertSame(0, Payable::count());
    }

    public function test_gasto_de_contado_sale_de_la_cuenta_y_a_credito_crea_cxp(): void
    {
        $this->setUpFlow();
        $expenses = app(ExpenseService::class);
        $base = ['category' => Expense::CATEGORY_BASIC_SERVICES, 'concept' => 'Luz', 'expense_date' => now()->toDateString()];

        $cash = $expenses->create($base + ['amount' => 300, 'payment_condition' => 'cash', 'financial_account_id' => $this->cash->id]);
        $this->assertSame(200.0, $this->balance($this->cash));
        $this->assertNull($cash->payable, 'de contado no hay CxP');

        $credit = $expenses->create($base + ['amount' => 800, 'payment_condition' => 'credit', 'due_date' => now()->addDays(10)->toDateString()]);
        $this->assertSame(Payable::STATUS_PENDING, $credit->payable->status);

        $expenses->void($cash, 'Prueba de anulación');
        $this->assertSame(500.0, $this->balance($this->cash), 'anular devuelve el dinero');

        $this->expectException(DomainException::class);
        $expenses->create($base + ['amount' => 99999, 'payment_condition' => 'cash', 'financial_account_id' => $this->cash->id]);
    }
}
