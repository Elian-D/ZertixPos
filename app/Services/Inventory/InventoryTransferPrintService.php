<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryTransfer;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryTransferPrintService
{
    /** PDF carta de despacho (x-pdf.*, docs/ui/pdf-documents.md): para firmar al enviar y al recibir. */
    public function generateLetterPDF(InventoryTransfer $transfer)
    {
        $transfer->load([
            'fromWarehouse:id,name', 'toWarehouse:id,name', 'creator:id,name', 'sender:id,name', 'receiver:id,name',
            'items.product:id,name,sku', 'waste:id,number,reference_type,reference_id',
        ]);

        // DomPDF necesita el logo como ruta local, no como URL (REQ-1.14).
        $config = general_config();
        $logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;

        return Pdf::loadView('inventory.transfers.pdf', compact('transfer', 'logoSrc'))
            ->setPaper('letter', 'portrait');
    }
}
