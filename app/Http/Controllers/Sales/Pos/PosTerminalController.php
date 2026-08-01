<?php

namespace App\Http\Controllers\Sales\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\Sales\Pos\PosSession;
use App\Http\Requests\Sales\Pos\PosTerminals\StorePosTerminalRequest;
use App\Http\Requests\Sales\Pos\PosTerminals\UpdatePosTerminalRequest;
use App\Services\Sales\Pos\PosTerminals\PosTerminalService;
use App\Services\Sales\Pos\PosTerminals\PosTerminalCatalogService;
use App\Tables\SalesTables\Pos\PosTerminalTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Exception;

class PosTerminalController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected PosTerminalService $service,
        protected PosTerminalCatalogService $catalogService
    ) {}

    /**
     * Vista principal con tabla AJAX y listado de terminales.
     */
    public function index(Request $request)
    {
        // 1. Parámetros de UI (Basado en tu clase Table)
        $visibleColumns = $request->input('columns', PosTerminalTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // 2. Consulta con relaciones necesarias para evitar N+1
        $terminals = PosTerminal::with(['warehouse', 'cashAccount', 'defaultNcfType', 'defaultClient'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // 3. Respuesta AJAX para recarga parcial de la tabla
        if ($request->ajax()) {
            return view('sales.pos.terminals.partials.table', [
                'items'          => $terminals,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => PosTerminalTable::allColumns(),
                'defaultDesktop' => PosTerminalTable::defaultDesktop(),
                'defaultMobile'  => PosTerminalTable::defaultMobile(),
            ])->render();
        }

        // 4. Vista completa con catálogos para filtros/modales si fuera necesario
        return view('sales.pos.terminals.index', array_merge(
            [
                'items'          => $terminals,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => PosTerminalTable::allColumns(),
                'defaultDesktop' => PosTerminalTable::defaultDesktop(),
                'defaultMobile'  => PosTerminalTable::defaultMobile(),
            ],
            $this->catalogService->getForForm()
        ));
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        return view('sales.pos.terminals.create', array_merge(
        ['posTerminal' => new \App\Models\Sales\Pos\PosTerminal()], // Objeto vacío
        $this->catalogService->getForForm()
    ));
    }

    /**
     * Almacenar nueva terminal POS.
     */
    public function store(StorePosTerminalRequest $request)
    {
        try {
            $terminal = $this->service->create($request->validated());

            return redirect()
                ->route('sales.pos.terminals.index')
                ->with('success', "Terminal '{$terminal->name}' registrada correctamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', "Error al crear la terminal: " . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit(PosTerminal $posTerminal)
    {
        return view('sales.pos.terminals.edit', array_merge(
            ['posTerminal' => $posTerminal],
            $this->catalogService->getForForm()
        ));
    }

    /**
     * Actualizar configuración de la terminal.
     */
    public function update(UpdatePosTerminalRequest $request, PosTerminal $posTerminal)
    {
        // Mismo guard que destroy(): no permitir desactivar una terminal con una sesión de
        // caja abierta. Anular una sesión/venta en curso por un simple cambio de flag sería
        // peor que dejarla activa un momento más — el cierre real de caja pasa por
        // sales.pos.sessions.close, no por aquí.
        if (! $request->boolean('is_active') && $posTerminal->is_active
            && $posTerminal->sessions()->where('status', PosSession::STATUS_OPEN)->exists()) {
            return back()->withInput()->with('error', "No se puede desactivar la terminal '{$posTerminal->name}': tiene una sesión de caja abierta. Cierra la caja primero.");
        }

        try {
            $this->service->update($posTerminal, $request->validated());

            return redirect()
                ->route('sales.pos.terminals.index')
                ->with('success', "Terminal '{$posTerminal->name}' actualizada correctamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', "Error al actualizar la terminal: " . $e->getMessage());
        }
    }

    /**
     * Eliminar (Soft Delete) usando el trait.
     */
    public function destroy(PosTerminal $posTerminal)
    {
        // No permitir eliminar una terminal con una sesión de caja abierta:
        // el cajero quedaría con una sesión "huérfana" e imposible de cerrar.
        if ($posTerminal->sessions()->where('status', PosSession::STATUS_OPEN)->exists()) {
            return redirect()
                ->route('sales.pos.terminals.index')
                ->with('error', "No se puede eliminar la terminal '{$posTerminal->name}': tiene una sesión de caja abierta. Cierra la caja primero.");
        }

        // El trait manejará la lógica de borrado suave y redirección
        return $this->destroyTrait($posTerminal, null);
    }

    /* ===========================
     |  CONFIGURACIÓN DEL TRAIT
     =========================== */
    protected function getModelClass(): string { return PosTerminal::class; }
    protected function getViewFolder(): string { return 'sales.pos.terminals'; }
    protected function getRouteIndex(): string { return 'sales.pos.terminals.index'; }
    protected function getRouteEliminadas(): string { return 'sales.pos.terminals.eliminadas'; }
    protected function getEntityName(): string { return 'Terminal POS'; }
}