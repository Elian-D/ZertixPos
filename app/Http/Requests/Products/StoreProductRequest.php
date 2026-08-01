<?php

namespace App\Http\Requests\Products;

use App\Models\Products\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create products');
    }

    public function rules(): array
    {
        return [
            'category_id'  => 'required|exists:categories,id',
            'unit_id'      => 'required|exists:units,id',
            'name'         => 'required|string|max:150',
            'sku'          => 'nullable|string|max:50|unique:products,sku',
            'description'  => 'nullable|string|max:1000',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            // Precios: min 0 para no permitir valores negativos
            'price'        => 'required|numeric|min:0',
            'cost'         => 'required|numeric|min:0',

            // Flags
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
        });
    }
}