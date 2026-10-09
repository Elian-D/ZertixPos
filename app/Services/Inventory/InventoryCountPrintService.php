<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryCount;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryCountPrintService
{
    /** PDF carta de la toma física (x-pdf.*, docs/ui/pdf-documents.md). */
    public function generateLetterPDF(InventoryCount $count)
    {
        $count->load([
            'warehouse:id,name', 'category:id,name', 'creator:id,name', 'applier:id,name',
            'items' => fn ($q) => $q->with('product:id,name,sku,cost')
                ->join('products', 'products.id', '=', 'inventory_count_items.product_id')
                ->orderBy('products.name')
                ->select('inventory_count_items.*'),
        ]);

        // DomPDF necesita el logo como ruta local, no como URL (REQ-1.14).
        $config = general_config();
        $logoSrc = $config->logo ? storage_path('app/public/'.$config->logo) : null;

        return Pdf::loadView('inventory.counts.pdf', compact('count', 'logoSrc'))
            ->setPaper('letter', 'portrait');
    }
}
