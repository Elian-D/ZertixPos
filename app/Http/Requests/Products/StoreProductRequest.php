<?php

namespace App\Http\Requests\Products;

use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    /**
     * El radio "Sin ITBIS" envía value="" con name="tax_keys[]" (mismo array que
     * los checkboxes) para poder desmarcar el grupo ITBIS — se filtra acá antes de
     * validar, así el resto del flujo nunca ve una clave vacía.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('tax_keys')) {
            $this->merge(['tax_keys' => array_values(array_filter((array) $this->input('tax_keys')))]);
        }
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'name' => 'required|string|max:150',
            // v1.5.0 REQ-1.2: SKU opcional (ya no se autogenera) y código de barras propio.
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50|unique:products,barcode',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            // Precios: min 0 para no permitir valores negativos
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',

            // Impuestos (Fase 5, REQ-5.4) — apilable, puede venir vacío (producto sin
            // ningún impuesto asignado es un estado válido, no un error: en RD no todo
            // lleva ITBIS). Solo claves scope 'product' de config('impuestos').
            'tax_keys' => 'nullable|array',
            'tax_keys.*' => ['string', Rule::in($this->validProductTaxKeys())],

            // Flags
            'is_active' => 'boolean',
            'type' => ['required', Rule::in([Product::TYPE_PRODUCT, Product::TYPE_SERVICE])],

            // Sección Inventario (v1.5.0 REQ-1.3). Un servicio la ignora (ProductService).
            'inventory' => 'nullable|array',
            'inventory.warehouse_id' => 'nullable|exists:warehouses,id',
            'inventory.quantity' => 'nullable|numeric|min:0',
            'inventory.min_stock' => 'nullable|numeric|min:0',
            'inventory.max_stock' => 'nullable|numeric|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'inventory.warehouse_id' => 'almacén',
            'inventory.quantity' => 'cantidad inicial',
            'inventory.min_stock' => 'mínimo',
            'inventory.max_stock' => 'máximo',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->filled('name')) {
                return;
            }

            // El slug (usado como clave única real en `products`) se genera del nombre en
            // ProductService::createProduct() y nunca se valida antes de insertar — dos
            // nombres que colisionan en el mismo slug (incluida una coincidencia exacta)
            // tronaban con un UniqueConstraintViolationException crudo en vez de un error
            // de formulario legible. `withTrashed()` porque el índice único de MySQL no
            // distingue productos borrados lógicamente: un slug "libre" en la UI puede
            // seguir ocupado en la tabla real.
            $slug = Str::slug($this->name);

            if (Product::withTrashed()->where('slug', $slug)->exists()) {
                $validator->errors()->add('name', "Ya existe un producto/servicio con el nombre \"{$this->name}\" (o uno muy similar). Usa un nombre distinto.");
            }

            // Regla DGII: el ITBIS es mutuamente excluyente. La UI ya lo fuerza con
            // radio buttons, pero esta es la escritura real — si de algún modo llega
            // más de una clave del grupo 'itbis' (POST directo, DevTools), se rechaza.
            $itbisSelected = collect($this->input('tax_keys', []))->intersect($this->itbisGroupKeys());
            if ($itbisSelected->count() > 1) {
                $validator->errors()->add('tax_keys', 'Solo se puede seleccionar un tipo de ITBIS por producto (18%, 16%, Exento o Sin ITBIS).');
            }

            if ($this->input('type') === Product::TYPE_PRODUCT) {
                $inventory = (array) $this->input('inventory', []);

                if ((float) ($inventory['quantity'] ?? 0) > 0 && empty($inventory['warehouse_id'])) {
                    $validator->errors()->add('inventory.warehouse_id', 'Elige el almacén donde está la cantidad inicial.');
                }

                if (! $this->maxCoversMin($inventory['min_stock'] ?? null, $inventory['max_stock'] ?? null)) {
                    $validator->errors()->add('inventory.max_stock', 'El máximo no puede ser menor que el mínimo.');
                }
            }
        });
    }

    /**
     * Claves de config('impuestos') asignables a un producto (scope 'product') —
     * excluye 'default' (no es un impuesto, es la preselección de UI) y
     * 'propina_legal' (scope 'sale', REQ-5.7 diferida).
     */
    private function validProductTaxKeys(): array
    {
        return collect(config('impuestos'))
            ->filter(fn ($tax) => is_array($tax) && ($tax['scope'] ?? null) === 'product')
            ->keys()
            ->all();
    }

    /** Un máximo vacío es "sin tope"; con valor, no puede quedar por debajo del mínimo. */
    private function maxCoversMin($min, $max): bool
    {
        return $max === null || $max === '' || (float) $max >= (float) ($min ?? 0);
    }

    /**
     * Claves del grupo ITBIS (mutuamente excluyente — ver config/impuestos.php).
     */
    private function itbisGroupKeys(): array
    {
        return collect(config('impuestos'))
            ->filter(fn ($tax) => is_array($tax) && ($tax['group'] ?? null) === 'itbis')
            ->keys()
            ->all();
    }
}
