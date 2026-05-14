<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Models\Sales\Sale;
use App\Services\Sales\SalesServices\SaleService;
use App\Services\Sales\SalesServices\SaleCatalogService;
use App\Filters\Sales\SalesFilters\SaleFilters;
use App\Tables\SalesTables\SaleTable;
use App\Traits\SoftDeletesTrait;
use App\Exports\Sales\SalesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected SaleService $service,
        protected SaleCatalogService $catalogService
    ) {}

    /**
     * Vista principal: Tabla AJAX con filtros dinámicos.
     */
    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', SaleTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // Aplicación del Pipeline de Filtros
        $sales = (new SaleFilters($request))
            ->apply(Sale::query()->withIndexRelations())
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('sales.partials.table', [
                'items'          => $sales,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => SaleTable::allColumns(),
                'defaultDesktop' => SaleTable::defaultDesktop(),
                'defaultMobile'  => SaleTable::defaultMobile(),
            ])->render();
        }

        return view('sales.index', array_merge(
            [
                'items'          => $sales,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => SaleTable::allColumns(),
                'defaultDesktop' => SaleTable::defaultDesktop(),
                'defaultMobile'  => SaleTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    /**
     * Redirigir a la impresión de la factura asociada a la venta.
     */
    public function printInvoice(Sale $sale, Request $request)
    {
        // Buscamos la factura asociada
        $invoice = $sale->invoice; 

        if (!$invoice) {
            return back()->with('error', 'Esta venta aún no tiene una factura generada.');
        }

        // Pasar el formato y parámetros de descarga al método print del InvoiceController
        return app(\App\Http\Controllers\Sales\InvoiceController::class)->print($invoice, $request);
    }

    /**
     * Mostrar formulario de creación (Ventanilla de Venta).
     */
    public function create()
    {
        return view('sales.create', $this->catalogService->getForForm());
    }

    /**
     * Registrar la venta, afectar inventario y generar asientos.
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            $sale = $this->service->create($request->validated());

            return redirect()
                ->route('sales.index')
                ->with('success', "Venta #{$sale->number} registrada con éxito.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', "Error al procesar la venta: " . $e->getMessage());
        }
    }

    public function cancel(Request $request, Sale $sale)
    {
        // 1. Verificación de estado rápida
        if ($sale->status === Sale::STATUS_CANCELED) {
            return back()->with('error', "Esta venta ya ha sido anulada previamente.");
        }

        // 2. Validación (Considera extraer esto a un FormRequest si crece mucho)
        $rules = [];
        if (!empty($sale->ncf)) {
            $rules['cancellation_reason'] = 'required|string|min:5|max:255';
        }

        $validated = $request->validate($rules, [
            'cancellation_reason.required' => 'El motivo de anulación es requerido para reportar a la DGII (608).'
        ]);

        try {
            // 3. Ejecución vía Servicio
            // Usamos null coalescing por si la venta no tiene NCF y no entró en la validación
            $reason = $validated['cancellation_reason'] ?? 'Anulación administrativa';
            
            $this->service->cancel($sale, $reason);
            
            return back()->with('success', "Venta {$sale->number} anulada y stock retornado.");
        } catch (Exception $e) {
            // Loguear el error para el admin es buena idea
            Log::error("Error anulando venta {$sale->id}: " . $e->getMessage());
            return back()->with('error', "Error: " . $e->getMessage());
        }
}

    /**
     * Exportación filtrada a Excel.
     */
    public function export(Request $request)
    {
        $query = (new SaleFilters($request))
            ->apply(Sale::query());

        $fileName = 'reporte-ventas-' . now()->format('d-m-Y-H-i') . '.xlsx';

        return Excel::download(new SalesExport($query), $fileName);
    }

    /**
     * Requerimientos para SoftDeletesTrait.
     */
    protected function getModelClass(): string { return Sale::class; }
    protected function getViewFolder(): string { return 'sales.sales'; }
    protected function getRouteIndex(): string { return 'sales.index'; }
    protected function getRouteEliminadas(): string { return 'sales.eliminados'; }
    protected function getEntityName(): string { return 'Venta'; }
}