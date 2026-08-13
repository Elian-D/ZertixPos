<?php

namespace App\Services\Inventory;

use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\JournalEntry;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
    public function register(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // 1. Obtener Stock y Producto (necesitamos el costo para el asiento)
            $stock = InventoryStock::firstOrCreate(
                ['warehouse_id' => $data['warehouse_id'], 'product_id' => $data['product_id']],
                ['quantity' => 0, 'min_stock' => 0]
            );
            $product = \App\Models\Products\Product::findOrFail($data['product_id']);

            $previousStock = $stock->quantity;
            $type = $data['type'];
            $rawQty = $data['quantity'];
            $absQty = abs($rawQty);

            $isNegativeOperation = in_array($type, [
                InventoryMovement::TYPE_OUTPUT,
                InventoryMovement::TYPE_TRANSFER,
            ]);

            // 2. Cálculo de Stock Físico
            if ($type === InventoryMovement::TYPE_ADJUSTMENT) {
                $newStockQuantity = $previousStock + $rawQty;
            } else {
                $newStockQuantity = $isNegativeOperation ? $previousStock - $absQty : $previousStock + $absQty;
            }

            if ($newStockQuantity < 0) {
                throw new Exception('Stock insuficiente en el almacén de origen.');
            }

            // 3. Crear el Movimiento Físico
            $movement = InventoryMovement::create([
                'warehouse_id' => $data['warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'] ?? null,
                'product_id' => $data['product_id'],
                'user_id' => Auth::id(),
                'quantity' => ($type === InventoryMovement::TYPE_ADJUSTMENT) ? $rawQty : ($isNegativeOperation ? -$absQty : $absQty),
                'type' => $type,
                'previous_stock' => $previousStock,
                'current_stock' => $newStockQuantity,
                'description' => $data['description'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
            ]);

            $stock->update(['quantity' => $newStockQuantity]);

            // 4. GENERAR ASIENTO CONTABLE
            $this->generateAccountingEntry($movement, $product, $absQty);

            // 5. SI ES TRANSFERENCIA, MANEJAR ESPEJO (Físico ya está bien, la contabilidad se maneja en el paso 4)
            if ($type === InventoryMovement::TYPE_TRANSFER && isset($data['to_warehouse_id'])) {
                $this->registerTransferEntry($movement, $data);
            }

            return $movement;
        });
    }

    private function generateAccountingEntry(InventoryMovement $movement, $product, $quantity)
    {
        // Sin contabilidad avanzada, un movimiento de inventario no necesita asiento —
        // es una operación base (Sale/InventoryMovement bastan para reportar).
        if (! module_enabled('accounting.advanced')) {
            return;
        }

        $totalValue = $quantity * $product->cost;
        if ($totalValue <= 0) {
            return;
        }

        // Cuentas resueltas por rol (antes hardcodeadas por código)
        $cashAccountId = AccountingAccountRole::resolve('cash_default');
        $costOfSalesAccountId = AccountingAccountRole::resolve('cost_of_sales');
        $productionAccountId = AccountingAccountRole::resolve('production_cost');

        $entry = JournalEntry::create([
            'entry_date' => now(),
            'reference' => "INV-MOV-{$movement->id}",
            'description' => "{$movement->type_label}: {$product->name}",
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => Auth::id(),
        ]);

        switch ($movement->type) {
            case InventoryMovement::TYPE_INPUT:
                // Diferenciamos si es Compra o Producción
                if ($movement->reference_type === 'Production') {
                    $this->createItem($entry, $movement->warehouse->accounting_account_id, $totalValue, 0, 'Entrada por producción');
                    $this->createItem($entry, $productionAccountId, 0, $totalValue, 'Costo de producción propia');
                } else {
                    $this->createItem($entry, $movement->warehouse->accounting_account_id, $totalValue, 0, 'Compra de mercancía');
                    $this->createItem($entry, $cashAccountId, 0, $totalValue, 'Pago en efectivo');
                }
                break;

            case InventoryMovement::TYPE_OUTPUT:
                // La salida de inventario es el COSTO, no la VENTA.
                $this->createItem($entry, $costOfSalesAccountId, $totalValue, 0, 'Costo de ventas devengado');
                $this->createItem($entry, $movement->warehouse->accounting_account_id, 0, $totalValue, 'Salida física de inventario');
                break;

            case InventoryMovement::TYPE_TRANSFER:
                // Reclasificación entre almacenes
                $this->createItem($entry, $movement->toWarehouse->accounting_account_id, $totalValue, 0, 'Entrada por traspaso');
                $this->createItem($entry, $movement->warehouse->accounting_account_id, 0, $totalValue, 'Salida por traspaso');
                break;

            case InventoryMovement::TYPE_ADJUSTMENT:
                if ($movement->quantity > 0) {
                    // --- CAMBIO AQUÍ ---
                    // Si el ajuste viene de una anulación de Venta, afectamos Costo de Ventas
                    $contraAccount = ($movement->reference_type === \App\Models\Sales\Sale::class)
                        ? $costOfSalesAccountId
                        : $productionAccountId;

                    $this->createItem($entry, $movement->warehouse->accounting_account_id, $totalValue, 0, 'Reingreso de inventario');
                    $this->createItem($entry, $contraAccount, 0, $totalValue, 'Reversión de costo/ajuste positivo');
                } else {
                    $this->createItem($entry, $costOfSalesAccountId, $totalValue, 0, 'Gasto por merma o pérdida');
                    $this->createItem($entry, $movement->warehouse->accounting_account_id, 0, $totalValue, 'Baja por merma');
                }
                break;
        }
    }

    private function createItem($entry, $accountId, $debit, $credit, $note)
    {
        if (! $accountId) {
            throw new Exception('Error Contable: Almacén o Contrapartida no tiene cuenta asignada.');
        }

        $entry->items()->create([
            'accounting_account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'note' => $note,
        ]);
    }

    private function registerTransferEntry(InventoryMovement $parentMovement, array $data)
    {
        $destStock = InventoryStock::firstOrCreate(
            ['warehouse_id' => $data['to_warehouse_id'], 'product_id' => $data['product_id']],
            ['quantity' => 0, 'min_stock' => 0]
        );

        $prevDestStock = $destStock->quantity;
        $qty = abs($data['quantity']);
        $newDestStock = $prevDestStock + $qty;

        InventoryMovement::create([
            'warehouse_id' => $data['to_warehouse_id'],
            'product_id' => $data['product_id'],
            'user_id' => Auth::id(),
            'quantity' => $qty,
            'type' => InventoryMovement::TYPE_TRANSFER,
            'previous_stock' => $prevDestStock,
            'current_stock' => $newDestStock,
            'description' => 'Entrada por transferencia desde: '.$parentMovement->warehouse->name,
            'reference_type' => get_class($parentMovement),
            'reference_id' => $parentMovement->id,
        ]);

        $destStock->update(['quantity' => $newDestStock]);
    }
}
