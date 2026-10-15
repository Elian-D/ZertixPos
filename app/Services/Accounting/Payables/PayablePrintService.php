<?php

namespace App\Services\Accounting\Payables;

use App\Models\Accounting\PayablePayment;
use Barryvdh\DomPDF\Facade\Pdf;

class PayablePrintService
{
    /** Recibo de pago a proveedor (EGR), PDF carta (x-pdf.*, docs/ui/pdf-documents.md). */
    public function paymentReceipt(PayablePayment $payment)
    {
        $payment->load(['payable.vendor', 'payable.source', 'account:id,name,type,bank_name,last_four_digits', 'creator:id,name', 'voider:id,name']);

        // DomPDF necesita el logo como ruta local, no como URL (REQ-1.14).
        $config = general_config();
        $logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;

        return Pdf::loadView('finance.payables.receipt', compact('payment', 'logoSrc'))
            ->setPaper('letter', 'portrait');
    }
}
