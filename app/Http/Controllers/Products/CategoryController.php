<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Products\Category;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use SoftDeletesTrait;

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\CategoryTable.
     */
    public function index()
    {
        return view('products.categories.index');
    }

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

    // Elimina la Category si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy($id)
    {
        $Category = Category::findOrFail($id);

        return $this->destroyTrait($Category);
    }

    /* Configuración del Trait para destroy() (eliminados/restaurar/borrarDefinitivo
     * del trait ya no se usan — reemplazados por el tab "Papelera" + CategoryTable
     * ::restore()/forceDelete(); toggleEstado() reemplazado por CategoryTable
     * ::toggleActivo(), ver docs/analisis/politica-soft-deletes.md §6). */
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
