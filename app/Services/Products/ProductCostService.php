<?php

namespace App\Services\Products;

use App\Models\Inventory\InventoryStock;
use App\Models\Products\Product;
use InvalidArgumentException;

/**
 * Costo promedio ponderado del producto (v1.5.0 REQ-1.7, Decisión 2 de
 * docs/features/v1.5.0.md). El costo es global por producto, no por almacén.
 *
 *   costo nuevo = (stock total × costo actual + cantidad recibida × costo de compra)
 *                 / (stock total + cantidad recibida)
 *
 * Lo llama la recepción de compra (Fase 5) ANTES de sumar la cantidad recibida al
 * stock, para que el "stock total" sea el que había. El inventario inicial no pasa
 * por aquí: usa el costo que el dueño escribió en el producto.
 */
class ProductCostService
{
    /** Decimales del cálculo y de products.cost (se muestra a 2). */
    private const SCALE = 4;

    public function applyIncoming(Product $product, float $qty, float $unitCost): void
    {
        if ($product->isService()) {
            throw new InvalidArgumentException("\"{$product->name}\" es un servicio: no tiene costo de inventario.");
        }

        if ($qty <= 0 || $unitCost < 0) {
            throw new InvalidArgumentException('La cantidad recibida debe ser mayor que 0 y el costo no puede ser negativo.');
        }

        $product->update(['cost' => $this->weightedCost($product, $qty, $unitCost)]);
    }

    /** El cálculo solo, sin guardar (para mostrar el costo resultante antes de confirmar). */
    public function weightedCost(Product $product, float $qty, float $unitCost): float
    {
        // lockForUpdate: dos recepciones simultáneas del mismo producto no pueden leer
        // el mismo stock y pisarse el costo. Solo tiene efecto dentro de una transacción.
        $stockTotal = (float) InventoryStock::where('product_id', $product->id)->lockForUpdate()->sum('quantity');

        // Sin stock (o negativo, por datos viejos), el promedio no tiene sentido:
        // el costo nuevo es el de la compra.
        if ($stockTotal <= 0) {
            return round($unitCost, self::SCALE);
        }

        $current = (float) $product->cost;

        return round(($stockTotal * $current + $qty * $unitCost) / ($stockTotal + $qty), self::SCALE);
    }
}
