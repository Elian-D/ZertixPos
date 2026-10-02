{{-- Factura, PDF carta — diseño base x-pdf.* (v1.4.0 REQ-3.20 f, docs/ui/pdf-documents.md).
     La misma vista sirve para DomPDF (InvoicePrintService pasa $logoSrc como ruta
     local) y para la vista previa en navegador (sin $logoSrc → tenant_asset() en
     x-pdf.document, REQ-1.14). --}}
@use('App\Models\Sales\Invoice')
@use('App\Models\Sales\Sale')
@php
    $sale = $invoice->sale;
    $client = $sale->client;
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $isCredit = $sale->payment_type === Sale::PAYMENT_CREDIT;
    $isCanceled = $invoice->status === Invoice::STATUS_CANCELLED;

    // Fiscal solo si el módulo está activo y la venta tiene NCF.
    $ncfLog = $sale->ncfLog;
    $isFiscal = module_enabled('sales.ncf') && $sale->ncf;
    $ncfTypeName = $isFiscal ? ($ncfLog?->type?->name ?? 'Comprobante fiscal') : null;

    // Desglose real por tipo de impuesto (Fase 5, REQ-5.6) desde el snapshot de cada línea.
    $taxBreakdown = $sale->items->pluck('tax_breakdown')->filter()->flatten(1)->groupBy('key');

    // 11.2.6: descuento por ítem vs. global — misma reconstrucción que ticket.blade.php
    // (discount_percentage de la línea es la señal de descuento por ítem).
    $itemDiscountTotal = 0;
    $eligibleGrossForGlobal = 0;
    foreach ($sale->items as $saleItem) {
        $lineGross = $saleItem->quantity * $saleItem->unit_price;
        if (($saleItem->discount_percentage ?? 0) > 0) {
            $itemDiscountTotal += ($lineGross * $saleItem->discount_percentage) / 100;
        } else {
            $eligibleGrossForGlobal += $lineGross;
        }
    }
    $globalDiscountTotal = max(0, ($sale->discount_total ?? 0) - $itemDiscountTotal);
    $globalDiscountPct = $eligibleGrossForGlobal > 0 ? ($globalDiscountTotal / $eligibleGrossForGlobal) * 100 : 0;

    $metaItems = [
        ['label' => 'Cliente', 'value' => $client->name, 'strong' => true, 'span' => 2,
            'sub' => collect([
                $client->tax_id ? ($client->tax_identifier_type?->value ?? 'RNC/Cédula').': '.$client->tax_id : null,
                $client->phone ? 'Tel. '.$client->phone : null,
            ])->filter()->join(' · ')],
        ['label' => $ncfLog?->type?->is_electronic ? 'e-NCF' : 'NCF',
            'html' => $isFiscal ? '<span class="mono bold">'.e($sale->ncf).'</span>' : null,
            'sub' => $isFiscal && $ncfLog?->sequence?->expiry_date ? 'Válido hasta '.$ncfLog->sequence->expiry_date->format('d/m/Y') : null],
        ['label' => 'Condición de pago', 'value' => $isCredit ? 'Crédito' : 'Contado', 'strong' => true,
            'sub' => $isCredit && $sale->receivable?->due_date ? 'Vence el '.$sale->receivable->due_date->format('d/m/Y') : null],
        ['label' => 'Venta', 'value' => $sale->number],
        ['label' => 'Vendedor', 'value' => $sale->user->name ?? null],
        ['label' => 'Terminal', 'value' => $sale->posTerminal?->name],
        ['label' => 'Cotización', 'value' => $sale->quote?->number],
    ];

    $totalRows = [
        ['label' => 'Subtotal bruto', 'value' => $money($sale->total_amount)],
        ['label' => 'Descuento por ítems', 'value' => $itemDiscountTotal > 0.01 ? '-'.$money($itemDiscountTotal) : null, 'variant' => 'discount'],
        ['label' => 'Descuento global ('.number_format($globalDiscountPct, 0).'%)', 'value' => $globalDiscountTotal > 0.01 ? '-'.$money($globalDiscountTotal) : null, 'variant' => 'discount'],
        ['label' => 'Subtotal neto', 'value' => $money($sale->net_amount)],
    ];
    foreach ($taxBreakdown as $lines) {
        $totalRows[] = ['label' => $lines->first()['label'], 'value' => $money($lines->sum('amount'))];
    }
    $totalRows[] = ['label' => 'Total', 'value' => $money($sale->grand_total), 'variant' => 'grand'];
    if (! $isCredit && $sale->cash_received > 0) {
        $totalRows[] = ['label' => 'Efectivo recibido', 'value' => $money($sale->cash_received), 'variant' => 'sep'];
        $totalRows[] = ['label' => 'Cambio', 'value' => $money($sale->cash_change)];
    }
@endphp

<x-pdf.document
    title="Factura"
    :number="$invoice->invoice_number"
    :subtitle="$ncfTypeName"
    :date="$sale->sale_date->format('d/m/Y h:i A')"
    dateLabel="Emisión"
    :status="$isCanceled ? 'Anulada' : null"
    statusVariant="bad"
    :logoSrc="$logoSrc ?? null"
    :footer="$isFiscal ? $ncfTypeName : 'Comprobante interno sin valor fiscal'">

    <x-pdf.meta :items="$metaItems" />

    @if($isCanceled)
        <x-pdf.note label="Factura anulada" variant="danger">
            {{ $sale->cancellation_reason ?? $ncfLog?->cancellation_reason ?? 'Sin motivo registrado.' }}
            @if($sale->canceled_at)
                <span class="cell-sub">{{ $sale->canceled_at->format('d/m/Y h:i A') }}</span>
            @endif
        </x-pdf.note>
    @endif

    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th class="center" style="width: 9%">Cant.</th>
                <th style="width: 55%">Descripción</th>
                <th class="num" style="width: 18%">Precio unit.</th>
                <th class="num" style="width: 18%">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td class="center bold">{{ $qty($item->quantity) }}</td>
                    <td>
                        <span class="bold">{{ $item->product->name }}</span>
                        @if($item->product->sku)
                            <span class="cell-sub">SKU {{ $item->product->sku }}</span>
                        @endif
                        @if(($item->discount_percentage ?? 0) > 0)
                            <span class="cell-sub discount">Desc. {{ number_format($item->discount_percentage, 0) }}%: -{{ $money(($item->quantity * $item->unit_price * $item->discount_percentage) / 100) }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $money($item->unit_price) }}</td>
                    <td class="num bold">{{ $money($item->quantity * $item->unit_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-pdf.totals :rows="$totalRows">
        <span class="label">Forma de pago</span>
        @if($isCredit)
            <span class="value">A crédito{{ $sale->receivable?->number ? ' · '.$sale->receivable->number : '' }}</span>
        @elseif($sale->payments->count() > 1)
            @foreach($sale->payments as $payment)
                <div class="value">{{ $payment->tipoPago?->nombre }}: <span class="bold">{{ $money($payment->amount) }}</span></div>
            @endforeach
        @else
            <span class="value">{{ $sale->payments->first()?->tipoPago?->nombre ?? $sale->tipoPago?->nombre ?? 'Efectivo' }}</span>
        @endif

        @if($sale->notes)
            <div style="margin-top: 12px;">
                <span class="label">Observaciones</span>
                <span class="value">{{ $sale->notes }}</span>
            </div>
        @endif
    </x-pdf.totals>

    @if($isCredit)
        <table class="signature">
            <tr>
                <td><div class="signature-line">{{ $sale->user->name ?? 'Vendedor' }}</div><span class="small muted">Entregado por</span></td>
                <td><div class="signature-line">{{ $client->name }}</div><span class="small muted">Recibido conforme (firma y sello)</span></td>
            </tr>
        </table>
    @endif
</x-pdf.document>
