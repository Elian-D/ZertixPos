<?php

namespace App\Http\Requests\Products;

use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.edit');
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
        // Obtenemos el ID del producto desde la ruta para la validación del SKU único
        $productId = $this->route('product')->id;

        return [
            'category_id'  => 'required|exists:categories,id',
            'unit_id'      => 'required|exists:units,id',
            'name'         => 'required|string|max:150',
            'sku'          => "nullable|string|max:50|unique:products,sku,{$productId}",
            'description'  => 'nullable|string|max:1000',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'price'        => 'required|numeric|min:0',
            'cost'         => 'required|numeric|min:0',

            // Impuestos (Fase 5, REQ-5.4) — apilable, puede venir vacío (producto sin
            // ningún impuesto asignado es un estado válido, no un error: en RD no todo
            // lleva ITBIS). Solo claves scope 'product' de config('impuestos').
            'tax_keys'     => 'nullable|array',
            'tax_keys.*'   => ['string', Rule::in($this->validProductTaxKeys())],

            'is_active'    => 'boolean',
            'is_stockable' => 'boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->filled('name')) {
                return;
            }

            // Mismo chequeo que StoreProductRequest (ver comentario ahí) — excluyendo el
            // propio producto que se está editando.
            $slug = Str::slug($this->name);
            $productId = $this->route('product')->id;

            if (Product::withTrashed()->where('slug', $slug)->where('id', '!=', $productId)->exists()) {
                $validator->errors()->add('name', "Ya existe un producto/servicio con el nombre \"{$this->name}\" (o uno muy similar). Usa un nombre distinto.");
            }

            // Regla DGII: el ITBIS es mutuamente excluyente. La UI ya lo fuerza con
            // radio buttons, pero esta es la escritura real — si de algún modo llega
            // más de una clave del grupo 'itbis' (POST directo, DevTools), se rechaza.
            $itbisSelected = collect($this->input('tax_keys', []))->intersect($this->itbisGroupKeys());
            if ($itbisSelected->count() > 1) {
                $validator->errors()->add('tax_keys', 'Solo se puede seleccionar un tipo de ITBIS por producto (18%, 16%, Exento o Sin ITBIS).');
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