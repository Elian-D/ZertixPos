<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Clients\Equipment;
use App\Traits\SoftDeletesTrait;
use App\Services\Equipment\EquipmentCatalogService;
use App\Services\Equipment\EquipmentService;
use App\Http\Requests\Equipment\{
    StoreEquipmentRequest,
    UpdateEquipmentRequest,
};

class EquipmentController extends Controller
{
    use SoftDeletesTrait;

    /**
     * REQ-0.7: la tabla vive ahora en App\Livewire\App\Clients\EquipmentTable
     * (motor Livewire, Fase 0) — este método solo renderiza el layout.
     */
    public function index()
    {
        return view('clients.equipment.index');
    }

    public function create(EquipmentCatalogService $catalogService)
    {
        return view('clients.equipment.create', $catalogService->getForForm());
    }

    public function store(StoreEquipmentRequest $request, EquipmentService $service)
    {
        $equipment = $service->create($request->validated());

        return redirect()
            ->route('clients.equipment.index')
            ->with('success', "Equipo {$equipment->code} creado correctamente.");
    }

    public function edit(Equipment $equipment, EquipmentCatalogService $catalogService)
    {
        return view('clients.equipment.edit', array_merge(
            ['equipment' => $equipment],
            $catalogService->getForForm()
        ));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment, EquipmentService $service)
    {
        $service->update($equipment, $request->validated());

        return redirect()
            ->route('clients.equipment.index')
            ->with('success', "Equipo {$equipment->code} actualizado correctamente.");
    }

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        return $this->destroyTrait($equipment);
    }


    /* ===== Configuración del Trait — solo destroyTrait() sigue alcanzable
     |  por HTTP. eliminadas()/restaurar()/borrarDefinitivo() del trait ya
     |  no tienen ruta, reemplazadas por el tab "Papelera" de EquipmentTable
     |  Livewire (docs/analisis/politica-soft-deletes.md §6). ===== */
    protected function getModelClass(): string { return Equipment::class; }
    protected function getViewFolder(): string { return 'clients.equipment'; }
    protected function getRouteIndex(): string { return 'clients.equipment.index'; }
    protected function getRouteEliminadas(): string { return 'clients.equipment.eliminados'; }
    protected function getEntityName(): string { return 'Equipo'; }
}
