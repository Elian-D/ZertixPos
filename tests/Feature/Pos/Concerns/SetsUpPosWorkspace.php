<?php

namespace Tests\Feature\Pos\Concerns;

use App\Models\Clients\Client;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosSetting;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\User;

trait SetsUpPosWorkspace
{
    protected User $cashier;

    protected Warehouse $warehouse;

    protected PosTerminal $terminal;

    protected Product $product;

    protected int $walkinClientId;

    protected function setUpPosWorkspace(int $stock = 50): void
    {
        $this->seed();

        $this->cashier = User::where('email', 'admin@local.com')->firstOrFail();
        $this->warehouse = Warehouse::firstOrFail();
        $this->product = Product::where('sku', 'PRD-00001')->firstOrFail();

        // "Consumidor Final" no siempre tiene id=1 en un entorno de test reseedeado
        // varias veces (MySQL no reinicia AUTO_INCREMENT al hacer rollback de transacción).
        $this->walkinClientId = Client::where('tax_id', '00000000000')->value('id');

        PosSetting::create([
            'allow_quick_customer_creation' => true,
            'default_walkin_customer_id' => $this->walkinClientId,
            'allow_quote_without_save' => true,
            'auto_print_receipt' => false,
            'receipt_size' => '80mm',
        ]);

        $this->terminal = PosTerminal::create([
            'name' => 'Terminal Test',
            'warehouse_id' => $this->warehouse->id,
            'is_active' => true,
            'requires_pin' => false,
        ]);

        InventoryStock::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => $stock,
        ]);
    }

    protected function openPosSession(): PosSession
    {
        return PosSession::create([
            'terminal_id' => $this->terminal->id,
            'user_id' => $this->cashier->id,
            'status' => PosSession::STATUS_OPEN,
            'opened_at' => now(),
            'opening_balance' => 0,
        ]);
    }
}
