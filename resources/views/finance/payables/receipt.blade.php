{{-- Recibo de pago a proveedor (EGR), PDF carta — x-pdf.* (v1.5.0 REQ-4.3,
     docs/ui/pdf-documents.md). Recibe $payment (payable.vendor, payable.source, account,
     creator, voider) y $logoSrc (PayablePrintService). --}}
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $payable = $payment->payable;
    $vendor = $payable->vendor;
    $account = $payment->account;

    $metaItems = [
        ['label' => 'Pagado a', 'value' => $vendor?->business_name ?? $payable->concept, 'strong' => true,
            'sub' => $vendor?->tax_id ? $vendor->tax_identifier_label.' '.$vendor->tax_id : null],
        ['label' => 'Cuenta por pagar', 'value' => $payable->number, 'sub' => $payable->concept],
        ['label' => 'Desde la cuenta', 'value' => $account?->name, 'sub' => $account?->bank_label],
        ['label' => 'Referencia', 'value' => $payment->reference],
        ['label' => 'Factura o NCF', 'value' => $payable->supplier_invoice],
        ['label' => 'Registró', 'value' => $payment->creator->name ?? null, 'sub' => $payment->created_at->format('d/m/Y h:i A')],
    ];

    $totalRows = [
        ['label' => 'Total de la cuenta', 'value' => $money($payable->total_amount)],
        ['label' => 'Pendiente después de los pagos', 'value' => $money($payable->balance)],
        ['label' => 'Este pago', 'value' => $money($payment->amount), 'variant' => 'grand'],
    ];
@endphp

<x-pdf.document
    title="Recibo de pago"
    :number="$payment->number"
    :date="$payment->payment_date->format('d/m/Y')"
    dateLabel="Fecha del pago"
    :status="$payment->status_label"
    :statusVariant="$payment->isVoided() ? 'bad' : 'ok'"
    :logoSrc="$logoSrc ?? null"
    footer="Recibo de pago a proveedor · documento interno">

    <x-pdf.meta :items="$metaItems" :cols="3" />

    @if($payment->isVoided())
        <x-pdf.note label="Anulado" variant="danger">
            Anulado por {{ $payment->voider->name ?? '—' }} el {{ $payment->voided_at?->format('d/m/Y h:i A') }}: {{ $payment->void_reason }}. El dinero volvió a la cuenta.
        </x-pdf.note>
    @endif

    <x-pdf.totals :rows="$totalRows">
        @if($payment->notes)
            <span class="label">Nota</span>
            <span class="value">{{ $payment->notes }}</span>
        @endif
    </x-pdf.totals>

    <table class="signature">
        <tr>
            <td><div class="signature-line">{{ $payment->creator->name ?? '' }}</div><span class="small muted">Entregó</span></td>
            <td><div class="signature-line">{{ $vendor?->contact_name ?? '' }}</div><span class="small muted">Recibió</span></td>
        </tr>
    </table>
</x-pdf.document>
