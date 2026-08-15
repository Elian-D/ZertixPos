<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\Accounting\JournalEntriesExport;
use App\Filters\Accounting\JournalEntriesFilters\JournalEntryFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\JournalEntries\StoreJournalEntryRequest;
use App\Http\Requests\Accounting\JournalEntries\UpdateJournalEntryRequest;
use App\Models\Accounting\JournalEntry;
use App\Services\Accounting\JournalEntries\JournalEntryCatalogService;
use App\Services\Accounting\JournalEntries\JournalEntryService;
use App\Tables\AccountingTables\JournalEntryTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class JournalEntryController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected JournalEntryService $service,
        protected JournalEntryCatalogService $catalogService
    ) {}

    /**
     * Vista principal con tabla AJAX y filtros
     */
    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', JournalEntryTable::defaultDesktop());
        $perPage = $request->input('per_page', 15);

        // Aplicación del Pipeline de Filtros (Fecha, Referencia, Estado)
        $entries = (new JournalEntryFilters($request))
            ->apply(JournalEntry::query()->with(['creator', 'items.account']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('accounting.journal_entries.partials.table', [
                'items' => $entries,
                'visibleColumns' => $visibleColumns,
                'allColumns' => JournalEntryTable::allColumns(),
                'defaultDesktop' => JournalEntryTable::defaultDesktop(),
                'defaultMobile' => JournalEntryTable::defaultMobile(),
            ])->render();
        }

        return view('accounting.journal_entries.index', array_merge(
            [
                'items' => $entries,
                'visibleColumns' => $visibleColumns,
                'allColumns' => JournalEntryTable::allColumns(),
                'defaultDesktop' => JournalEntryTable::defaultDesktop(),
                'defaultMobile' => JournalEntryTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('accounting.journal_entries.create', $this->catalogService->getForForm());
    }

    /**
     * Registrar nuevo asiento contable
     */
    public function store(StoreJournalEntryRequest $request)
    {
        try {
            $entry = $this->service->create($request->validated());

            return redirect()
                ->route('finance.journal_entries.index')
                ->with('success', 'Asiento contable registrado con éxito.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición (solo si es Borrador)
     */
    public function edit(JournalEntry $journal_entry)
    {
        if ($journal_entry->status !== JournalEntry::STATUS_DRAFT) {
            return redirect()->route('finance.journal_entries.index')
                ->with('error', 'No se puede editar un asiento que ya ha sido asentado o anulado.');
        }

        return view('accounting.journal_entries.edit', [
            'item' => $journal_entry->load('items.account'),
            'catalogs' => $this->catalogService->getForForm(),
        ]);
    }

    /**
     * Actualizar asiento contable
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journal_entry)
    {
        try {
            $this->service->update($journal_entry, $request->validated());

            return redirect()
                ->route('finance.journal_entries.index')
                ->with('success', 'Asiento actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Aplicamos los mismos filtros que en el index
        $query = (new JournalEntryFilters($request))
            ->apply(JournalEntry::query());

        $fileName = 'libro-diario-'.now()->format('d-m-Y-H-i').'.xlsx';

        return Excel::download(new JournalEntriesExport($query), $fileName);
    }

    /**
     * Acciones de cambio de estado (Asentar)
     */
    public function post(JournalEntry $journal_entry)
    {
        try {
            $this->service->post($journal_entry);

            return back()->with('success', 'El asiento ha sido asentado definitivamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Acciones de cambio de estado (Anular)
     */
    public function cancel(JournalEntry $journal_entry)
    {
        try {
            $this->service->cancel($journal_entry);

            return back()->with('success', 'El asiento ha sido anulado.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Eliminación lógica (SoftDelete)
     */
    public function destroy($id)
    {
        $entry = JournalEntry::findOrFail($id);

        if ($entry->status === JournalEntry::STATUS_POSTED) {
            return back()->with('error', 'No se puede eliminar un asiento ya asentado. Debe anularlo.');
        }

        return $this->destroyTrait($entry);
    }

    /**
     * Requerimientos para SoftDeletesTrait
     */
    protected function getModelClass(): string
    {
        return JournalEntry::class;
    }

    protected function getViewFolder(): string
    {
        return 'accounting.journal_entries';
    }

    protected function getRouteIndex(): string
    {
        return 'finance.journal_entries.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'finance.journal_entries.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Asiento Contable';
    }
}
