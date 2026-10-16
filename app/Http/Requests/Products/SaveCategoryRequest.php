<?php

namespace App\Http\Requests\Products;

use App\Models\Products\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Crear y editar una categoría (v1.5.1 REQ-2.2/2.3). Dos niveles: `kind` = parent (principal,
 * solo agrupa) o child (subcategoría, recibe productos). El nombre es único dentro de su
 * principal; entre principales, único entre ellas.
 */
class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('categories.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'parent_id' => $this->input('kind') === 'child' ? $this->input('parent_id') : null,
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'kind' => ['required', Rule::in(['parent', 'child'])],
            'parent_id' => [
                'nullable', 'required_if:kind,child',
                Rule::exists('categories', 'id')->whereNull('parent_id')->whereNull('deleted_at'),
                Rule::notIn(array_filter([$category?->id])),
            ],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories', 'name')
                    ->where(fn ($q) => $this->input('parent_id')
                        ? $q->where('parent_id', $this->input('parent_id'))
                        : $q->whereNull('parent_id'))
                    ->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Category|null $category */
            $category = $this->route('category');
            if (! $category || $validator->errors()->any()) {
                return;
            }

            if ($this->input('kind') === 'child' && $category->children()->exists()) {
                $validator->errors()->add('kind', 'Tiene subcategorías: no puede pasar a ser subcategoría.');
            }

            if ($this->input('kind') === 'parent' && $category->products()->exists()) {
                $validator->errors()->add('kind', 'Tiene productos y una categoría principal no recibe productos. Muévelos a otra subcategoría primero.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['parent_id' => 'categoría principal', 'name' => 'nombre', 'description' => 'descripción'];
    }

    public function messages(): array
    {
        return [
            'parent_id.required_if' => 'Elige de qué categoría principal cuelga.',
            'parent_id.exists' => 'Elige una categoría principal.',
            'name.unique' => 'Ya existe una categoría con ese nombre aquí (revisa también la papelera).',
        ];
    }
}
