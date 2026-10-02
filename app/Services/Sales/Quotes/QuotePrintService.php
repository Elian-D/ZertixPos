<?php

namespace App\Services\Sales\Quotes;

use App\Models\Sales\Quotes\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class QuotePrintService
{
    /**
     * Genera el PDF en formato Carta (Letter)
     */
    public function generateLetterPDF(Quote $quote)
    {
        $quote->load(['items.product', 'customer', 'user', 'sale:id,number']);

        // DomPDF necesita el logo como ruta local, no como URL (REQ-1.14).
        $config = general_config();
        $logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;

        return Pdf::loadView('sales.quotes.formats.pdf', compact('quote', 'logoSrc'))
            ->setPaper('letter', 'portrait');
    }

    /**
     * Retorna la vista para impresión térmica (Ticket 80mm)
     */
    public function getTicketView(Quote $quote)
    {
        $quote->load(['items.product', 'customer', 'user', 'terminal']);
        
        return view('sales.quotes.formats.ticket', compact('quote'));
    }
}