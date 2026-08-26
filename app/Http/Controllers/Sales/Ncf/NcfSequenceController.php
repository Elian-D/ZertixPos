<?php

namespace App\Http\Controllers\Sales\Ncf;

use App\Filters\Sales\Ncf\NcfSequenceFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Ncf\StoreNcfSequenceRequest;
use App\Models\Sales\Ncf\NcfSequence;
use App\Services\Sales\Ncf\NcfCatalogService;
use App\Services\Sales\Ncf\NcfSequenceService;
use App\Tables\SalesTables\Ncf\NcfSequenceTable;
use Illuminate\Http\Request;

class NcfSequenceController extends Controller
{
    public function __construct(
        protected NcfSequenceService $service,
        protected NcfCatalogService $catalog
    ) {}

    public function index(Request $request)
    {
        // 1. Obtener configuración de tabla
        $visibleColumns = $request->input('columns', NcfSequenceTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // 2. Aplicar Filtros (Pipeline)
        // Importante: Pasamos el $request al constructor
        $sequences = (new NcfSequenceFilters($request))
            ->apply(NcfSequence::query()->with('type'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // 3. Obtener Catálogos (Necesarios para el Index Y el Modal)
        $catalog = $this->catalog->getForSequences();

        // 4. Preparar datos para la vista
        $data = array_merge([
            'items' => $sequences,
            'visibleColumns' => $visibleColumns,
            'allColumns' => NcfSequenceTable::allColumns(),
            'defaultDesktop' => NcfSequenceTable::defaultDesktop(),
            'defaultMobile' => NcfSequenceTable::defaultMobile(),
        ], $catalog);

        // Si es AJAX, retornamos solo la tabla (y opcionalmente los modales si se refrescan)
        if ($request->ajax()) {
            return view('sales.ncf.sequences.partials.table', $data)->render();
        }

        return view('sales.ncf.sequences.index', $data);
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
