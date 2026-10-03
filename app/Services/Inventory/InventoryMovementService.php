<?php

namespace App\Services\Inventory;

use App\Models\Accounting\AccountingAccountRole;
use App\Models\Accounting\JournalEntry;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Products\Product;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único punto de escritura del kardex. Desde v1.5.0 (REQ-1.5) solo lo llaman
 * documentos con nombre — venta, devolución, inventario inicial y, en las fases
 * siguientes, compra, toma física, merma y transferencia —, nunca un formulario libre.
 */
class InventoryMovementService
{
    public function register(array $data): InventoryMovement
    {
        $type = $data['type'];

        if (! array_key_exists($type, InventoryMovement::getTypes())) {
            throw new InvalidArgumentException("Tipo de movimiento de inventario desconocido: {$type}.");
        }

        return DB::transaction(function () use ($data, $type) {
            $product = Product::findOrFail($data['product_id']);

            // v1.5.0 REQ-1.6: un servicio no tiene existencias. Se corta aquí (no solo en
            // la UI) porque por este método pasa todo movimiento de cualquier documento.
            if ($product->isService()) {
                throw new Exception("\"{$product->name}\" es un servicio: no maneja existencias ni puede entrar a un documento de inventario.");
            }

            $stock = InventoryStock::firstOrCreate(
                ['warehouse_id' => $data['warehouse_id'], 'product_id' => $product->id],
                ['quantity' => 0, 'min_stock' => 0]
            );

            $previousStock = (float) $stock->quantity;
            $rawQty = (float) $data['quantity'];
            $absQty = abs($rawQty);

            // El signo sale del mapa de tipos del modelo: entrada (+), salida (−) o con
            // signo tal cual llega (devolución, toma física, merma).
            $quantity = match (InventoryMovement::signFor($type)) {
                InventoryMovement::SIGN_IN => $absQty,
                InventoryMovement::SIGN_OUT => -$absQty,
                default => $rawQty,
            };

            $newStockQuantity = $previousStock + $quantity;

            if ($newStockQuantity < 0) {
                throw new Exception("Stock insuficiente de \"{$product->name}\" en el almacén.");
            }

            $movement = InventoryMovement::create([
                'warehouse_id' => $data['warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'] ?? null,
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'quantity' => $quantity,
                'type' => $type,
                'previous_stock' => $previousStock,
                'current_stock' => $newStockQuantity,
                'description' => $data['description'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
            ]);

            $stock->update(['quantity' => $newStockQuantity]);

            $this->generateAccountingEntry($movement, $product, $absQty);

            return $movement;
        });
    }

    /**
     * Ajuste manual (REQ-1.5): cada línea es un movimiento 'adjustment_in' o
     * 'adjustment_out' del almacén elegido, con el motivo y el comentario en la
     * descripción y el usuario que lo hizo. Todo o nada: si una línea falla (stock
     * insuficiente, un servicio), no se aplica ninguna. No hay reversión aparte —
     * un ajuste mal hecho se corrige con otro ajuste.
     *
     * @param  array<int, array{product_id: int, direction: 'in'|'out', quantity: float}>  $lines
     * @return \Illuminate\Support\Collection<int, InventoryMovement>
     */
    public function registerAdjustment(int $warehouseId, string $reason, string $notes, array $lines)
    {
        $reasonLabel = InventoryMovement::getAdjustmentReasons()[$reason] ?? $reason;
        $description = mb_strimwidth("{$reasonLabel}: {$notes}", 0, 255, '…');

        return DB::transaction(fn () => collect($lines)->map(fn ($line) => $this->register([
            'warehouse_id' => $warehouseId,
            'product_id' => $line['product_id'],
            'quantity' => $line['quantity'],
            'type' => $line['direction'] === 'in'
                ? InventoryMovement::TYPE_ADJUSTMENT_IN
                : InventoryMovement::TYPE_ADJUSTMENT_OUT,
            'description' => $description,
        ])));
    }

    /**
     * Asiento del costo de ventas. Solo con contabilidad avanzada encendida, y solo para
     * venta y su anulación: el resto de documentos de inventario no genera asiento en
     * v1.5.0 (Decisión 4 de docs/features/v1.5.0.md).
     */
    private function generateAccountingEntry(InventoryMovement $movement, Product $product, float $quantity): void
    {
        if (! module_enabled('accounting.advanced')) {
            return;
        }

        if (! in_array($movement->type, [InventoryMovement::TYPE_SALE, InventoryMovement::TYPE_SALE_VOID], true)) {
            return;
        }

        $totalValue = $quantity * $product->cost;
        if ($totalValue <= 0) {
            return;
        }

        $costOfSalesAccountId = AccountingAccountRole::resolve('cost_of_sales');
        $warehouseAccountId = $movement->warehouse->accounting_account_id;

        $entry = JournalEntry::create([
            'entry_date' => now(),
            'reference' => "INV-MOV-{$movement->id}",
            'description' => "{$movement->type_label}: {$product->name}",
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => Auth::id(),
        ]);

        if ($movement->type === InventoryMovement::TYPE_SALE) {
            // La salida de inventario es el COSTO, no la VENTA.
            $this->createItem($entry, $costOfSalesAccountId, $totalValue, 0, 'Costo de ventas devengado');
            $this->createItem($entry, $warehouseAccountId, 0, $totalValue, 'Salida física de inventario');
        } else {
            $this->createItem($entry, $warehouseAccountId, $totalValue, 0, 'Reingreso por anulación de venta');
            $this->createItem($entry, $costOfSalesAccountId, 0, $totalValue, 'Reversión del costo de ventas');
        }
    }

    private function createItem($entry, $accountId, $debit, $credit, $note): void
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
}
