<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\Accounting\CollectionsExport;
use App\Filters\Accounting\CollectionsFilters\CollectionFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\Collection\StoreCollectionRequest;
use App\Models\Accounting\ClientCollection;
use App\Services\Accounting\Collection\CollectionCatalogService;
use App\Services\Accounting\Collection\CollectionPrintService;
use App\Services\Accounting\Collection\CollectionService;
use App\Tables\AccountingTables\CollectionTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CollectionController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected CollectionService $service,
        protected CollectionCatalogService $catalogService,
        protected CollectionPrintService $printService
    ) {}

    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', CollectionTable::defaultDesktop());
        $perPage = $request->input('per_page', 15);

        $collections = (new CollectionFilters($request))
            ->apply(ClientCollection::query()->with(['client', 'receivable', 'tipoPago', 'creator']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('finance.collections.partials.table', [
                'items' => $collections,
                'visibleColumns' => $visibleColumns,
                'allColumns' => CollectionTable::allColumns(),
                'defaultDesktop' => CollectionTable::defaultDesktop(),
                'defaultMobile' => CollectionTable::defaultMobile(),
            ])->render();
        }

        return view('finance.collections.index', array_merge(
            [
                'items' => $collections,
                'visibleColumns' => $visibleColumns,
                'allColumns' => CollectionTable::allColumns(),
                'defaultDesktop' => CollectionTable::defaultDesktop(),
                'defaultMobile' => CollectionTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    /**
     * Formato de impresión del recibo — ticket térmico (default, ancho dinámico
     * según la terminal de origen) o carta/PDF, mismo patrón que InvoiceController
     * (Fase 6, REQ-6.8).
     */
    public function print(ClientCollection $payment, Request $request)
    {
        try {
            $format = $request->query('format', 'ticket');

            if ($format === 'letter') {
                if ($request->boolean('download')) {
                    $pdf = $this->printService->generateLetterPDF($payment);

                    return $pdf->download($this->printService->getFileName($payment));
                }

                $payment->load(['client', 'creator', 'tipoPago', 'receivable']);

                return view('finance.collections.pdf', compact('payment'));
            }

            $view = $this->printService->getTicketView($payment)->render();

            return view('finance.collections.print', compact('payment', 'view'));
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo cargar el formato: '.$e->getMessage());
        }
    }

    /**
     * Exportar los datos filtrados a Excel
     */
    public function export(Request $request)
    {
        try {
            // Aplicamos los mismos filtros que en la tabla principal
            $query = (new CollectionFilters($request))
                ->apply(ClientCollection::query());

            $fileName = 'reporte-cobros-'.now()->format('d-m-Y-H-i').'.xlsx';

            return Excel::download(new CollectionsExport($query), $fileName);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar el reporte: '.$e->getMessage());
        }
    }

    public function create()
    {
        return view('finance.collections.create', $this->catalogService->getForForm());
    }

    public function store(StoreCollectionRequest $request)
    {
        try {
            $this->service->createCollection($request->validated());

            return redirect()
                ->route('finance.collections.index')
                ->with('success', 'Cobro registrado y contabilizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function cancel(ClientCollection $payment)
    {
        try {
            $this->service->cancelCollection($payment);

            return back()->with('success', 'El cobro ha sido anulado y el saldo de la factura revertido.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $payment = ClientCollection::findOrFail($id);

        if ($payment->status === ClientCollection::STATUS_ACTIVE) {
            return back()->with('error', 'No se puede eliminar un cobro activo. Debe anularlo primero para revertir la contabilidad.');
        }

        return $this->destroyTrait($payment);
    }

    // Métodos requeridos por SoftDeletesTrait
    protected function getModelClass(): string
    {
        return ClientCollection::class;
    }

    protected function getViewFolder(): string
    {
        return 'finance.collections';
    }

    protected function getRouteIndex(): string
    {
        return 'finance.collections.index';
    }

    protected function getRouteEliminadas(): string
    {
        return 'finance.collections.eliminados';
    }

    protected function getEntityName(): string
    {
        return 'Cobro / Recibo';
    }
}
