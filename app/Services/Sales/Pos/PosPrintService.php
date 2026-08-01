<?php

namespace App\Services\Sales\Pos;

use App\Models\Sales\Pos\PosTerminal;

class PosPrintService
{
    /**
     * Resuelve el ancho de papel a usar para el ticket, con el mismo criterio
     * jerárquico que PosTerminal::getSetting() (terminal → global → default).
     */
    public function resolvePaperWidth(?PosTerminal $terminal): string
    {
        return $terminal?->getSetting('printer_format') ?? pos_config('receipt_size') ?? '80mm';
    }
}
