<?php

namespace App\Services\Purchases;

use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseReception;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDFs carta de compras (x-pdf.*, docs/ui/pdf-documents.md): la orden y la recepción. */
class PurchasePrintService
{
    /** El documento que se le envía al proveedor (WhatsApp o papel). */
    public function order(PurchaseOrder $order)
    {
        $order->load(['vendor', 'warehouse:id,name', 'creator:id,name', 'approver:id,name', 'items.product:id,name,sku,barcode']);

        return Pdf::loadView('purchases.orders.pdf', ['order' => $order, 'logoSrc' => $this->logo()])
            ->setPaper('letter', 'portrait');
    }

    /** Constancia de lo que llegó, lo rechazado y lo que se debe o se pagó. */
    public function reception(PurchaseReception $reception)
    {
        $reception->load([
            'order.vendor', 'order.warehouse:id,name', 'account:id,name', 'creator:id,name',
            'items.product:id,name,sku', 'payable:id,source_type,source_id,number,status,due_date',
        ]);

        return Pdf::loadView('purchases.receptions.pdf', ['reception' => $reception, 'logoSrc' => $this->logo()])
            ->setPaper('letter', 'portrait');
    }

    /** DomPDF necesita el logo como ruta local, no como URL (REQ-1.14). */
    private function logo(): ?string
    {
        $config = general_config();

        return $config->logo ? storage_path('app/public/'.$config->logo) : null;
    }
}
