<?php

namespace App\Http\Controllers\Accounting;

use App\Filters\Accounting\ReceivablesFilters\ReceivableFilters;
use App\Http\Controllers\Controller;
use App\Models\Accounting\Receivable;
use App\Services\Accounting\Receivable\ReceivableCatalogService;
use App\Services\Accounting\Receivable\ReceivableService;
use App\Tables\AccountingTables\ReceivableTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected ReceivableService $service,
        protected ReceivableCatalogService $catalogService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', ReceivableTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // Aplicación del Pipeline de Filtros
        $receivables = (new ReceivableFilters($request))
            ->apply(Receivable::query()->with(['client', 'journalEntry']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('accounting.receivables.partials.table', [
                'items' => $receivables,
                'visibleColumns' => $visibleColumns,
                'allColumns' => ReceivableTable::allColumns(),
                'defaultDesktop' => ReceivableTable::defaultDesktop(),
                'defaultMobile' => ReceivableTable::defaultMobile(),
            ])->render();
        }

        return view('accounting.receivables.index', array_merge(
            [
                'items' => $receivables,
                'visibleColumns' => $visibleColumns,
                'allColumns' => ReceivableTable::allColumns(),
                'defaultDesktop' => ReceivableTable::defaultDesktop(),
                'defaultMobile' => ReceivableTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    /**
     * Borrado de la vista (SoftDelete)
     */
    public function destroy($id)
    {
        $receivable = Receivable::findOrFail($id);

        // Si no está anulada, forzamos que primero pase por cancel() para asegurar la contabilidad
        if ($receivable->status !== Receivable::STATUS_CANCELLED) {
            return back()->with('error', 'Para eliminar esta cuenta primero debe ser anulada para revertir la contabilidad.');
        }

        return $this->destroyTrait($receivable);
    }

    // Requerimientos del SoftDeletesTrait
    protected function getModelClass(): string
    {
        return Receivable::class;
    }

    protected function getViewFolder(): string
    {
        return 'accounting.receivables';
    }

    protected function getRouteIndex(): string
    {
        return 'finance.receivables.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'finance.receivables.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Cuenta por Cobrar';
    }
}
