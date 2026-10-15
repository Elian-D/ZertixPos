{{-- Recepción de compra, PDF carta — x-pdf.* (v1.5.0 REQ-5.2, docs/ui/pdf-documents.md).
     Constancia de lo que llegó, lo rechazado con su motivo y lo que se debe o se pagó.
     Recibe $reception (order.vendor, order.warehouse, account, creator, items.product,
     payable) y $logoSrc (PurchasePrintService). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $order = $reception->order;
    $payable = $reception->payable;

    $metaItems = [
        ['label' => 'Proveedor', 'value' => $order->vendor?->business_name, 'strong' => true, 'span' => 2],
        ['label' => 'Orden', 'value' => $order->number, 'strong' => true],
        ['label' => 'Almacén', 'value' => $order->warehouse->name ?? null],
        ['label' => 'Factura del proveedor', 'value' => $reception->supplier_invoice],
        ['label' => 'NCF del proveedor', 'value' => $reception->supplier_ncf],
        ['label' => $order->isCash() ? 'Pagado desde' : 'Cuenta por pagar', 'value' => $order->isCash() ? $reception->account?->name : $payable?->number,
            'sub' => ! $order->isCash() && $payable ? 'Vence '.$payable->due_date->format('d/m/Y') : null],
        ['label' => 'Calificación', 'value' => $reception->rating],
    ];
    $rejected = $reception->items->sum('quantity_rejected');
    $totalRows = [['label' => 'Unidades recibidas', 'value' => $qty($reception->items->sum('quantity_received'))]];
    if ($rejected > 0) {
        $totalRows[] = ['label' => 'Unidades rechazadas', 'value' => $qty($rejected)];
    }
    $totalRows[] = ['label' => 'Total recibido', 'value' => $money($reception->total), 'variant' => 'grand'];
@endphp

<x-pdf.document
    title="Recepción de compra"
    :number="$reception->number"
    :date="$reception->reception_date->format('d/m/Y')"
    :status="$order->isCash() ? 'Contado' : 'Crédito'"
    statusVariant="info"
    :logoSrc="$logoSrc ?? null"
    footer="Recepción de compra · documento interno">

    <x-pdf.meta :items="$metaItems" :cols="4" />

    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th style="width: 40%">Producto</th>
                <th class="num" style="width: 12%">Recibido</th>
                <th class="num" style="width: 16%">Rechazado</th>
                <th class="num" style="width: 16%">Costo unitario</th>
                <th class="num" style="width: 16%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reception->items as $item)
                <tr>
                    <td>
                        <span class="bold">{{ $item->product->name ?? 'Producto eliminado' }}</span>
                        @if($item->quantity_rejected > 0)
                            <span class="cell-sub">Rechazo: {{ $item->reject_reason_label }}</span>
                        @elseif($item->product?->sku)
                            <span class="cell-sub">SKU {{ $item->product->sku }}</span>
                        @endif
                    </td>
                    <td class="num bold">{{ $qty($item->quantity_received) }}</td>
                    <td class="num {{ $item->quantity_rejected > 0 ? 'discount bold' : 'muted' }}">{{ $item->quantity_rejected > 0 ? $qty($item->quantity_rejected) : '—' }}</td>
                    <td class="num">{{ $money($item->unit_cost) }}</td>
                    <td class="num bold">{{ $money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-pdf.totals :rows="$totalRows">
        @if($reception->notes)
            <span class="label">Nota</span>
            <span class="value">{{ $reception->notes }}</span>
        @endif
    </x-pdf.totals>

    <table class="signature">
        <tr>
            <td><div class="signature-line"></div><span class="small muted">Entregó (proveedor)</span></td>
            <td><div class="signature-line">{{ $reception->creator->name ?? '' }}</div><span class="small muted">Recibió</span></td>
        </tr>
    </table>
</x-pdf.document>
