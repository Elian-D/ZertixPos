<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryWaste;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryWastePrintService
{
    /** PDF carta de la merma (x-pdf.*, docs/ui/pdf-documents.md): comprobante para firmar. */
    public function generateLetterPDF(InventoryWaste $waste)
    {
        $waste->load([
            'warehouse:id,name', 'creator:id,name', 'voider:id,name', 'reference',
            'items' => fn ($q) => $q->with('product:id,name,sku'),
        ]);

        // DomPDF necesita el logo como ruta local, no como URL (REQ-1.14).
        $config = general_config();
        $logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;

        return Pdf::loadView('inventory.wastes.pdf', compact('waste', 'logoSrc'))
            ->setPaper('letter', 'portrait');
    }
}
