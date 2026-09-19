<?php

namespace App\Services\Sales\InvoicesServices;

use App\Models\Sales\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class InvoicePrintService
{
    /**
     * Genera el PDF en formato Carta (Letter)
     */
    public function generateLetterPDF(Invoice $invoice)
    {
        $invoice->load(['sale.items.product', 'sale.client', 'sale.user', 'sale.quote', 'sale.ncfLog.type']);

        // DomPDF no sigue URLs remotas (enable_remote=false por defecto) — el
        // logo necesita una ruta de archivo local, no la URL que usa la vista
        // previa en navegador (ver comentario en full.blade.php, REQ-1.14).
        $config = general_config();
        $logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;

        return Pdf::loadView('sales.invoices.formats.full', compact('invoice', 'logoSrc'))
            ->setPaper('letter', 'portrait');
    }

    /**
     * Retorna la vista para impresión térmica (Ticket 80mm)
     */
    public function getTicketView(Invoice $invoice)
    {
        $invoice->load(['sale.items.product', 'sale.client', 'sale.user', 'sale.quote', 'sale.ncfLog.type']);
        
        return view('sales.invoices.formats.ticket', compact('invoice'));
    }

    /**
     * Método auxiliar para determinar si el formato es para descargar
     */
    public function shouldDownload(string $format, bool $download = false): bool
    {
        return $format === 'letter' && $download;
    }

    /**
     * Método auxiliar para obtener el nombre de archivo
     */
    public function getFileName(Invoice $invoice): string
    {
        return "Factura-{$invoice->invoice_number}.pdf";
    }
}
