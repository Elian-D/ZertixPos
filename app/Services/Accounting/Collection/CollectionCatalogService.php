<?php

namespace App\Services\Accounting\Collection;

use App\Models\Accounting\ClientCollection;
use App\Models\Accounting\Receivable;
use App\Models\Clients\Client;
use App\Models\Configuration\TipoPago;

class CollectionCatalogService
{
    /**
     * Datos para los filtros de la tabla de Cobros
     */
    public function getForFilters(): array
    {
        return [
            // Solo clientes que han realizado cobros
            'clients' => Client::whereHas('collections')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'paymentMethods' => TipoPago::activo()
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get(),

            'statuses' => ClientCollection::getStatuses(),
        ];
    }

    /**
     * Datos para el formulario de nuevo cobro (Recibo de Cobro)
     */
    public function getForForm(): array
    {
        return [
            // Clientes que actualmente deben dinero
            'clients' => Client::where('balance', '>', 0)
                ->select('id', 'name', 'balance')
                ->orderBy('name')
                ->get(),

            // 'slug' se agrega acá (Fase 6, REQ-6.9) — el form necesita distinguir
            // Efectivo/Tarjeta en el frontend para ocultar el campo de referencia.
            'paymentMethods' => TipoPago::activo()
                ->select('id', 'nombre', 'slug')
                ->orderBy('nombre')
                ->get(),

            // Solo facturas con saldo pendiente (para el selector de factura a pagar)
            'pendingReceivables' => Receivable::whereIn('status', [Receivable::STATUS_UNPAID, Receivable::STATUS_PARTIAL])
                ->select('id', 'client_id', 'document_number', 'current_balance', 'total_amount')
                ->get()
        ];
    }
}
