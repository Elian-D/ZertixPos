<?php

namespace Tests\Feature\Pos;

use App\Models\Configuration\TipoPago;
use App\Models\Inventory\InventoryStock;
use App\Models\Sales\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpPosWorkspace;

    private function baseItems(float $quantity = 2, float $discountPercentage = 0, float $discountAmount = 0): array
    {
        return [
            [
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'price' => (float) $this->product->price,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
            ],
        ];
    }

    private function checkoutPayload(array $overrides = []): array
    {
        $tipoPago = TipoPago::where('slug', 'efectivo')->firstOrFail();
        $items = $overrides['items'] ?? $this->baseItems();
        $grossTotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['price']);
        $discountTotal = collect($items)->sum(fn ($item) => $item['discount_amount']);

        return array_merge([
            'client_id' => $this->walkinClientId, // Consumidor Final (ClientSeeder)
            'warehouse_id' => $this->warehouse->id,
            'sale_date' => now()->format('Y-m-d'),
            'payment_type' => Sale::PAYMENT_CASH,
            'tipo_pago_id' => $tipoPago->id,
            'total_amount' => $grossTotal,
            'discount_total' => $discountTotal,
            'apply_tax' => 0,
            'cash_received' => $grossTotal - $discountTotal,
            'cash_change' => 0,
            'items' => $items,
            'pos_terminal_id' => $this->terminal->id,
            'pos_session_id' => null, // se rellena en cada test tras abrir sesión
            'is_walkin_customer' => 1,
        ], $overrides);
    }

    public function test_successful_cash_checkout_creates_a_pos_sale_and_decrements_stock(): void
    {
        $this->setUpPosWorkspace(stock: 50);
        $session = $this->openPosSession();

        $payload = $this->checkoutPayload(['pos_session_id' => $session->id]);

        $response = $this->actingAs($this->cashier)
            ->post(route('sales.pos.checkout.store', $this->terminal), $payload);

        $response->assertRedirect(route('sales.pos.workspace', $this->terminal));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sales', [
            'pos_session_id' => $session->id,
            'pos_terminal_id' => $this->terminal->id,
            'sale_origin' => 'pos',
            'is_walkin_customer' => 1,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $stock = InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(48, $stock->quantity);
    }

    public function test_checkout_rejects_when_stock_is_insufficient(): void
    {
        $this->setUpPosWorkspace(stock: 1);
        $session = $this->openPosSession();

        $payload = $this->checkoutPayload([
            'pos_session_id' => $session->id,
            'items' => $this->baseItems(quantity: 5),
            'total_amount' => 5 * (float) $this->product->price,
            'cash_received' => 5 * (float) $this->product->price,
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(route('sales.pos.checkout.store', $this->terminal), $payload);

        $response->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_checkout_rejects_discount_beyond_the_configured_maximum(): void
    {
        $this->setUpPosWorkspace(stock: 50);
        $session = $this->openPosSession();

        // max_discount_percentage por defecto es 10%; forzamos 50%.
        $price = (float) $this->product->price;
        $discountAmount = $price * 2 * 0.5;

        $payload = $this->checkoutPayload([
            'pos_session_id' => $session->id,
            'items' => $this->baseItems(quantity: 2, discountPercentage: 50, discountAmount: $discountAmount),
            'discount_total' => $discountAmount,
            'cash_received' => ($price * 2) - $discountAmount,
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(route('sales.pos.checkout.store', $this->terminal), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('sales', 0);
    }
}
