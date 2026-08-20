@php
    $config = general_config();
    $currency = config('regional.currency_symbol');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; size: auto; }
        * {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.25;
            color: #000000 !important;
            margin: 0; padding: 0;
            box-sizing: border-box;
        }
        body { background: #fff; -webkit-print-color-adjust: exact; }
        .ticket { width: 72mm; margin: 0 auto; padding: 10px 2px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .spacer { margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        table.fixed-cols { table-layout: fixed; }
        td { word-wrap: break-word; overflow-wrap: break-word; }
        .table-header { border-bottom: 1px solid #000; }
        .total-row { font-size: 13px; font-weight: bold; }
        .dashed { border-top: 1px dashed #000; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            @if($config->logo)
                <img src="{{ asset('storage/'.$config->logo) }}" style="max-height: 45px; margin-bottom: 4px;"><br>
            @endif
            <span class="bold" style="font-size: 14px; display:block;">{{ $config->nombre_empresa }}</span>
            <span style="font-size: 10px;">{{ $config->direccion }}</span>
        </div>

        <div class="spacer center bold" style="font-size: 13px;">
            REPORTE DE TURNO #{{ $session->id }}
        </div>

        <div class="spacer" style="border-top: 1px solid #000; padding-top: 4px;">
            TERMINAL: {{ $session->terminal->name ?? 'N/A' }}<br>
            ABRIO: {{ $session->openedBy->name ?? $session->user->name ?? 'N/A' }}<br>
            CERRO: {{ $session->closedBy->name ?? '---' }}<br>
            PERIODO: {{ $session->opened_at->format('d/m H:i') }} - {{ $session->closed_at?->format('d/m H:i') ?? 'EN CURSO' }}
        </div>

        <div class="spacer dashed">
            <span class="bold">DETALLE DE VENTAS</span>
            <table class="fixed-cols">
                <colgroup>
                    <col style="width: 24%">
                    <col style="width: 46%">
                    <col style="width: 30%">
                </colgroup>
                <thead>
                    <tr class="table-header">
                        <th align="left">HORA</th>
                        <th align="left">CAJERO</th>
                        <th class="right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesDetail as $row)
                        <tr>
                            <td valign="top">{{ $row['hora'] }}</td>
                            <td valign="top" style="word-wrap: break-word;">{{ $row['cajero'] }}</td>
                            <td class="right" valign="top">{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">SIN VENTAS EN ESTE TURNO</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="spacer dashed">
            <span class="bold">RESUMEN POR FORMA DE PAGO</span>
            <table>
                @foreach($breakdownRows as $row)
                    @foreach($columns as $col)
                        <tr>
                            <td>{{ strtoupper($col) }} ({{ $row['concepto'] }})</td>
                            <td class="right">{{ $currency }}{{ number_format($row['methods'][$col] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total-row" style="border-top: 1px solid #000;">
                    <td>TOTAL CAJA</td>
                    <td class="right">{{ $currency }}{{ number_format($grandTotal, 2) }}</td>
                </tr>
            </table>
            @if($creditTotal > 0)
                <div style="border: 1.5px solid #000; padding: 5px; margin-top: 6px;">
                    <span class="bold" style="font-size: 11px;">CXC PENDIENTE POR COBRAR</span><br>
                    <span class="bold" style="font-size: 15px;">{{ $currency }}{{ number_format($creditTotal, 2) }}</span><br>
                    <span style="font-size: 9px;">A CREDITO — NO ES CAJA HOY, PERO SI DINERO PENDIENTE.</span><br>
                    <span style="font-size: 9px;">VENTAS TOTALES DEL TURNO: {{ $currency }}{{ number_format($totalSalesWithCredit, 2) }}</span>
                </div>
            @endif
        </div>

        @if($session->difference_reason)
            <div class="spacer dashed">
                <span class="bold">MOTIVO DEL DESCUADRE</span><br>
                <span style="font-size: 10px;">{{ strtoupper(\App\Models\Sales\Pos\PosSession::getReasons()[$session->difference_reason] ?? $session->difference_reason) }}</span>
                @if($session->difference_notes)
                    <br><span style="font-size: 10px;">{{ $session->difference_notes }}</span>
                @endif
            </div>
        @endif

        @if($session->notes)
            <div class="spacer dashed">
                <span class="bold">NOTAS DEL TURNO</span><br>
                <span style="font-size: 10px;">{{ $session->notes }}</span>
            </div>
        @endif

        <div class="spacer dashed">
            <span class="bold">ARQUEO DE CAJA</span>
            <table>
                <tr><td>FONDO INICIAL</td><td class="right">{{ number_format($session->opening_balance, 2) }}</td></tr>
                <tr><td>VENTAS EFECTIVO</td><td class="right">{{ number_format($session->cash_sales, 2) }}</td></tr>
                @if($session->cash_collections > 0)
                    <tr><td>COBROS CXC EFECTIVO</td><td class="right">{{ number_format($session->cash_collections, 2) }}</td></tr>
                @endif
                <tr class="total-row"><td>ESPERADO</td><td class="right">{{ number_format($session->isClosed() ? $session->expected_balance : $session->calculateExpected(), 2) }}</td></tr>
                @if($session->isClosed())
                    <tr><td>CONTADO</td><td class="right">{{ number_format($session->closing_balance, 2) }}</td></tr>
                    <tr class="total-row">
                        <td>{{ $session->difference == 0 ? 'CUADRADA' : ($session->difference > 0 ? 'SOBRANTE' : 'FALTANTE') }}</td>
                        <td class="right">{{ number_format(abs($session->difference), 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="spacer center" style="font-size: 10px; padding-bottom: 8mm;">
            *** FIN DEL REPORTE ***
        </div>
    </div>
</body>
</html>
