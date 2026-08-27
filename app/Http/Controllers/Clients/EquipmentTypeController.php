<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Clients\EquipmentType;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentTypeController extends Controller
{
    use SoftDeletesTrait;

    /**
     * REQ-0.7: la tabla vive ahora en App\Livewire\App\Clients\EquipmentTypeTable
     * (motor Livewire, Fase 0) — este método solo renderiza el layout.
     */
    public function index()
    {
        return view('clients.equipmentTypes.index');
    }

    /**
     * Crear Tipos de Negocio
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:equipment_types,nombre',
            'activo' => 'sometimes|boolean',
        ]);

        $equipo = EquipmentType::create([
            'nombre' => $request->nombre,
            'activo' => $request->activo,
        ]);

        // ... (redirección)
        return redirect()
            ->route('clients.equipmentTypes.index')
            ->with('success', 'Tipo de equipo "'.$equipo->nombre.'" creado exitosamente.');
    }

    /**
     * Actualizar datos
     */
    public function update(Request $request, EquipmentType $equipo)
    {
        $request->validate([
            'nombre' => 'required|string|'.Rule::unique('equipment_types')->ignore($equipo->id),
            'activo' => 'sometimes|boolean',
        ]);

        $data = ['nombre' => $request->nombre, 'activo' => $request->activo];

        $equipo->update($data);

        // ... (redirección)
        return redirect()
            ->route('clients.equipmentTypes.index')
            ->with('success', 'Tipo de equipo "'.$equipo->nombre.'" actualizado exitosamente.');
    }

    // Elimina la EquipmentType si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy(EquipmentType $equipo)
    {
        return $this->destroyTrait($equipo, null);
    }

    // toggleEstado()/eliminadas()/restaurar()/borrarDefinitivo() ya no tienen
    // ruta — EquipmentTypeTable Livewire (toggleActivo()/restore()/forceDelete())
    // las reemplazó (docs/analisis/politica-soft-deletes.md §6). Solo
    // destroyTrait() (destroy() arriba) sigue alcanzable por HTTP.

    // Métodos abstractos que el trait necesita
    protected function getModelClass(): string
    {
        return \App\Models\Clients\EquipmentType::class;
    }

    protected function getViewFolder(): string
    {
        return 'clients.equipmentTypes';
    }

    protected function getRouteIndex(): string
    {
        return 'clients.equipmentTypes.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'clients.equipmentTypes.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Tipo de Equipo';
    }
}
