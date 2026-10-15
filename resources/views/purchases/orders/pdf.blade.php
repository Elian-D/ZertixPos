{{-- Orden de compra, PDF carta — x-pdf.* (v1.5.0 REQ-5.1, docs/ui/pdf-documents.md).
     El documento que se le envía al proveedor (WhatsApp o papel): qué se pide, a qué costo
     y cómo se paga. Recibe $order (vendor, warehouse, creator, approver, items.product) y
     $logoSrc (PurchasePrintService). --}}
@use('App\Models\Purchases\PurchaseOrder')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
    $vendor = $order->vendor;

    $statusVariant = match ($order->status) {
        PurchaseOrder::STATUS_RECEIVED => 'ok',
        PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIAL => 'info',
        PurchaseOrder::STATUS_CANCELED => 'bad',
        default => 'warn',
    };

    $metaItems = [
        ['label' => 'Proveedor', 'value' => $vendor?->business_name, 'strong' => true, 'span' => 2,
            'sub' => $vendor?->tax_id ? $vendor->tax_identifier_label.' '.$vendor->tax_id : null],
        ['label' => 'Contacto', 'value' => $vendor?->contact_name ?: $vendor?->phone, 'sub' => $vendor?->contact_name ? $vendor?->phone : null],
        ['label' => 'Entregar en', 'value' => $order->warehouse->name ?? null, 'strong' => true],
        ['label' => 'Condición de pago', 'value' => $order->condition_label.(! $order->isCash() && $vendor?->credit_days ? ' · '.$vendor->credit_days.' días' : '')],
        ['label' => 'Entrega estimada', 'value' => $order->expected_date?->format('d/m/Y')],
        ['label' => 'Aprobó', 'value' => $order->approver->name ?? null, 'sub' => $order->approved_at?->format('d/m/Y')],
    ];
@endphp

<x-pdf.document
    title="Orden de compra"
    :number="$order->number"
    :date="$order->order_date->format('d/m/Y')"
    :status="$order->status_label"
    :statusVariant="$statusVariant"
    :logoSrc="$logoSrc ?? null"
    footer="Orden de compra · los precios son los acordados con el proveedor">

    <x-pdf.meta :items="$metaItems" :cols="4" />

    @if($order->status === PurchaseOrder::STATUS_CANCELED)
        <x-pdf.note label="Cancelada" variant="danger">Esta orden fue cancelada: no se debe despachar.</x-pdf.note>
    @elseif($order->isDraft())
        <x-pdf.note label="Borrador" variant="alert">Esta orden todavía no está aprobada.</x-pdf.note>
    @endif

    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th style="width: 50%">Producto</th>
                <th class="num" style="width: 14%">Cantidad</th>
                <th class="num" style="width: 18%">Costo unitario</th>
                <th class="num" style="width: 18%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <span class="bold">{{ $item->product->name ?? 'Producto eliminado' }}</span>
                        @if($item->product?->sku || $item->product?->barcode)
                            <span class="cell-sub">{{ collect([$item->product->sku, $item->product->barcode])->filter()->join(' · ') }}</span>
                        @endif
                    </td>
                    <td class="num bold">{{ $qty($item->quantity) }}</td>
                    <td class="num">{{ $money($item->unit_cost) }}</td>
                    <td class="num bold">{{ $money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-pdf.totals :rows="[
        ['label' => 'Unidades', 'value' => $qty($order->items->sum('quantity'))],
        ['label' => 'Total', 'value' => $money($order->total), 'variant' => 'grand'],
    ]">
        @if($order->notes)
            <span class="label">Nota</span>
            <span class="value">{{ $order->notes }}</span>
        @endif
    </x-pdf.totals>

    <table class="signature">
        <tr>
            <td><div class="signature-line">{{ $order->approver->name ?? $order->creator->name ?? '' }}</div><span class="small muted">Por el negocio</span></td>
            <td><div class="signature-line"></div><span class="small muted">Recibido por el proveedor</span></td>
        </tr>
    </table>
</x-pdf.document>
