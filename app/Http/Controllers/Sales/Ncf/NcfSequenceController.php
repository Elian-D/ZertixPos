<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Ncf\StoreNcfSequenceRequest;
use App\Models\Sales\Ncf\NcfLog;
use App\Models\Sales\Ncf\NcfSequence;
use App\Services\Sales\Ncf\NcfCatalogService;
use App\Services\Sales\Ncf\NcfSequenceService;
use Illuminate\Http\Request;

class NcfSequenceController extends Controller
{
    /** NCF emitidos que se listan en el show (el resto, en el Log NCF). */
    private const LOG_LIMIT = 25;

    public function __construct(
        protected NcfSequenceService $service,
        protected NcfCatalogService $catalog
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Finance\NcfSequenceTable.
     */
    public function index()
    {
        return view('sales.ncf.sequences.index');
    }

    /**
     * Detalle de la secuencia NCF (v1.4.0 Fase 3, patrón Infolist — /filament-show).
     * Reemplaza el modal "view-sequence" del listado.
     */
    public function show(NcfSequence $sequence)
    {
        $sequence->load('type');

        $logs = NcfLog::where('ncf_sequence_id', $sequence->id);

        return view('sales.ncf.sequences.show', array_merge([
            'sequence' => $sequence,
            'recentLogs' => (clone $logs)
                ->with(['sale:id,number,client_id', 'sale.client:id,name,commercial_name'])
                ->latest()
                ->limit(self::LOG_LIMIT)
                ->get(),
            'issuedCount' => (clone $logs)->count(),
            'voidedCount' => (clone $logs)->where('status', NcfLog::STATUS_VOIDED)->count(),
            'logLimit' => self::LOG_LIMIT,
        ],
            // El partial de modales trae también el de "crear secuencia", que
            // necesita el catálogo de tipos (ncf_types, ncf_types_prefixes…).
            $this->catalog->getTypesData()
        ));
    }

    public function store(StoreNcfSequenceRequest $request)
    {
        try {
            $this->service->create($request->validated());

            return redirect()->route('finance.ncf.sequences.index')
                ->with('success', 'Lote de NCF registrado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function updateThreshold(Request $request, NcfSequence $sequence)
    {
        $request->validate([
            'alert_threshold' => 'required|integer|min:0',
        ]);

        $this->service->updateAlertThreshold($sequence, $request->alert_threshold);

        return back()->with('success', 'Umbral de alerta actualizado correctamente.');
    }

    public function extend(Request $request, NcfSequence $sequence)
    {
        $validated = $request->validate([
            'new_to' => [
                'required',
                'integer',
                "gt:{$sequence->to}", // Validar contra el valor actual
                'max:99999999',
            ],
        ]);

        // Actualizamos el rango y reiniciamos el estado si estaba agotado
        $newStatus = ($sequence->current < $validated['new_to']) ? 'active' : $sequence->status;

        $sequence->update([
            'to' => $validated['new_to'],
            'status' => $newStatus,
        ]);

        return back()->with('success', "Rango ampliado correctamente hasta el número {$validated['new_to']}.");
    }

    public function destroy(NcfSequence $sequence)
    {
        try {
            $this->service->delete($sequence);

            return back()->with('success', 'Secuencia eliminada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
