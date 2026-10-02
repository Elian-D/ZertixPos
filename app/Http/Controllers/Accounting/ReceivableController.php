<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Receivable;
use App\Models\Sales\Returns\SaleReturn;

/**
 * Categoría C (docs/analisis/politica-soft-deletes.md) — CxC es la bitácora de
 * deuda de un cliente: nunca se borra ni se archiva, ni siquiera cuando el
 * documento origen fue anulado (eso ya lo refleja el `status` de la fila).
 * Sin SoftDeletesTrait, sin destroy() — este controlador es de solo lectura.
 */
class ReceivableController extends Controller
{
    /**
     * Listado migrado a Livewire — ver App\Livewire\App\Finance\ReceivableTable.
     */
    public function index()
    {
        return view('accounting.receivables.index');
    }

    /**
     * Detalle de la CxC (v1.4.0 Fase 3, patrón Infolist — /filament-show).
     * Reemplaza el modal "view-receivable" del listado.
     */
    public function show(Receivable $receivable)
    {
        $receivable->load([
            'client:id,name,commercial_name,tax_id,phone',
            'accountingAccount:id,code,name',
            'journalEntry:id,reference',
            'collections' => fn ($q) => $q->with(['tipoPago', 'creator:id,name'])->latest('payment_date')->latest('id'),
            'saleReturns' => fn ($q) => $q->where('refund_method', SaleReturn::METHOD_RECEIVABLE)->with('user:id,name')->latest(),
        ]);

        // Solo las devoluciones vigentes bajan la deuda (las anuladas ya se revirtieron).
        $receivable->returned_amount = (float) $receivable->saleReturns
            ->where('status', SaleReturn::STATUS_COMPLETED)
            ->sum('refund_value');

        return view('accounting.receivables.show', [
            'receivable' => $receivable,
            'sale' => $receivable->reference_type === \App\Models\Sales\Sale::class
                ? \App\Models\Sales\Sale::select('id', 'number', 'sale_date', 'pos_terminal_id')->find($receivable->reference_id)
                : null,
        ]);
    }
}
