{{-- Reporte de turno, PDF carta — diseño base x-pdf.* (v1.4.0 REQ-3.20 f, docs/ui/pdf-documents.md).
     Recibe lo de PosSessionReportService::getReportData() + $logoSrc. --}}
@use('App\Models\Sales\Pos\PosSession')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    $isClosed = $session->isClosed();
    $expected = $isClosed ? $session->expected_balance : $session->calculateExpected();

    // Ancho del resumen por forma de pago: concepto y total fijos, el resto se
    // reparte entre los métodos que tuvo el turno.
    $conceptWidth = 30;
    $totalWidth = 18;
    $methodWidth = count($columns) > 0 ? (100 - $conceptWidth - $totalWidth) / count($columns) : 0;

    $diffLabel = $session->difference == 0 ? 'Cuadrada' : ($session->difference > 0 ? 'Sobrante' : 'Faltante');
    $diffVariant = $session->difference == 0 ? 'ok' : 'bad';
@endphp

<x-pdf.document
    title="Reporte de turno"
    :number="$session->number"
    :date="$session->opened_at->format('d/m/Y h:i A').' — '.($session->closed_at?->format('d/m/Y h:i A') ?? 'en curso')"
    dateLabel="Periodo"
    :status="PosSession::getStatuses()[$session->status] ?? $session->status"
    :statusVariant="$isClosed ? 'muted' : 'info'"
    :logoSrc="$logoSrc ?? null"
    :footer="'Turno '.$session->number">

    <x-pdf.meta :items="[
        ['label' => 'Terminal', 'value' => $session->terminal->name ?? null, 'strong' => true],
        ['label' => 'Abierto por', 'value' => $session->openedBy->name ?? $session->user->name ?? null],
        ['label' => 'Cerrado por', 'value' => $session->closedBy->name ?? null],
        ['label' => 'Fondo inicial', 'value' => $money($session->opening_balance)],
    ]" />

    <x-pdf.section title="Ventas del turno">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 9%">Hora</th>
                    <th style="width: 14%">Venta</th>
                    <th style="width: 25%">Cliente</th>
                    <th style="width: 16%">Cajero</th>
                    <th class="num" style="width: 7%">Cant.</th>
                    <th style="width: 13%">Pago</th>
                    <th class="num" style="width: 16%">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesDetail as $row)
                    <tr>
                        <td>{{ $row['hora'] }}</td>
                        <td class="bold">{{ $row['numero'] }}</td>
                        <td>{{ $row['cliente'] }}</td>
                        <td>{{ $row['cajero'] }}</td>
                        <td class="num">{{ $qty($row['cantidad']) }}</td>
                        <td>{{ $row['metodo'] }}</td>
                        <td class="num">{{ $money($row['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center muted">No se registraron ventas en este turno.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-pdf.section>

    <x-pdf.section title="Resumen de caja por forma de pago">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: {{ $conceptWidth }}%">Concepto</th>
                    @foreach($columns as $col)
                        <th class="num" style="width: {{ $methodWidth }}%">{{ $col }}</th>
                    @endforeach
                    <th class="num" style="width: {{ $totalWidth }}%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdownRows as $row)
                    <tr>
                        <td>{{ $row['concepto'] }}</td>
                        @foreach($columns as $col)
                            <td class="num">{{ $money($row['methods'][$col] ?? 0) }}</td>
                        @endforeach
                        <td class="num">{{ $money($row['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total cobrado en caja</td>
                    @foreach($columns as $col)
                        <td class="num">{{ $money($columnTotals[$col] ?? 0) }}</td>
                    @endforeach
                    <td class="num">{{ $money($grandTotal) }}</td>
                </tr>
            </tfoot>
        </table>
    </x-pdf.section>

    @if($creditTotal > 0)
        <x-pdf.note label="Ventas a crédito del turno" variant="alert">
            De {{ $money($totalSalesWithCredit) }} vendidos en el turno, <strong>{{ $money($creditTotal) }}</strong> fueron a crédito (CxC).
            No es dinero en caja y no entra en el arqueo, pero queda pendiente de cobro.
        </x-pdf.note>
    @endif

    <x-pdf.section title="Arqueo de caja">
        <x-pdf.totals :width="50" :rows="[
            ['label' => 'Fondo inicial', 'value' => $money($session->opening_balance)],
            ['label' => 'Ventas en efectivo', 'value' => $money($session->cash_sales)],
            ['label' => 'Cobros de CxC en efectivo', 'value' => $session->cash_collections > 0 ? $money($session->cash_collections) : null],
            ['label' => 'Esperado en caja', 'value' => $money($expected), 'variant' => 'grand'],
            ['label' => 'Contado al cierre', 'value' => $isClosed ? $money($session->closing_balance) : null, 'variant' => 'sep'],
        ]">
            @if($isClosed)
                <span class="label">Resultado del arqueo</span>
                <span class="badge badge-{{ $diffVariant }}" style="font-size: 10px; padding: 4px 12px;">{{ $diffLabel }} {{ $money(abs($session->difference)) }}</span>
            @else
                <span class="muted">El turno sigue abierto: el arqueo final se hace al cerrarlo.</span>
            @endif
        </x-pdf.totals>
    </x-pdf.section>

    @if($session->difference_reason)
        <x-pdf.note label="Motivo del descuadre" variant="danger">
            <strong>{{ PosSession::getReasons()[$session->difference_reason] ?? $session->difference_reason }}</strong>
            @if($session->difference_notes)
                <br>{{ $session->difference_notes }}
            @endif
        </x-pdf.note>
    @endif

    @if($session->notes)
        <x-pdf.note label="Notas del turno">{{ $session->notes }}</x-pdf.note>
    @endif
</x-pdf.document>
