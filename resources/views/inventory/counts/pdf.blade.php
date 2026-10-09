{{-- Toma física, PDF carta — x-pdf.* (v1.5.0 REQ-2.1, docs/ui/pdf-documents.md).
     Recibe $count (warehouse, category, creator, applier, items.product) y $logoSrc
     (InventoryCountPrintService).
     - Con comparación: las líneas contadas con existencia al iniciar, contado, diferencia
       y valor, el resumen y las firmas de quien contó y quien supervisó.
     - Conteo ciego en borrador (sin permiso de aplicar): hoja de conteo sin la existencia
       del sistema, con todas las líneas y la columna "Contado" para llenar a mano. --}}
@use('App\Models\Inventory\InventoryCount')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => ($v < 0 ? '-' : '').$currency.number_format(abs((float) $v), 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $showComparison = $count->differencesVisibleTo(auth()->user());

    $counted = $count->items->filter->isCounted();
    $pending = $count->items->count() - $counted->count();
    $lines = $showComparison ? $counted : $count->items;
    $shortValue = $counted->filter(fn ($i) => $i->current_difference < 0)->sum(fn ($i) => $i->difference_value);
    $surplusValue = $counted->filter(fn ($i) => $i->current_difference > 0)->sum(fn ($i) => $i->difference_value);
    $netValue = $count->isApplied() ? (float) $count->difference_value : $shortValue + $surplusValue;

    $statusVariant = match ($count->status) {
        InventoryCount::STATUS_APPLIED => 'ok',
        InventoryCount::STATUS_CANCELED => 'bad',
        default => 'warn',
    };

    $metaItems = [
        ['label' => 'Almacén', 'value' => $count->warehouse->name, 'strong' => true],
        ['label' => 'Alcance', 'value' => $count->scope_label],
        ['label' => 'Conteo ciego', 'value' => $count->blind ? 'Sí' : 'No'],
        ['label' => 'Contados', 'value' => $counted->count().' de '.$count->items->count()],
        ['label' => 'Creada por', 'value' => $count->creator->name ?? null, 'sub' => $count->created_at->format('d/m/Y h:i A')],
        ['label' => 'Aplicada por', 'value' => $count->applier->name ?? null, 'sub' => $count->applied_at?->format('d/m/Y h:i A')],
    ];

    $totalRows = [
        ['label' => 'Faltantes', 'value' => $money($shortValue), 'variant' => 'discount'],
        ['label' => 'Sobrantes', 'value' => ($surplusValue > 0 ? '+' : '').$money($surplusValue)],
        ['label' => 'Valor neto ajustado', 'value' => ($netValue > 0 ? '+' : '').$money($netValue), 'variant' => 'grand'],
    ];
@endphp

<x-pdf.document
    title="Toma física"
    :number="$count->number"
    :date="$count->created_at->format('d/m/Y')"
    dateLabel="Fecha"
    :status="$count->status_label"
    :statusVariant="$statusVariant"
    :logoSrc="$logoSrc ?? null"
    footer="Toma física de inventario · documento interno">

    <x-pdf.meta :items="$metaItems" :cols="3" />

    @if($count->status === InventoryCount::STATUS_CANCELED)
        <x-pdf.note label="Cancelada" variant="danger">
            Esta toma física fue cancelada y no ajustó ninguna existencia.
        </x-pdf.note>
    @elseif($count->isDraft() && $showComparison)
        <x-pdf.note label="Borrador" variant="alert">
            Las diferencias se calculan contra la existencia al crear la toma ({{ $count->created_at->format('d/m/Y h:i A') }}) y se valoran al costo actual. Todavía no ajustan el inventario.
        </x-pdf.note>
    @endif

    @if($showComparison)
        <table class="data-table" style="margin-top: 4px;">
            <thead>
                <tr>
                    <th style="width: 38%">Producto</th>
                    <th class="num" style="width: 11%">Al iniciar</th>
                    <th class="num" style="width: 11%">Contado</th>
                    <th class="num" style="width: 12%">Diferencia</th>
                    <th class="num" style="width: 13%">Costo unit.</th>
                    <th class="num" style="width: 15%">Valor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $item)
                    @php $diff = $item->current_difference; @endphp
                    <tr>
                        <td>
                            <span class="bold">{{ $item->product->name ?? 'Producto eliminado' }}</span>
                            @if($item->product?->sku)
                                <span class="cell-sub">SKU {{ $item->product->sku }}</span>
                            @endif
                        </td>
                        <td class="num">{{ $qty($item->system_quantity) }}</td>
                        <td class="num bold">{{ $qty($item->counted_quantity) }}</td>
                        <td class="num bold {{ $diff < 0 ? 'discount' : '' }}">{{ $diff == 0 ? '—' : ($diff > 0 ? '+' : '').$qty($diff) }}</td>
                        <td class="num muted">{{ $money($item->unit_cost ?? $item->product->cost ?? 0) }}</td>
                        <td class="num {{ $item->difference_value < 0 ? 'discount' : '' }}">{{ $item->difference_value == 0 ? '—' : ($item->difference_value > 0 ? '+' : '').$money($item->difference_value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center muted">Todavía no se ha contado ningún producto.</td></tr>
                @endforelse
            </tbody>
        </table>

        <x-pdf.totals :rows="$totalRows">
            <span class="label">Valor ajustado</span>
            <span class="value">Es el valor al costo de la mercancía que faltó o apareció. No es dinero que entra o sale de la caja.</span>
            @if($pending > 0)
                <span class="label" style="margin-top: 6px;">Sin contar</span>
                <span class="value">{{ $pending }} {{ $pending === 1 ? 'producto' : 'productos' }} del alcance no se contaron y no generan ajuste.</span>
            @endif
            @if($count->notes)
                <span class="label" style="margin-top: 6px;">Notas</span>
                <span class="value">{{ $count->notes }}</span>
            @endif
        </x-pdf.totals>
    @else
        {{-- Hoja de conteo ciego: sin la existencia del sistema --}}
        <table class="data-table" style="margin-top: 4px;">
            <thead>
                <tr>
                    <th style="width: 50%">Producto</th>
                    <th style="width: 25%">SKU / Código</th>
                    <th class="num" style="width: 25%">Contado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $item)
                    <tr>
                        <td class="bold">{{ $item->product->name ?? 'Producto eliminado' }}</td>
                        <td class="muted">{{ collect([$item->product?->sku, $item->product?->barcode])->filter()->join(' · ') ?: '—' }}</td>
                        <td class="num bold">{{ $item->isCounted() ? $qty($item->counted_quantity) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="signature">
        <tr>
            <td><div class="signature-line">{{ $count->creator->name ?? '' }}</div><span class="small muted">Contó</span></td>
            <td><div class="signature-line">{{ $count->applier->name ?? '' }}</div><span class="small muted">Supervisó</span></td>
        </tr>
    </table>
</x-pdf.document>
