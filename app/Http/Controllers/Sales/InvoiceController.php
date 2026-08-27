<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use App\Services\Sales\InvoicesServices\InvoicePrintService;
use App\Services\Sales\Pos\PosPrintService;
use Illuminate\Http\Request;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — Invoice::status solo
 * refleja la anulación de la venta origen (SaleService::cancel()), nunca un
 * cancel() propio. Nunca implementó destroy() pese al SoftDeletesTrait viejo
 * (boilerplate muerto, igual que SaleController) — se quitó del todo.
 */
class InvoiceController extends Controller
{
    public function __construct(
        protected InvoicePrintService $printService,
        protected PosPrintService $posPrintService
    ) {}

    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Finance\InvoiceTable.
     */
    public function index()
    {
        return view('sales.invoices.index');
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
            Invoice::FORMAT_ROUTE => 'ticket',
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
}
