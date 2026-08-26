<?php

namespace App\Services\Accounting\Collection;

use App\Models\Accounting\ClientCollection;
use App\Services\Sales\Pos\PosPrintService;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Espejo de InvoicePrintService (app/Services/Sales/InvoicesServices/InvoicePrintService.php)
 * para el recibo de Cobro — antes no tenía este tratamiento (una sola vista de ancho
 * fijo, sin separación ticket/PDF, ver docs/features/v1.2.0.md Fase 6, REQ-6.8).
 */
class CollectionPrintService
{
    public function __construct(protected PosPrintService $posPrintService) {}

    /**
     * Retorna la vista para impresión térmica, con el mismo ancho dinámico
     * (58mm/80mm, zona muerta del cabezal) ya construido para el ticket de venta.
     */
    public function getTicketView(ClientCollection $payment)
    {
        $payment->load(['client', 'creator', 'tipoPago', 'receivable.reference.items.product', 'posTerminal']);

        return view('finance.collections.ticket', [
            'payment' => $payment,
            'paperWidth' => $this->posPrintService->resolvePaperWidth($payment->posTerminal),
        ]);
    }

    /**
     * Genera el PDF en formato Carta (Letter)
     */
    public function generateLetterPDF(ClientCollection $payment)
    {
        $payment->load(['client', 'creator', 'tipoPago', 'receivable']);

        return Pdf::loadView('finance.collections.pdf', compact('payment'))
            ->setPaper('letter', 'portrait');
    }

    public function getFileName(ClientCollection $payment): string
    {
        return "Cobro-{$payment->receipt_number}.pdf";
    }
}
