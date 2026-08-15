<?php

namespace App\Http\Controllers\Clients;

use App\Filters\BusinessTypes\BusinessTypesFilters;
use App\Http\Controllers\Controller;
use App\Models\Clients\BusinessType;
use App\Tables\BusinessTypesTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessTypeController extends Controller
{
    use SoftDeletesTrait;

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', BusinessTypesTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        $businessTypes = (new BusinessTypesFilters($request))
            ->apply(BusinessType::query())
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return view('clients.businessTypes.partials.table', [
                'businessTypes' => $businessTypes,
                'visibleColumns' => $visibleColumns,
                'allColumns' => BusinessTypesTable::allColumns(),
                'defaultDesktop' => BusinessTypesTable::defaultDesktop(),
                'defaultMobile' => BusinessTypesTable::defaultMobile(),
            ])->render();
        }

        return view('clients.businessTypes.index', array_merge(
            [
                'businessTypes' => $businessTypes,
                'visibleColumns' => $visibleColumns,
                'allColumns' => BusinessTypesTable::allColumns(),
                'defaultDesktop' => BusinessTypesTable::defaultDesktop(),
                'defaultMobile' => BusinessTypesTable::defaultMobile(),
            ],
        ));
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

    public function toggleEstado(BusinessType $negocio)
    {
        $negocio->toggleActivo();

        return redirect()
            ->route('clients.businessTypes.index')
            ->with('success', 'Estado actualizado para "'.$negocio->nombre.'".');
    }

    // Elimina la BusinessType si no tiene relaciones (o desactiva la eliminación por defecto).
    public function destroy($id)
    {
        $businessType = BusinessType::findOrFail($id);

        return $this->destroyTrait($businessType);
    }

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
