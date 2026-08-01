<?php

namespace App\Http\Controllers\Sales\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Pos\CashMovements\StoreCashMovementRequest;
use App\Services\Sales\Pos\PosCashMovementServices\PosCashMovementService;
use App\Services\Sales\Pos\PosCashMovementServices\PosCashMovementCatalogService;
use App\Filters\Sales\Pos\CashMovementFilters\PosCashMovementFilters;
use App\Models\Sales\Pos\PosCashMovement;
use App\Tables\SalesTables\Pos\PosCashMovementTable;
use Illuminate\Http\Request;

class PosCashMovementController extends Controller
{
    public function __construct(
        protected PosCashMovementService $service,
        protected PosCashMovementCatalogService $catalogService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', PosCashMovementTable::defaultDesktop());
        $perPage = $request->input('per_page', 15);

        $movements = (new PosCashMovementFilters($request))
            ->apply(PosCashMovement::query()->with(['user', 'session.terminal', 'accountingEntry']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return view('sales.pos.cash-movements.partials.table', [
                'items'          => $movements,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => PosCashMovementTable::allColumns(),
                'defaultDesktop' => PosCashMovementTable::defaultDesktop(),
                'defaultMobile'  => PosCashMovementTable::defaultMobile(),
            ])->render();
        }

        return view('sales.pos.cash-movements.index', array_merge(
            [
                'items'          => $movements,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => PosCashMovementTable::allColumns(),
                'defaultDesktop' => PosCashMovementTable::defaultDesktop(),
                'defaultMobile'  => PosCashMovementTable::defaultMobile(),
            ],
            $this->catalogService->getForForm()
        ));
    }

    /**
     * Registro clásico con redirección para disparar Toasts de sesión.
     */
    public function store(StoreCashMovementRequest $request)
    {
        $movement = $this->service->store($request->validated());

        // Redirección clásica para asegurar la persistencia de mensajes flash
        return redirect()->back()
            ->with('success', "Movimiento registrado: " . ($movement->type === 'in' ? 'Entrada' : 'Salida') . " de " . number_format($movement->amount, 2) . " por motivo: {$movement->reason}");
    }
}