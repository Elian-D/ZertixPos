@php
    $config = general_config();
    $currency = config('regional.currency_symbol');

    // Anchos fijos para la tabla de resumen por forma de pago — DomPDF con
    // table-layout:fixed necesita los anchos declarados en <colgroup> (o en la
    // primera fila) para que thead/tbody/tfoot queden alineados; sin esto,
    // calcula el ancho de cada fila por separado según su propio contenido y
    // las columnas terminan desalineadas entre encabezado y datos.
    $conceptWidth = 28;
    $totalWidth = 16;
    $methodWidth = count($columns) > 0 ? (100 - $conceptWidth - $totalWidth) / count($columns) : 0;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; margin: 0; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; color: #1a1a1a; }
        .info-label { color: #666; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }

        .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 15px; margin-bottom: 18px; border-radius: 8px; }
        .meta-box table { width: 100%; }
        .meta-box td { padding: 2px 0; font-size: 11px; }

        h3.section-title { font-size: 13px; text-transform: uppercase; color: #1e293b; border-bottom: 2px solid #1e293b; padding-bottom: 4px; margin: 22px 0 8px 0; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
        table.data-table th, table.data-table td { word-wrap: break-word; overflow-wrap: break-word; vertical-align: middle; }
        table.data-table th { background: #1e293b; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 6px 8px; text-align: left; line-height: 1.3; }
        table.data-table td { padding: 5px 8px; font-size: 10.5px; border-bottom: 1px solid #eee; }
        table.data-table tbody tr:nth-child(even) { background: #f9fafb; }
        table.data-table tfoot td { font-weight: bold; border-top: 2px solid #1e293b; background: #f1f5f9; }

        .notes-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; margin: 10px 0 18px 0; font-size: 10.5px; color: #92400e; }
        .notes-box .notes-label { font-size: 8.5px; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 2px; }

        .arqueo-table td { padding: 4px 0; font-size: 11px; }
        .arqueo-table .total-row td { font-weight: bold; font-size: 13px; border-top: 1.5px solid #1e293b; padding-top: 6px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-ok { background: #d1fae5; color: #047857; }
        .badge-bad { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($config->logo)
                    <img src="{{ storage_path('app/public/'.$config->logo) }}" style="max-height: 55px; margin-bottom: 6px;"><br>
                @endif
                <span class="company-name">{{ $config->nombre_empresa }}</span><br>
                <span style="font-size: 10px; color: #666;">{{ $config->direccion }}</span>
            </td>
            <td class="text-right" style="width: 40%; vertical-align: top;">
                <span style="font-size: 16px; font-weight: bold;">REPORTE DE TURNO #{{ $session->id }}</span><br>
                <span style="font-size: 10px; color: #666;">Generado: {{ now()->format('d/m/Y h:i A') }}</span>
            </td>
        </tr>
    </table>

    <div class="meta-box">
        <table>
            <tr>
                <td style="width: 25%;"><span class="info-label">Terminal</span><br>{{ $session->terminal->name ?? 'N/A' }}</td>
                <td style="width: 25%;"><span class="info-label">Abierto Por</span><br>{{ $session->openedBy->name ?? $session->user->name ?? 'N/A' }}</td>
                <td style="width: 25%;"><span class="info-label">Cerrado Por</span><br>{{ $session->closedBy->name ?? '—' }}</td>
                <td style="width: 25%;"><span class="info-label">Estado</span><br>{{ \App\Models\Sales\Pos\PosSession::getStatuses()[$session->status] ?? $session->status }}</td>
            </tr>
            <tr>
                <td colspan="2"><span class="info-label">Periodo</span><br>
                    {{ $session->opened_at->format('d/m/Y h:i A') }} — {{ $session->closed_at?->format('d/m/Y h:i A') ?? 'En curso' }}
                </td>
                <td colspan="2"><span class="info-label">Fondo Inicial</span><br>{{ $currency }}{{ number_format($session->opening_balance, 2) }}</td>
            </tr>
        </table>
    </div>

    <h3 class="section-title">Detalle de Ventas</h3>
    <table class="data-table">
        <colgroup>
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 22%">
            <col style="width: 16%">
            <col style="width: 8%">
            <col style="width: 18%">
            <col style="width: 16%">
        </colgroup>
        <thead>
            <tr>
                <th>Hora</th>
                <th>#</th>
                <th>Cliente</th>
                <th>Cajero</th>
                <th class="text-right">Cant.</th>
                <th>Método de Pago</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesDetail as $row)
                <tr>
                    <td>{{ $row['hora'] }}</td>
                    <td>{{ $row['numero'] }}</td>
                    <td>{{ $row['cliente'] }}</td>
                    <td>{{ $row['cajero'] }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($row['cantidad'], 2), '0'), '.') }}</td>
                    <td>{{ $row['metodo'] }}</td>
                    <td class="text-right">{{ $currency }}{{ number_format($row['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center" style="color: #999; padding: 12px;">No se registraron ventas en este turno.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 class="section-title">Resumen de Caja por Forma de Pago</h3>
    <table class="data-table">
        <colgroup>
            <col style="width: {{ $conceptWidth }}%">
            @foreach($columns as $col)
                <col style="width: {{ $methodWidth }}%">
            @endforeach
            <col style="width: {{ $totalWidth }}%">
        </colgroup>
        <thead>
            <tr>
                <th>Concepto</th>
                @foreach($columns as $col)
                    <th class="text-right">{{ $col }}</th>
                @endforeach
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($breakdownRows as $row)
                <tr>
                    <td>{{ $row['concepto'] }}</td>
                    @foreach($columns as $col)
                        <td class="text-right">{{ $currency }}{{ number_format($row['methods'][$col] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="text-right">{{ $currency }}{{ number_format($row['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL COBRADO EN CAJA</td>
                @foreach($columns as $col)
                    <td class="text-right">{{ $currency }}{{ number_format($columnTotals[$col] ?? 0, 2) }}</td>
                @endforeach
                <td class="text-right">{{ $currency }}{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($creditTotal > 0)
        <div style="background: #fff7ed; border: 1.5px solid #fb923c; border-radius: 8px; padding: 10px 14px; margin: 0 0 18px 0;">
            <span style="font-size: 8.5px; text-transform: uppercase; font-weight: bold; color: #c2410c; display: block; margin-bottom: 3px;">Cuentas por Cobrar pendientes de este turno</span>
            <span style="font-size: 12px; color: #7c2d12;">
                Ventas totales del turno: <strong>{{ $currency }}{{ number_format($totalSalesWithCredit, 2) }}</strong>
                — de las cuales <strong style="font-size: 14px;">{{ $currency }}{{ number_format($creditTotal, 2) }}</strong> fueron a
                <strong>Crédito (CxC)</strong>: no es dinero en caja hoy, pero es dinero que va a entrar — no forma parte del arqueo de arriba, pero sí queda pendiente de cobro.
            </span>
        </div>
    @endif

    @if($session->difference_reason)
        <div class="notes-box">
            <span class="notes-label">Motivo del Descuadre</span>
            <strong>{{ \App\Models\Sales\Pos\PosSession::getReasons()[$session->difference_reason] ?? $session->difference_reason }}</strong>
            @if($session->difference_notes)
                <br>{{ $session->difference_notes }}
            @endif
        </div>
    @endif

    @if($session->notes)
        <div class="notes-box">
            <span class="notes-label">Notas del Turno</span>
            {{ $session->notes }}
        </div>
    @endif

    <h3 class="section-title">Arqueo de Caja</h3>
    <table class="arqueo-table" style="width: 60%;">
        <tr><td>Fondo Inicial</td><td class="text-right">{{ $currency }}{{ number_format($session->opening_balance, 2) }}</td></tr>
        <tr><td>Ventas en Efectivo</td><td class="text-right">{{ $currency }}{{ number_format($session->cash_sales, 2) }}</td></tr>
        @if($session->cash_collections > 0)
            <tr><td>Cobros CxC en Efectivo</td><td class="text-right">{{ $currency }}{{ number_format($session->cash_collections, 2) }}</td></tr>
        @endif
        <tr class="total-row"><td>Esperado en Caja</td><td class="text-right">{{ $currency }}{{ number_format($session->isClosed() ? $session->expected_balance : $session->calculateExpected(), 2) }}</td></tr>
        @if($session->isClosed())
            <tr><td>Monto Real Contado</td><td class="text-right">{{ $currency }}{{ number_format($session->closing_balance, 2) }}</td></tr>
            <tr>
                <td>Diferencia</td>
                <td class="text-right">
                    <span class="badge {{ $session->difference == 0 ? 'badge-ok' : 'badge-bad' }}">
                        {{ $session->difference == 0 ? 'Cuadrada' : ($session->difference > 0 ? 'Sobrante' : 'Faltante') }}
                        {{ $currency }}{{ number_format(abs($session->difference), 2) }}
                    </span>
                </td>
            </tr>
        @endif
    </table>
</body>
</html>
