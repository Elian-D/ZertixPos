<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Products\Unit;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    use SoftDeletesTrait;

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Inventory\UnitTable.
     */
    public function index()
    {
        return view('products.units.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'abbreviation' => [
                'required',
                'string',
                Rule::unique('units', 'abbreviation'),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $unit = Unit::create([
            'name' => $request->name,
            'abbreviation' => $request->abbreviation,
            'is_active' => $request->is_active,
        ]);

        return redirect()
            ->route('inventory.products.units.index')
            ->with('success', 'Unidad de medida "'.$unit->name.'" creada exitosamente.');
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'abbreviation' => [
                'required',
                'string',
                Rule::unique('units', 'abbreviation')->ignore($unit->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Convertimos el input a boolean para comparar correctamente
        $nuevoEstado = $request->boolean('is_active');

        $unit->update([
            'name' => $request->name,
            'abbreviation' => $request->abbreviation,
            'is_active' => $nuevoEstado, // Importante usar la variable ya procesada
        ]);

        return redirect()
            ->route('inventory.products.units.index')
            ->with('success', "Unidad de medida \"{$unit->name}\" actualizada correctamente.");
    }

    // Elimina la Unit si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);

        return $this->destroyTrait($unit);
    }

    /* Configuración del Trait para destroy() (eliminados/restaurar/borrarDefinitivo
     * del trait ya no se usan — reemplazados por el tab "Papelera" + UnitTable
     * ::restore()/forceDelete(); toggleEstado() reemplazado por UnitTable
     * ::toggleActivo(), ver docs/analisis/politica-soft-deletes.md §6). */
    protected function getModelClass(): string
    {
        return \App\Models\Products\Unit::class;
    }

    protected function getViewFolder(): string
    {
        return 'products.units';
    }

    protected function getRouteIndex(): string
    {
        return 'inventory.products.units.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'inventory.products.units.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Unidad de medida';
    }
}
