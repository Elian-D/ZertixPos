<?php

namespace App\Http\Controllers\Products;

use App\Filters\Categories\CategoryFilters;
use App\Http\Controllers\Controller;
use App\Models\Products\Category;
use App\Tables\CategoryTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use SoftDeletesTrait;

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', CategoryTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        $categories = (new CategoryFilters($request))
            ->apply(Category::query())
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return view('products.categories.partials.table', [
                'categories' => $categories,
                'visibleColumns' => $visibleColumns,
                'allColumns' => CategoryTable::allColumns(),
                'defaultDesktop' => CategoryTable::defaultDesktop(),
                'defaultMobile' => CategoryTable::defaultMobile(),
            ])->render();
        }

        return view('products.categories.index', array_merge(
            [
                'categories' => $categories,
                'visibleColumns' => $visibleColumns,
                'allColumns' => CategoryTable::allColumns(),
                'defaultDesktop' => CategoryTable::defaultDesktop(),
                'defaultMobile' => CategoryTable::defaultMobile(),
            ],
        ));
    }

    /**
     * Crear Tipos de Negocio
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
        ]);

        // ... (redirección)
        return redirect()
            ->route('inventory.products.categories.index')
            ->with('success', 'Categoria "'.$category->name.'" creada exitosamente.');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => ['required', 'string', Rule::unique('categories')->ignore($category->id)],
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        // Convertimos el input a boolean para comparar correctamente
        $nuevoEstado = $request->boolean('is_active');

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $nuevoEstado, // Importante usar la variable ya procesada
        ]);

        return redirect()
            ->route('inventory.products.categories.index')
            ->with('success', "Categoría \"{$category->name}\" actualizada correctamente.");
    }

    public function toggleEstado(Category $category)
    {
        $category->toggleActivo();

        return redirect()
            ->route('inventory.products.categories.index')
            ->with('success', 'Estado actualizado para "'.$category->name.'".');
    }

    // Elimina la Category si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy($id)
    {
        $Category = Category::findOrFail($id);

        return $this->destroyTrait($Category);
    }

    // Métodos abstractos que el trait necesita
    protected function getModelClass(): string
    {
        return \App\Models\Products\Category::class;
    }

    protected function getViewFolder(): string
    {
        return 'products.categories';
    }

    protected function getRouteIndex(): string
    {
        return 'inventory.products.categories.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'inventory.products.categories.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Tipo de Negocio';
    }
}
