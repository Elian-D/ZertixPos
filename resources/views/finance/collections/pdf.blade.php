{{-- Recibo de cobro, PDF carta — diseño base x-pdf.* (v1.4.0 REQ-3.20 f, docs/ui/pdf-documents.md).
     Misma vista para DomPDF (CollectionPrintService pasa $logoSrc como ruta local)
     y para la vista previa en navegador (sin $logoSrc → tenant_asset(), REQ-1.14). --}}
@use('App\Models\Accounting\ClientCollection')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);

    $receivable = $payment->receivable;
    $isCanceled = $payment->status === ClientCollection::STATUS_CANCELLED;

    $metaItems = [
        ['label' => 'Recibimos de', 'value' => $payment->client->name, 'strong' => true, 'span' => 2,
            'sub' => $payment->client->tax_id ? ($payment->client->tax_identifier_type?->value ?? 'RNC/Cédula').': '.$payment->client->tax_id : null],
        ['label' => 'Cuenta por cobrar', 'value' => $receivable?->number, 'strong' => true,
            'sub' => $receivable?->document_number ? 'Venta '.$receivable->document_number : null],
        ['label' => 'Recibido por', 'value' => $payment->creator->name ?? null],
    ];
@endphp

<x-pdf.document
    title="Recibo de cobro"
    :number="$payment->receipt_number"
    :date="$payment->payment_date->format('d/m/Y')"
    :status="$isCanceled ? 'Anulado' : null"
    statusVariant="bad"
    :logoSrc="$logoSrc ?? null"
    footer="Recibo de cobro">

    <x-pdf.meta :items="$metaItems" />

    @if($isCanceled)
        <x-pdf.note label="Recibo anulado" variant="danger">
            Este cobro fue anulado y su monto se devolvió al saldo de la cuenta por cobrar.
        </x-pdf.note>
    @endif

    <x-pdf.section title="Detalle del cobro">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%">Forma de pago</th>
                    <th style="width: 45%">Referencia</th>
                    <th class="num" style="width: 25%">Monto recibido</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="bold">{{ $payment->tipoPago?->nombre }}</td>
                    <td>{{ $payment->reference ?: '—' }}</td>
                    <td class="num bold">{{ $money($payment->amount) }}</td>
                </tr>
            </tbody>
        </table>
    </x-pdf.section>

    <x-pdf.totals :rows="[
        ['label' => 'Total recibido', 'value' => $money($payment->amount), 'variant' => 'grand'],
        ['label' => 'Saldo pendiente de la cuenta', 'value' => $receivable ? $money($receivable->current_balance) : null, 'variant' => 'sep'],
        ['label' => 'Saldo total del cliente', 'value' => $money($payment->client->balance)],
    ]">
        @if($payment->note)
            <span class="label">Nota</span>
            <span class="value">{{ $payment->note }}</span>
        @endif
    </x-pdf.totals>

    <table class="signature">
        <tr>
            <td><div class="signature-line">{{ $payment->creator->name ?? 'Cajero' }}</div><span class="small muted">Firma autorizada</span></td>
            <td><div class="signature-line">{{ $payment->client->name }}</div><span class="small muted">Firma del cliente</span></td>
        </tr>
    </table>
</x-pdf.document>
