<?php

namespace App\Http\Controllers\Clients;

use App\Filters\EquipmentTypes\EquipmentTypesFilters;
use App\Http\Controllers\Controller;
use App\Models\Clients\EquipmentType;
use App\Tables\EquipmentTypesTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentTypeController extends Controller
{
    use SoftDeletesTrait;

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', EquipmentTypesTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        $equipmentsTypes = (new EquipmentTypesFilters($request))
            ->apply(EquipmentType::query())
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return view('clients.equipmentTypes.partials.table', [
                'equipmentsTypes' => $equipmentsTypes,
                'visibleColumns' => $visibleColumns,
                'allColumns' => EquipmentTypesTable::allColumns(),
                'defaultDesktop' => EquipmentTypesTable::defaultDesktop(),
                'defaultMobile' => EquipmentTypesTable::defaultMobile(),
            ])->render();
        }

        return view('clients.equipmentTypes.index', array_merge(
            [
                'equipmentsTypes' => $equipmentsTypes,
                'visibleColumns' => $visibleColumns,
                'allColumns' => EquipmentTypesTable::allColumns(),
                'defaultDesktop' => EquipmentTypesTable::defaultDesktop(),
                'defaultMobile' => EquipmentTypesTable::defaultMobile(),
            ],
        ));
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

    public function toggleEstado(EquipmentType $equipo)
    {
        $equipo->toggleActivo();

        return redirect()
            ->route('clients.equipmentTypes.index')
            ->with('success', 'Estado actualizado para "'.$equipo->nombre.'".');
    }

    // Elimina la EquipmentType si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy(EquipmentType $equipo)
    {
        return $this->destroyTrait($equipo, null);
    }

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
