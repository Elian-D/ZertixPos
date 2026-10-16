<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryCount;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\Warehouse;
use App\Models\Products\Product;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Toma física (v1.5.0 REQ-2.1). Ver App\Models\Inventory\InventoryCount.
 */
class InventoryCountService
{
    public function __construct(protected InventoryMovementService $movements) {}

    /**
     * Crea la toma en borrador con una línea por cada producto activo (tipo Producto)
     * del alcance, tenga o no existencia en el almacén: la existencia al iniciar se
     * congela aquí. Un producto sin fila de stock entra con 0 (si se cuentan unidades,
     * es un sobrante).
     */
    public function create(array $data): InventoryCount
    {
        return DB::transaction(function () use ($data) {
            // Una sola toma abierta por almacén para los mismos productos: dos borradores
            // que se solapan ajustarían dos veces lo mismo. El lock sobre el almacén
            // serializa dos creaciones simultáneas para que ninguna se salte la regla.
            Warehouse::whereKey($data['warehouse_id'])->lockForUpdate()->first();
            $this->assertNoOverlappingDraft((int) $data['warehouse_id'], $data['category_id'] ?? null);

            $count = InventoryCount::create([
                'warehouse_id' => $data['warehouse_id'],
                'category_id' => $data['category_id'] ?? null,
                'blind' => (bool) ($data['blind'] ?? false),
                'notes' => $data['notes'] ?? null,
                'status' => InventoryCount::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $stocks = InventoryStock::where('warehouse_id', $count->warehouse_id)->pluck('quantity', 'product_id');
            $now = now();

            $rows = Product::where('type', Product::TYPE_PRODUCT)
                ->where('is_active', true)
                ->when($count->category_id, fn ($q, $id) => $q->where('category_id', $id))
                ->orderBy('name')
                ->pluck('id')
                ->map(fn ($productId) => [
                    'inventory_count_id' => $count->id,
                    'product_id' => $productId,
                    'system_quantity' => (float) ($stocks[$productId] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($rows->isEmpty()) {
                throw new DomainException('No hay productos activos en ese alcance para contar.');
            }

            foreach ($rows->chunk(500) as $chunk) {
                DB::table('inventory_count_items')->insert($chunk->all());
            }

            return $count;
        });
    }

    /**
     * Guardar avance: solo en borrador. $counts = [item_id => cantidad|null], donde null
     * (campo vacío) deja la línea sin contar. Ids ajenos a la toma se ignoran.
     */
    public function saveProgress(InventoryCount $count, array $counts, ?string $notes = null): int
    {
        $this->assertDraft($count);

        return DB::transaction(function () use ($count, $counts, $notes) {
            $count->update(['notes' => $notes]);

            $updated = 0;
            foreach ($counts as $itemId => $qty) {
                $updated += $count->items()->whereKey($itemId)->update([
                    'counted_quantity' => ($qty === null || $qty === '') ? null : max(0, (float) $qty),
                ]);
            }

            return $updated;
        });
    }

    /**
     * Aplicar: cada línea contada con diferencia distinta de 0 genera un movimiento
     * 'count' sobre el stock ACTUAL (diferencia = contada − existencia al iniciar).
     * Todo o nada: si alguna línea dejaría el stock en negativo (se vendió durante el
     * conteo más de lo que había), no se aplica nada y se avisa qué productos.
     */
    public function apply(InventoryCount $count): InventoryCount
    {
        return DB::transaction(function () use ($count) {
            $count = InventoryCount::whereKey($count->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($count);

            $items = $count->items()->with('product:id,name,cost')->whereNotNull('counted_quantity')->get();

            if ($items->isEmpty()) {
                throw new DomainException('No hay ninguna línea contada: cuenta al menos un producto antes de aplicar.');
            }

            $stocks = InventoryStock::where('warehouse_id', $count->warehouse_id)
                ->whereIn('product_id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->pluck('quantity', 'product_id');

            $negatives = $items->filter(function ($item) use ($stocks) {
                $diff = (float) $item->counted_quantity - (float) $item->system_quantity;

                return (float) ($stocks[$item->product_id] ?? 0) + $diff < 0;
            });

            if ($negatives->isNotEmpty()) {
                throw new DomainException('No se aplicó la toma: con las ventas hechas durante el conteo, estos productos quedarían en negativo — '
                    .$negatives->pluck('product.name')->join(', ').'. Revisa su conteo.');
            }

            $total = 0;

            foreach ($items as $item) {
                $diff = round((float) $item->counted_quantity - (float) $item->system_quantity, 2);
                $cost = (float) $item->product->cost;

                $item->update(['difference' => $diff, 'unit_cost' => $cost]);
                $total += $diff * $cost;

                if ($diff != 0) {
                    $this->movements->register([
                        'warehouse_id' => $count->warehouse_id,
                        'product_id' => $item->product_id,
                        'quantity' => $diff,
                        'type' => InventoryMovement::TYPE_COUNT,
                        'description' => ($diff < 0 ? 'Faltante' : 'Sobrante')." en toma física {$count->number}",
                        'reference_type' => InventoryCount::class,
                        'reference_id' => $count->id,
                    ]);
                }
            }

            $count->update([
                'status' => InventoryCount::STATUS_APPLIED,
                'difference_value' => round($total, 2),
                'applied_by' => Auth::id(),
                'applied_at' => now(),
            ]);

            return $count;
        });
    }

    public function cancel(InventoryCount $count): InventoryCount
    {
        $this->assertDraft($count);

        $count->update([
            'status' => InventoryCount::STATUS_CANCELED,
            'canceled_by' => Auth::id(),
            'canceled_at' => now(),
        ]);

        return $count;
    }

    /**
     * "Todo el almacén" se solapa con cualquier borrador del almacén; una categoría se
     * solapa con un borrador de todo el almacén o de esa misma categoría. Dos
     * categorías distintas del mismo almacén pueden contarse a la vez.
     */
    private function assertNoOverlappingDraft(int $warehouseId, ?int $categoryId): void
    {
        $open = InventoryCount::where('warehouse_id', $warehouseId)
            ->where('status', InventoryCount::STATUS_DRAFT)
            ->when($categoryId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('category_id')->orWhere('category_id', $categoryId)))
            ->with('warehouse:id,name')
            ->first();

        if ($open) {
            throw new DomainException("Ya hay una toma en borrador que cubre estos productos en el almacén {$open->warehouse->name} ({$open->number}): termínala o cancélala antes de crear otra.");
        }
    }

    private function assertDraft(InventoryCount $count): void
    {
        if (! $count->isDraft()) {
            throw new DomainException("La toma {$count->number} ya está {$count->status_label}: no se puede modificar.");
        }
    }
}
