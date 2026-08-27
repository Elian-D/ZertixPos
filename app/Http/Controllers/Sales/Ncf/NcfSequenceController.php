<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Ncf\StoreNcfSequenceRequest;
use App\Models\Sales\Ncf\NcfSequence;
use App\Services\Sales\Ncf\NcfCatalogService;
use App\Services\Sales\Ncf\NcfSequenceService;
use Illuminate\Http\Request;

class NcfSequenceController extends Controller
{
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
