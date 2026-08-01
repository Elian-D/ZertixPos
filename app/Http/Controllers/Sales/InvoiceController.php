<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use App\Services\Sales\InvoicesServices\InvoiceCatalogService;
use App\Services\Sales\InvoicesServices\InvoicePrintService;
use App\Services\Sales\Pos\PosPrintService;
use App\Filters\Sales\InvoiceFilters\InvoiceFilters;
use App\Tables\SalesTables\InvoiceTable;
use App\Traits\SoftDeletesTrait;
use Illuminate\Http\Request;
use Exception;
use App\Http\Requests\Sales\Invoices\ExportInvoiceRequest;
use App\Exports\Sales\InvoicesExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
    use SoftDeletesTrait;

    public function __construct(
        protected InvoiceCatalogService $catalogService,
        protected InvoicePrintService $printService,
        protected PosPrintService $posPrintService
    ) {}

    /**
     * Vista principal: Listado de documentos legales.
     */
    public function index(Request $request)
    {
        $visibleColumns = $request->input('columns', InvoiceTable::defaultDesktop());
        $perPage = $request->input('per_page', 10);

        // Aplicación del Pipeline de Filtros sobre la relación con ventas y clientes
        $invoices = (new InvoiceFilters($request))
            ->apply(Invoice::query()->withIndexRelations())
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $catalogs = $this->catalogService->getForFilters();

        if ($request->ajax()) {
            return view('sales.invoices.partials.table', [
                'items'          => $invoices,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => InvoiceTable::allColumns(),
                'defaultDesktop' => InvoiceTable::defaultDesktop(),
                'defaultMobile'  => InvoiceTable::defaultMobile(),
            ])->render();
        }

        return view('sales.invoices.index', array_merge(
            [
                'items'          => $invoices,
                'visibleColumns' => $visibleColumns,
                'allColumns'     => InvoiceTable::allColumns(),
                'defaultDesktop' => InvoiceTable::defaultDesktop(),
                'defaultMobile'  => InvoiceTable::defaultMobile(),
            ],
            $catalogs
        ));
    }

    public function show(Invoice $invoice)
    {
        // CARGA PROFUNDA: Entramos hasta la secuencia para obtener la fecha de vencimiento
        $invoice->load([
            'sale.items.product', 
            'sale.client', 
            'sale.quote', // <--- Para mostrar descuentos de cotización
            'sale.ncfLog.type', 
            'sale.ncfLog.sequence', // <--- FUNDAMENTAL
        ]);
        
        $formats = Invoice::getFormats();
        return view('sales.invoices.show', compact('invoice', 'formats'));
    }

    public function preview(Invoice $invoice, Request $request)
    {
        // El preview también necesita la data del NCF para mostrarla en el iframe
        $invoice->load([
            'sale.items.product',
            'sale.client',
            'sale.user',
            'sale.quote', // <--- Para mostrar descuentos de cotización
            'sale.ncfLog.type',
            'sale.ncfLog.sequence', // <--- FUNDAMENTAL
            'sale.posTerminal',
        ]);

        // Permitir cambio de formato vía query string desde JavaScript
        $format = $request->query('format', $invoice->format_type);

        $viewMap = [
            Invoice::FORMAT_TICKET => 'ticket',
            Invoice::FORMAT_ROUTE  => 'ticket',
            Invoice::FORMAT_LETTER => 'full',
            'ticket' => 'ticket',
            'letter' => 'full',
        ];

        $viewName = $viewMap[$format] ?? 'ticket';

        return view("sales.invoices.formats.{$viewName}", [
            'invoice' => $invoice,
            'paperWidth' => $this->posPrintService->resolvePaperWidth($invoice->sale->posTerminal),
        ]);
    }



    public function print(Invoice $invoice, Request $request)
    {
        // Cargar todas las relaciones necesarias
        $invoice->load([
            'sale.items.product',
            'sale.client',
            'sale.user',
            'sale.quote',
            'sale.ncfLog.type',
            'sale.ncfLog.sequence',
            'sale.posTerminal',
        ]);

        // Permitir cambio de formato vía query string
        $format = $request->query('format', $invoice->format_type);

        // Determinar si es descarga
        $download = $request->boolean('download', false);

        // Si es formato TICKET o RUTA
        if (in_array($format, ['ticket', 'route'])) {
            // Renderizar la vista de ticket
            $paperWidth = $this->posPrintService->resolvePaperWidth($invoice->sale->posTerminal);
            $view = view('sales.invoices.formats.ticket', ['invoice' => $invoice, 'paperWidth' => $paperWidth])->render();
            return view('sales.invoices.print', compact('invoice', 'view'));
        }

        // Si es formato CARTA
        // Si se solicita descarga, retornar PDF para descargar
        if ($download) {
            $pdf = $this->printService->generateLetterPDF($invoice);
            return $pdf->download($this->printService->getFileName($invoice));
        }

        // Si es visualización (no descarga), renderizar la vista de formato
        $view = view('sales.invoices.formats.full', ['invoice' => $invoice])->render();
        return view('sales.invoices.print', compact('invoice', 'view'));
    }


    /**
     * Exportación filtrada de facturas a Excel.
     */
    public function export(ExportInvoiceRequest $request)
    {
        try {
            // Aplicamos los mismos filtros que en la tabla
            $query = (new InvoiceFilters($request))
                ->apply(Invoice::query());

            $fileName = 'historial-facturacion-' . now()->format('d-m-Y-H-i') . '.xlsx';

            return Excel::download(new InvoicesExport($query), $fileName);
            
        } catch (Exception $e) {
            return back()->with('error', "No se pudo generar el reporte: " . $e->getMessage());
        }
    }

    // Requerimientos para SoftDeletesTrait (Auditoría técnica)
    protected function getModelClass(): string { return Invoice::class; }
    protected function getViewFolder(): string { return 'sales.invoices'; }
    protected function getRouteIndex(): string { return 'sales.invoices.index'; }
    protected function getRouteEliminadas(): string { return 'sales.invoices.eliminadas'; }
    protected function getEntityName(): string { return 'Factura'; }
}