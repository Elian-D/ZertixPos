{{-- Transferencia, PDF carta de despacho — x-pdf.* (v1.5.0 REQ-2.4, docs/ui/pdf-documents.md).
     Se imprime al enviar (columna "Recibido" en blanco para llenar a mano) y queda como
     constancia al recibir. Recibe $transfer (almacenes, usuarios, items.product, waste) y
     $logoSrc (InventoryTransferPrintService). --}}
@use('App\Models\Inventory\InventoryTransfer')
@php
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $statusVariant = match ($transfer->status) {
        InventoryTransfer::STATUS_RECEIVED => 'ok',
        InventoryTransfer::STATUS_SENT => 'info',
        InventoryTransfer::STATUS_CANCELED => 'bad',
        default => 'warn',
    };

    $metaItems = [
        ['label' => 'Desde', 'value' => $transfer->fromWarehouse->name, 'strong' => true],
        ['label' => 'Hacia', 'value' => $transfer->toWarehouse->name, 'strong' => true],
        ['label' => 'Envió', 'value' => $transfer->sender->name ?? null, 'sub' => $transfer->sent_at?->format('d/m/Y h:i A')],
        ['label' => 'Recibió', 'value' => $transfer->receiver->name ?? null, 'sub' => $transfer->received_at?->format('d/m/Y h:i A')],
        ['label' => 'Pérdida en tránsito', 'value' => $transfer->waste?->number],
    ];
    $totalSent = $transfer->items->sum('quantity_sent');
    $totalReceived = $transfer->isReceived() ? $transfer->items->sum('quantity_received') : null;

    $totalRows = [['label' => 'Unidades enviadas', 'value' => $qty($totalSent)]];
    if ($totalReceived !== null) {
        $totalRows[] = ['label' => 'Unidades recibidas', 'value' => $qty($totalReceived), 'variant' => 'grand'];
    }
@endphp

<x-pdf.document
    title="Transferencia"
    :number="$transfer->number"
    :date="($transfer->sent_at ?? $transfer->created_at)->format('d/m/Y')"
    :dateLabel="$transfer->sent_at ? 'Envío' : 'Fecha'"
    :status="$transfer->status_label"
    :statusVariant="$statusVariant"
    :logoSrc="$logoSrc ?? null"
    footer="Transferencia entre almacenes · documento interno">

    <x-pdf.meta :items="$metaItems" :cols="3" />

    @if($transfer->status === InventoryTransfer::STATUS_CANCELED)
        <x-pdf.note label="Cancelada" variant="danger">Esta transferencia fue cancelada y no movió existencias.</x-pdf.note>
    @elseif($transfer->isDraft())
        <x-pdf.note label="Borrador" variant="alert">Todavía no se envió: la mercancía sigue en {{ $transfer->fromWarehouse->name }}.</x-pdf.note>
    @endif

    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th style="width: 52%">Producto</th>
                <th class="num" style="width: 16%">Enviado</th>
                <th class="num" style="width: 16%">Recibido</th>
                <th class="num" style="width: 16%">Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $item)
                <tr>
                    <td>
                        <span class="bold">{{ $item->product->name ?? 'Producto eliminado' }}</span>
                        @if($item->notes)
                            <span class="cell-sub">{{ $item->notes }}</span>
                        @elseif($item->product?->sku)
                            <span class="cell-sub">SKU {{ $item->product->sku }}</span>
                        @endif
                    </td>
                    <td class="num bold">{{ $qty($item->quantity_sent) }}</td>
                    {{-- En tránsito queda en blanco: se llena a mano al recibir --}}
                    <td class="num">{{ $item->quantity_received !== null ? $qty($item->quantity_received) : '' }}</td>
                    <td class="num {{ $item->shortage > 0 ? 'discount bold' : 'muted' }}">{{ $item->shortage > 0 ? '-'.$qty($item->shortage) : ($item->quantity_received !== null ? '—' : '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-pdf.totals :rows="$totalRows">
        @if($transfer->notes)
            <span class="label">Nota</span>
            <span class="value">{{ $transfer->notes }}</span>
        @endif
        @if($transfer->waste)
            <span class="label" style="margin-top: 6px;">Pérdida en tránsito</span>
            <span class="value">Lo que no llegó se dio de baja en la merma {{ $transfer->waste->number }}.</span>
        @endif
    </x-pdf.totals>

    <table class="signature">
        <tr>
            <td><div class="signature-line">{{ $transfer->sender->name ?? '' }}</div><span class="small muted">Despachó</span></td>
            <td><div class="signature-line">{{ $transfer->receiver->name ?? '' }}</div><span class="small muted">Recibió</span></td>
        </tr>
    </table>
</x-pdf.document>
