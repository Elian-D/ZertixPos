<?php

namespace App\Services\Sales\InvoicesServices;

use App\Models\Sales\Invoice;
use App\Models\Sales\Sale;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    /**
     * Crea el registro legal de la factura a partir de una venta.
     */
    public function createFromSale(Sale $sale): Invoice
    {
        return Invoice::create([
            'sale_id' => $sale->id,
            'invoice_number' => $sale->number, // Usamos el mismo folio de la venta por consistencia legal
            'type' => $sale->payment_type,
            'format_type' => $this->determineFormat($sale),
            'status' => Invoice::STATUS_ACTIVE,
            // Se lee de la Receivable ya creada por SaleService (REQ-11.9) — nunca se
            // recalcula acá, para no divergir del dato que de verdad rige esMoroso().
            'due_date' => $sale->receivable?->due_date,
            'generated_by' => Auth::user()->name ?? 'Sistema',
        ]);
    }

    /**
     * Marca la factura como anulada.
     */
    public function cancelInvoice(Sale $sale): void
    {
        if ($sale->invoice) {
            $sale->invoice->update(['status' => Invoice::STATUS_CANCELLED]);
        }
    }

    /**
     * Lógica para determinar el formato de impresión (Ticket, Carta, Ruta).
     */
    private function determineFormat(Sale $sale): string
    {
        // Si la venta tiene una ruta asignada (ajustar según tu modelo de rutas futuro)
        if (isset($sale->route_id)) {
            return Invoice::FORMAT_ROUTE;
        }

        // Si es crédito en oficina, suele ser hoja completa
        if ($sale->payment_type === Sale::PAYMENT_CREDIT) {
            return Invoice::FORMAT_LETTER;
        }

        // Por defecto, ticket de planta
        return Invoice::FORMAT_TICKET;
    }
}
