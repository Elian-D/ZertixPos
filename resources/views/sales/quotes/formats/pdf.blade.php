{{-- Cotización, PDF carta — diseño base x-pdf.* (v1.4.0 REQ-3.20 f, docs/ui/pdf-documents.md).
     Recibe $quote (items.product, customer, user, sale) y $logoSrc (QuotePrintService). --}}
@use('App\Models\Sales\Quotes\Quote')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $statusVariant = match ($quote->status) {
        Quote::STATUS_APPROVED => 'ok',
        Quote::STATUS_CONVERTED => 'info',
        Quote::STATUS_EXPIRED, Quote::STATUS_CANCELLED => 'bad',
        default => 'muted',
    };

    // Vigencia: solo tiene sentido mientras la cotización sigue abierta.
    $isOpen = in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_APPROVED], true);
    $daysLeft = (int) now()->startOfDay()->diffInDays($quote->expires_at->copy()->startOfDay(), false);
    $validitySub = ! $isOpen ? null : ($daysLeft < 0
        ? 'Venció hace '.abs($daysLeft).' '.(abs($daysLeft) === 1 ? 'día' : 'días')
        : ($daysLeft === 0 ? 'Vence hoy' : 'Quedan '.$daysLeft.' '.($daysLeft === 1 ? 'día' : 'días')));

    $hasDiscounts = $quote->items->contains(fn ($i) => $i->discount_amount > 0);

    $metaItems = [
        ['label' => 'Cliente', 'value' => $quote->customer->name, 'strong' => true, 'span' => 2,
            'sub' => collect([
                $quote->customer->tax_id ? 'RNC/Cédula: '.$quote->customer->tax_id : null,
                $quote->customer->phone ? 'Tel. '.$quote->customer->phone : null,
            ])->filter()->join(' · ')],
        ['label' => 'Válida hasta', 'value' => $quote->expires_at->format('d/m/Y'), 'strong' => true, 'sub' => $validitySub],
        ['label' => 'Vendedor', 'value' => $quote->user->name ?? null],
        ['label' => 'Convertida en venta', 'value' => $quote->sale?->number],
    ];

    $totalRows = [
        ['label' => 'Subtotal', 'value' => $money($quote->subtotal)],
        ['label' => 'Descuento', 'value' => $quote->discount_total > 0 ? '-'.$money($quote->discount_total) : null, 'variant' => 'discount'],
    ];
    // Desglose real por tipo de impuesto (Fase 5, REQ-5.12), snapshot de quote_items.
    foreach ($quote->items->pluck('tax_breakdown')->filter()->flatten(1)->groupBy('key') as $lines) {
        $totalRows[] = ['label' => $lines->first()['label'], 'value' => $money($lines->sum('amount'))];
    }
    $totalRows[] = ['label' => 'Total', 'value' => $money($quote->grand_total), 'variant' => 'grand'];
@endphp

<x-pdf.document
    title="Cotización"
    :number="$quote->number"
    :date="$quote->created_at->format('d/m/Y')"
    dateLabel="Emisión"
    :status="Quote::getStatuses()[$quote->status] ?? $quote->status"
    :statusVariant="$statusVariant"
    :logoSrc="$logoSrc ?? null"
    footer="Cotización · no es un comprobante fiscal">

    <x-pdf.meta :items="$metaItems" />

    <table class="data-table" style="margin-top: 4px;">
        <thead>
            <tr>
                <th class="center" style="width: 9%">Cant.</th>
                <th style="width: {{ $hasDiscounts ? 43 : 55 }}%">Descripción</th>
                <th class="num" style="width: 18%">Precio unit.</th>
                @if($hasDiscounts)
                    <th class="num" style="width: 12%">Descuento</th>
                @endif
                <th class="num" style="width: 18%">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
                <tr>
                    <td class="center bold">{{ $qty($item->quantity) }}</td>
                    <td>
                        <span class="bold">{{ $item->product->name }}</span>
                        @if($item->product->sku)
                            <span class="cell-sub">SKU {{ $item->product->sku }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $money($item->price) }}</td>
                    @if($hasDiscounts)
                        <td class="num {{ $item->discount_amount > 0 ? 'discount' : 'muted' }}">{{ $item->discount_amount > 0 ? '-'.$money($item->discount_amount) : '—' }}</td>
                    @endif
                    <td class="num bold">{{ $money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-pdf.totals :rows="$totalRows">
        @if($quote->notes)
            <span class="label">Observaciones</span>
            <span class="value">{{ $quote->notes }}</span>
        @endif
    </x-pdf.totals>

    @if($isOpen && $daysLeft < 0)
        <x-pdf.note label="Cotización vencida" variant="alert">
            Esta cotización venció el <strong>{{ $quote->expires_at->format('d/m/Y') }}</strong>. Los precios y condiciones deben confirmarse de nuevo antes de facturar.
        </x-pdf.note>
    @elseif($isOpen)
        <x-pdf.note label="Vigencia" variant="info">
            Precios y condiciones válidos hasta el <strong>{{ $quote->expires_at->format('d/m/Y') }}</strong>. Pasada esa fecha, la cotización debe confirmarse de nuevo.
        </x-pdf.note>
    @endif
</x-pdf.document>
