<?php

namespace App\Http\Controllers\Sales\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Pos\StorePosCollectionRequest;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Services\Accounting\Collection\CollectionService;
use Exception;
use Illuminate\Support\Facades\Auth;

class PosCollectionController extends Controller
{
    public function __construct(protected CollectionService $service) {}

    /**
     * Registra un Cobro de CxC originado desde el Workspace POS. Reutiliza
     * CollectionService::createCollection() (mismo camino que el cobro de
     * backoffice) para no duplicar reglas de negocio; solo agrega la
     * trazabilidad de sesión/terminal (Fase 6, REQ-6.6).
     */
    public function store(StorePosCollectionRequest $request, PosTerminal $pos_terminal)
    {
        // Defensa en profundidad — StorePosCollectionRequest::authorize() ya exige
        // el permiso, pero esta es la escritura real.
        abort_unless(Auth::user()->can('pos_sessions.manage'), 403);

        abort_unless($pos_terminal->allow_receivable_collection, 403, 'Esta terminal no permite cobrar cuentas por cobrar.');

        $session = PosSession::where('terminal_id', $pos_terminal->id)
            ->open()
            ->first();

        if (! $session) {
            return redirect()
                ->route('sales.pos.index')
                ->with('error', 'No hay un turno abierto en esta terminal.');
        }

        $data = $request->validated();
        // El Cobro siempre es "hoy" desde el TPV — a diferencia del backoffice, que
        // permite retrofechar (StoreCollectionRequest::payment_date).
        $data['payment_date'] = now()->toDateString();
        $data['pos_session_id'] = $session->id;
        $data['pos_terminal_id'] = $pos_terminal->id;

        try {
            $collection = $this->service->createCollection($data);

            return redirect()
                ->route('sales.pos.workspace', $pos_terminal)
                ->with('success', "Cobro {$collection->receipt_number} registrado con éxito.")
                ->with('lastCollectionId', $collection->id);
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el cobro: '.$e->getMessage());
        }
    }
}
