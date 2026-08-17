<?php

namespace App\Services\Products;

use App\Models\Products\Product;
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
            unset($data['tax_keys']);

            // Generar SKU si no viene
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku();
            }

            // Generar Slug
            $data['slug'] = Str::slug($data['name']);

            // Gestionar Imagen usando el Trait
            if ($image) {
                $data['image_path'] = $this->handleUpload($image, 'products');
            }

            $product = Product::create($data);

            $this->syncTaxes($product, $taxKeys);

            return $product;
        });
    }

    public function updateProduct(Product $product, array $data, $image = null): bool
    {
        return DB::transaction(function () use ($product, $data, $image) {
            $taxKeys = $data['tax_keys'] ?? [];
            unset($data['tax_keys']);

            // Si hay imagen nueva, el Trait borra la vieja automáticamente
            if ($image) {
                $data['image_path'] = $this->handleUpload($image, 'products', $product->image_path);
            }

            $updated = $product->update($data);

            $this->syncTaxes($product, $taxKeys);

            return $updated;
        });
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
     * Generador de SKU correlativo
     */
    private function generateSku(): string
    {
        $lastId = Product::withTrashed()->max('id') ?? 0;
        return 'PRD-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Ejecuta acciones sobre múltiples productos a la vez
     */
    public function performBulkAction(array $ids, string $action, $value = null): int
    {
        return DB::transaction(function () use ($ids, $action, $value) {
            $query = Product::whereIn('id', $ids);
            $count = count($ids);

            match ($action) {
                'change_active'     => $query->update(['is_active' => $value]),
                'change_stockable'  => $query->update(['is_stockable' => $value]),
                'change_category'   => $query->update(['category_id' => $value]),
                'change_unit'       => $query->update(['unit_id' => $value]),
                default => throw new \InvalidArgumentException("Acción no soportada"),
            };

            return $count;
        });
    }

    public function getActionLabel(string $action): string
    {
        return match ($action) {
            'change_active'     => 'actualizado el estado operativo',
            'change_stockable'  => 'actualizado la gestión de stock',
            'change_category'   => 'cambiado de categoría',
            'change_unit'       => 'cambiado de unidad',
            default             => 'procesado',
        };
    }
}