<?php

namespace App\Http\Controllers\Sales\Pos;

use App\DTOs\Sales\PosContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Pos\StorePosSaleRequest;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Services\Sales\SalesServices\SaleService;
use Exception;

class PosCheckoutController extends Controller
{
    public function __construct(protected SaleService $service) {}

    /**
     * Registra una venta originada desde el Workspace POS.
     * StorePosSaleRequest (REQ-2.4, v1.3.0 Fase 2) — extiende las reglas de
     * StoreSaleRequest sin duplicarlas, pero autoriza por `pos_sessions.manage`,
     * no `sales.create` (permiso de backoffice, ya no aplica acá). Cualquier
     * usuario con permiso para operar sesiones POS puede vender en un turno ya
     * abierto, no solo quien lo abrió (ver PosSession 9.0 en POS-Interfaz.md).
     */
    public function store(StorePosSaleRequest $request, PosTerminal $pos_terminal)
    {
        $session = PosSession::where('terminal_id', $pos_terminal->id)
            ->open()
            ->first();

        if (! $session) {
            return redirect()
                ->route('sales.pos.index')
                ->with('error', 'No hay un turno abierto en esta terminal.');
        }

        $data = $request->validated();
        // El almacén de la venta siempre es el de la terminal, nunca el que envíe el cliente.
        $data['warehouse_id'] = $session->terminal->warehouse_id;

        $context = PosContext::fromSession($session, $request->boolean('is_walkin_customer'));

        try {
            $sale = $this->service->create($data, $context);

            return redirect()
                ->route('sales.pos.workspace', $pos_terminal)
                ->with('success', "Venta #{$sale->number} registrada con éxito.")
                ->with('lastSaleId', $sale->id);
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar la venta: '.$e->getMessage());
        }
    }
}
