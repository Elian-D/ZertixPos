<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\SaveCategoryRequest;
use App\Models\Products\Category;
use App\Services\Products\CategoryService;
use DomainException;

/**
 * Categorías en dos niveles (v1.5.1 REQ-2.3): listado en árbol (CategoryTable) y crear/editar
 * en vista propia. La papelera, restaurar y borrar definitivo viven en CategoryTable.
 */
class CategoryController extends Controller
{
    public function __construct(private CategoryService $service) {}

    public function index()
    {
        return view('products.categories.index');
    }

    public function create()
    {
        return view('products.categories.create', ['parents' => $this->parents()]);
    }

    public function store(SaveCategoryRequest $request)
    {
        $category = $this->service->save($request->validated());

        return redirect()
            ->route('configuration.categories.index')
            ->with('success', "Categoría \"{$category->name}\" creada.");
    }

    public function edit(Category $category)
    {
        return view('products.categories.edit', [
            'category' => $category->loadCount(['children', 'products']),
            'parents' => $this->parents($category),
        ]);
    }

    public function update(SaveCategoryRequest $request, Category $category)
    {
        $this->service->save($request->validated(), $category);

        return redirect()
            ->route('configuration.categories.index')
            ->with('success', "Categoría \"{$category->name}\" actualizada.");
    }

    public function destroy(Category $category)
    {
        try {
            $this->service->trash($category);
        } catch (DomainException $e) {
            return redirect()->route('configuration.categories.index')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('configuration.categories.index')
            ->with('success', "Categoría \"{$category->name}\" enviada a la papelera.");
    }

    /** Principales a las que se puede colgar una subcategoría (sin la propia). */
    private function parents(?Category $except = null)
    {
        return Category::parents()
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }
}
