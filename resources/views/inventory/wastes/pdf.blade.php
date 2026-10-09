{{-- Merma, PDF carta — x-pdf.* (v1.5.0 REQ-2.2, docs/ui/pdf-documents.md): comprobante para
     firmar. Recibe $waste (warehouse, creator, voider, reference, items.product) y $logoSrc
     (InventoryWastePrintService). --}}
@use('App\Models\Inventory\InventoryWasteItem')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $metaItems = [
        ['label' => 'Almacén', 'value' => $waste->warehouse->name, 'strong' => true],
        ['label' => 'Fecha de la merma', 'value' => $waste->waste_date->format('d/m/Y')],
        ['label' => 'Registró', 'value' => $waste->creator->name ?? null, 'sub' => $waste->created_at->format('d/m/Y h:i A')],
        ['label' => 'Origen', 'value' => $waste->origin['label'] ?? null],
    ];

    $totalRows = $waste->items->groupBy('reason')
        ->map(fn ($items, $reason) => ['label' => InventoryWasteItem::getReasons()[$reason] ?? $reason, 'value' => $money($items->sum('total_value'))])
        ->values()->all();
    $totalRows[] = ['label' => 'Valor perdido', 'value' => $money($waste->total_value), 'variant' => 'grand'];
@endphp

<x-pdf.document
    title="Merma"
    :number="$waste->number"
    :date="$waste->waste_date->format('d/m/Y')"
    dateLabel="Fecha"
    :status="$waste->status_label"
    :statusVariant="$waste->isVoided() ? 'bad' : 'ok'"
    :logoSrc="$logoSrc ?? null"
    footer="Merma de inventario · documento interno">

    <x-pdf.meta :items="$metaItems" />

    @if($waste->isVoided())
        <x-pdf.note label="Anulada" variant="danger">
            Anulada por {{ $waste->voider->name ?? '—' }} el {{ $waste->voided_at?->format('d/m/Y h:i A') }}: {{ $waste->void_reason }}. Las existencias regresaron al almacén.
        </x-pdf.note>
    @endif

    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th style="width: 40%">Producto</th>
                <th style="width: 18%">Motivo</th>
                <th class="num" style="width: 12%">Cantidad</th>
                <th class="num" style="width: 14%">Costo unit.</th>
                <th class="num" style="width: 16%">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($waste->items as $item)
                <tr>
                    <td>
                        <span class="bold">{{ $item->product->name ?? 'Producto eliminado' }}</span>
                        @if($item->notes)
                            <span class="cell-sub">{{ $item->notes }}</span>
                        @elseif($item->product?->sku)
                            <span class="cell-sub">SKU {{ $item->product->sku }}</span>
                        @endif
                    </td>
                    <td>{{ $item->reason_label }}</td>
                    <td class="num bold">{{ $qty($item->quantity) }}</td>
                    <td class="num muted">{{ $money($item->unit_cost) }}</td>
                    <td class="num bold">{{ $money($item->total_value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-pdf.totals :rows="$totalRows">
        <span class="label">Valor perdido</span>
        <span class="value">Valor al costo de la mercancía dada de baja. No es dinero que entra o sale de la caja.</span>
        @if($waste->notes)
            <span class="label" style="margin-top: 6px;">Nota</span>
            <span class="value">{{ $waste->notes }}</span>
        @endif
    </x-pdf.totals>

    <table class="signature">
        <tr>
            <td><div class="signature-line">{{ $waste->creator->name ?? '' }}</div><span class="small muted">Registró</span></td>
            <td><div class="signature-line">&nbsp;</div><span class="small muted">Autorizó</span></td>
        </tr>
    </table>
</x-pdf.document>
