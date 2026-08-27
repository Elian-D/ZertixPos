<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Clients\BusinessType;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessTypeController extends Controller
{
    use SoftDeletesTrait;

    /**
     * REQ-0.7: la tabla vive ahora en App\Livewire\App\Clients\BusinessTypeTable
     * (motor Livewire, Fase 0) — este método solo renderiza el layout.
     */
    public function index()
    {
        return view('clients.businessTypes.index');
    }

    /**
     * Crear Tipos de Negocio
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:business_types,nombre',
            'activo' => 'sometimes|boolean',
        ]);

        $negocio = BusinessType::create([
            'nombre' => $request->nombre,
            'activo' => $request->activo,
        ]);

        // ... (redirección)
        return redirect()
            ->route('clients.businessTypes.index')
            ->with('success', 'Tipo de negocio "'.$negocio->nombre.'" creado exitosamente.');
    }

    /**
     * Actualizar datos
     */
    public function update(Request $request, BusinessType $negocio)
    {
        $request->validate([
            'nombre' => 'required|string|'.Rule::unique('business_types')->ignore($negocio->id),
            'activo' => 'sometimes|boolean',
        ]);

        $data = ['nombre' => $request->nombre, 'activo' => $request->activo];

        $negocio->update($data);

        // ... (redirección)
        return redirect()
            ->route('clients.businessTypes.index')
            ->with('success', 'Tipo de negocio "'.$negocio->nombre.'" actualizado exitosamente.');
    }

    // Elimina la BusinessType si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy($id)
    {
        $businessType = BusinessType::findOrFail($id);

        return $this->destroyTrait($businessType);
    }

    // toggleEstado()/eliminadas()/restaurar()/borrarDefinitivo() ya no tienen
    // ruta — BusinessTypeTable Livewire (toggleActivo()/restore()/forceDelete())
    // las reemplazó (docs/analisis/politica-soft-deletes.md §6). Solo
    // destroyTrait() (destroy() arriba) sigue alcanzable por HTTP.

    // Métodos abstractos que el trait necesita
    protected function getModelClass(): string
    {
        return \App\Models\Clients\BusinessType::class;
    }

    protected function getViewFolder(): string
    {
        return 'clients.businessTypes';
    }

    protected function getRouteIndex(): string
    {
        return 'clients.businessTypes.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'clients.businessTypes.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Tipo de Negocio';
    }
}
