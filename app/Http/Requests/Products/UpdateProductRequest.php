<?php

namespace App\Http\Requests\Products;

use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit products');
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
        });
    }
}