<?php

namespace App\Services\Products;

use App\Models\Products\Category;
use DomainException;

/**
 * Categorías en dos niveles (v1.5.1 REQ-2.2). Las reglas de crear/editar están en
 * SaveCategoryRequest; aquí, mandar a la papelera y restaurar.
 */
class CategoryService
{
    public function save(array $data, ?Category $category = null): Category
    {
        $fields = collect($data)->only(['parent_id', 'name', 'description', 'is_active'])->all();

        if ($category) {
            $category->update($fields);

            return $category;
        }

        return Category::create($fields);
    }

    /** Una principal con subcategorías, o una subcategoría con productos, no va a la papelera. */
    public function trash(Category $category): void
    {
        $found = collect($category->trashBlockers())->filter()
            ->map(function (int $count, string $label) {
                [$one, $many] = explode('|', $label);

                return $count.' '.($count === 1 ? $one : $many);
            });

        if ($found->isNotEmpty()) {
            $move = $category->isParent() ? 'Mueve o borra primero sus subcategorías.' : 'Mueve primero sus productos a otra subcategoría.';
            throw new DomainException("La categoría \"{$category->name}\" tiene {$found->join(', ', ' y ')}. {$move}");
        }

        $category->delete();
    }

    /** Una subcategoría no se restaura si su principal sigue en la papelera. */
    public function restore(Category $category): void
    {
        if ($category->parent_id && Category::onlyTrashed()->whereKey($category->parent_id)->exists()) {
            $parent = Category::withTrashed()->find($category->parent_id)->name;
            throw new DomainException("Restaura primero la categoría principal \"{$parent}\".");
        }

        $category->restore();
    }
}
