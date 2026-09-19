<?php

namespace App\Http\Requests\Sales\Pos;

use App\Http\Requests\Sales\StoreSaleRequest;

/**
 * Checkout del POS — mismas reglas de validación que el backoffice
 * (StoreSaleRequest, extendida sin duplicar ~200 líneas de lógica), pero
 * autorizada por `pos_sessions.manage`, no `sales.create` (REQ-2.4, v1.3.0
 * Fase 2). `sales.create` es un permiso de oficina ("Nueva Venta" en el
 * backoffice); un cajero con `pos_sessions.manage` puede abrir turno, armar
 * el carrito y todo el flujo del Workspace, pero recibía 403 al confirmar
 * porque StoreSaleRequest exigía además `sales.create`, que nadie le daba —
 * un permiso que ni siquiera suena a "vender en el POS".
 */
class StorePosSaleRequest extends StoreSaleRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pos_sessions.manage');
    }
}
