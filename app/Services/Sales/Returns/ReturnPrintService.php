<?php

namespace App\Services\Sales\Returns;

use App\Models\Sales\Returns\SaleReturn;
use App\Services\Sales\Pos\PosPrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;

/**
 * Ticket corto de devolución (v1.4.0 REQ-2.5): solo lo devuelto y el resultado,
 * sin reproducir la venta original. Mismo ancho térmico que el resto de tickets.
 */
class ReturnPrintService
{
    private const MM_TO_PT = 2.8346;

    public function __construct(protected PosPrintService $posPrintService) {}

    public function getTicketView(SaleReturn $return): View
    {
        $return->load(['sale.posTerminal', 'user:id,name', 'items.saleItem.product', 'exchangeSale.items.product']);

        return view('sales.returns.ticket', [
            'return' => $return,
            'paperWidth' => $this->posPrintService->resolvePaperWidth($return->sale->posTerminal),
        ]);
    }

    /**
     * El mismo ticket en PDF, con el ancho del papel térmico de la terminal. El
     * alto se estima por líneas (DomPDF no ajusta el alto al contenido).
     */
    public function downloadTicketPdf(SaleReturn $return)
    {
        $view = $this->getTicketView($return);
        $widthMm = (int) str_replace('mm', '', $view->getData()['paperWidth']);
        $heightMm = 150 + ($return->items->count() * 12) + ($return->exchange_sale_id ? 25 : 0);

        return Pdf::loadHTML($view->render())
            ->setPaper([0, 0, $widthMm * self::MM_TO_PT, $heightMm * self::MM_TO_PT])
            ->download("{$return->number}.pdf");
    }
}
