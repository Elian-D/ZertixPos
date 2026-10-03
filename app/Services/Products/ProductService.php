<?php

namespace App\Services\Products;

use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryStock;
use App\Models\Products\Product;
use App\Services\Inventory\InventoryMovementService;
use App\Traits\HandleStorage; // 1. Importar el Trait
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    use HandleStorage; // 2. "Pegar" las habilidades del Trait

    public function createProduct(array $data, $image = null): Product
    {
        return DB::transaction(function () use ($data, $image) {
            // No es columna de products — se extrae antes de Product::create() y se
            // sincroniza aparte contra el pivote product_taxes (Fase 5, REQ-5.4).
            $taxKeys = $data['tax_keys'] ?? [];
            $inventory = $data['inventory'] ?? [];
            unset($data['tax_keys'], $data['inventory']);

            // Generar Slug
            $data['slug'] = Str::slug($data['name']);

            // Gestionar Imagen usando el Trait
            if ($image) {
                $data['image_path'] = $this->handleUpload($image, 'products');
            }

            $product = Product::create($data);

            $this->syncTaxes($product, $taxKeys);
            $this->createInitialStock($product, $inventory);

            return $product;
        });
    }

    public function updateProduct(Product $product, array $data, $image = null): bool
    {
        return DB::transaction(function () use ($product, $data, $image) {
            $taxKeys = $data['tax_keys'] ?? [];
            $stocks = $data['stocks'] ?? [];
            unset($data['tax_keys'], $data['stocks']);

            // Si hay imagen nueva, el Trait borra la vieja automáticamente
            if ($image) {
                $data['image_path'] = $this->handleUpload($image, 'products', $product->image_path);
            }

            $updated = $product->update($data);

            $this->syncTaxes($product, $taxKeys);
            $this->updateStockThresholds($product, $stocks);

            return $updated;
        });
    }

    /**
     * Sección Inventario al crear (v1.5.0 REQ-1.3): deja el InventoryStock con su mínimo
     * y máximo, y si hay cantidad inicial la registra como movimiento 'initial' —
     * no es una compra ni mueve dinero. Un servicio nunca tiene existencias.
     */
    private function createInitialStock(Product $product, array $inventory): void
    {
        if ($product->isService() || empty($inventory['warehouse_id'])) {
            return;
        }

        InventoryStock::create([
            'warehouse_id' => $inventory['warehouse_id'],
            'product_id' => $product->id,
            'quantity' => 0,
            'min_stock' => $inventory['min_stock'] ?? 0,
            'max_stock' => $inventory['max_stock'] ?? null,
        ]);

        $quantity = (float) ($inventory['quantity'] ?? 0);

        // Con inventory.tracking apagado ningún movimiento toca el stock (REQ-10.5).
        if ($quantity > 0 && module_enabled('inventory.tracking')) {
            app(InventoryMovementService::class)->register([
                'warehouse_id' => $inventory['warehouse_id'],
                'product_id' => $product->id,
                'quantity' => $quantity,
                'type' => InventoryMovement::TYPE_INITIAL,
                'description' => 'Inventario inicial',
                'reference_type' => Product::class,
                'reference_id' => $product->id,
            ]);
        }
    }

    /**
     * Al editar solo cambian mínimo y máximo por almacén; la existencia se ajusta con
     * una toma física. whereKey sobre la relación: un id ajeno al producto no se toca.
     */
    private function updateStockThresholds(Product $product, array $stocks): void
    {
        // REQ-1.6: al pasar a servicio (UpdateProductRequest ya exigió existencia 0)
        // se quitan sus filas de existencia; los movimientos históricos se conservan.
        if ($product->isService()) {
            $product->stocks()->delete();

            return;
        }

        foreach ($stocks as $stockId => $values) {
            $product->stocks()->whereKey($stockId)->update([
                'min_stock' => $values['min_stock'] ?? 0,
                'max_stock' => ($values['max_stock'] ?? '') === '' ? null : $values['max_stock'],
            ]);
        }
    }

    /**
     * Reemplaza los impuestos asignados al producto por los recibidos — un arreglo
     * vacío es válido (producto sin ningún impuesto asignado, ej. un servicio exento
     * o un caso donde el negocio decide no cobrar nada) y deja el pivote sin filas
     * para ese producto, no un impuesto "por defecto" forzado. `config('impuestos.default')`
     * solo preselecciona el checkbox en la UI al crear, no se aplica aquí.
     */
    private function syncTaxes(Product $product, array $taxKeys): void
    {
        DB::table('product_taxes')->where('product_id', $product->id)->delete();

        if (empty($taxKeys)) {
            return;
        }

        DB::table('product_taxes')->insert(
            collect($taxKeys)->unique()->map(fn ($key) => [
                'product_id' => $product->id,
                'tax_key' => $key,
            ])->all()
        );
    }

    /**
     * Ejecuta acciones sobre múltiples productos a la vez
     */
    public function performBulkAction(array $ids, string $action, $value = null): int
    {
        return DB::transaction(function () use ($ids, $action, $value) {
            $query = Product::whereIn('id', $ids);
            $count = count($ids);

            // REQ-1.6: misma regla que UpdateProductRequest — un producto con existencias
            // no pasa a servicio; los que no tienen, pierden sus filas de stock vacías.
            if ($action === 'change_type' && $value === Product::TYPE_SERVICE) {
                $withStock = InventoryStock::whereIn('product_id', $ids)->where('quantity', '!=', 0)->exists();
                if ($withStock) {
                    throw new \DomainException('Hay productos con existencias en la selección: llévalas a 0 antes de convertirlos en servicio.');
                }
                InventoryStock::whereIn('product_id', $ids)->delete();
            }

            match ($action) {
                'change_active' => $query->update(['is_active' => $value]),
                'change_type' => $query->update(['type' => $value]),
                'change_category' => $query->update(['category_id' => $value]),
                'change_unit' => $query->update(['unit_id' => $value]),
                default => throw new \InvalidArgumentException('Acción no soportada'),
            };

            return $count;
        });
    }

    public function getActionLabel(string $action): string
    {
        return match ($action) {
            'change_active' => 'actualizado el estado operativo',
            'change_type' => 'actualizado el tipo (producto/servicio)',
            'change_category' => 'cambiado de categoría',
            'change_unit' => 'cambiado de unidad',
            default => 'procesado',
        };
    }
}
