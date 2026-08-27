<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\PointOfSale\StorePointOfSaleRequest;
use App\Http\Requests\PointOfSale\UpdatePointOfSaleRequest;
use App\Models\Clients\PointOfSale;
use App\Services\PointOfSale\POSCatalogService;
use App\Services\PointOfSale\POSService;
use App\Traits\SoftDeletesTrait;

class PointOfSaleController extends Controller
{
    use SoftDeletesTrait;

    /**
     * REQ-0.7: la tabla vive ahora en App\Livewire\App\Clients\PointOfSaleTable
     * (motor Livewire, Fase 0) — este método solo renderiza el layout.
     */
    public function index()
    {
        return view('clients.pos.index');
    }

    public function create(POSCatalogService $catalogService)
    {
        return view('clients.pos.create', $catalogService->getForForm());
    }

    public function store(StorePointOfSaleRequest $request, POSService $posService)
    {
        $pos = $posService->createPOS($request->validated());

        return redirect()->route('clients.delivery_points.index')
            ->with('success', "Punto de venta {$pos->name} ({$pos->code}) creado.");
    }

    public function edit(PointOfSale $pos, POSCatalogService $catalogService)
    {
        return view('clients.pos.edit', array_merge(
            ['pos' => $pos],
            $catalogService->getForForm()
        ));
    }

    public function update(UpdatePointOfSaleRequest $request, PointOfSale $pos, POSService $posService)
    {
        $posService->updatePOS($pos, $request->validated());

        return redirect()->route('clients.delivery_points.index')
            ->with('success', "Punto de venta {$pos->name} actualizado correctamente.");
    }

    public function destroy(PointOfSale $pos)
    {
        return $this->destroyTrait($pos, null);
    }

    /* Configuración del Trait — solo destroyTrait() (destroy() arriba) sigue
     | alcanzable por HTTP. eliminadas()/restaurar()/borrarDefinitivo() del
     | trait ya no tienen ruta — el tab "Papelera" de PointOfSaleTable
     | Livewire las reemplazó (docs/analisis/politica-soft-deletes.md §6). */
    protected function getModelClass(): string
    {
        return PointOfSale::class;
    }

    protected function getViewFolder(): string
    {
        return 'clients.pos';
    }

    protected function getRouteIndex(): string
    {
        return 'clients.delivery_points.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'clients.delivery_points.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Punto de Reparto';
    }
}
