@php
    $config = general_config();
    $payment_type = $payment->tipoPago;
    $receivable = $payment->receivable;
    // Accedemos a la venta a través de la relación polimórfica 'reference'
    $sale = $receivable->reference; 
    $client = $payment->client;
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
            font-size: 12px; 
            line-height: 1.2; 
            color: #000000 !important;
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ticket { width: 72mm; margin: 0 auto; padding: 10px 2px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .spacer { margin-top: 12px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        .table-header { border-bottom: 1.5px solid #000; }
        .total-row { font-size: 14px; }
        .bg-light { background: #f3f3f3; padding: 4px; }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- 1. ENCABEZADO --}}
        <div class="center">
            <span style="font-size: 16px; display: block; margin-bottom: 2px;">{{ $config->nombre_empresa }}</span>
            <div style="font-size: 11px;">
                {{ $config->direccion }}<br>
                TEL: {{ $config->telefono }}<br>
                {{ $config->tax_id }}
            </div>
        </div>

        <div class="spacer center" style="border: 1.5px solid #000; padding: 4px;">
            RECIBO DE PAGO: {{ $payment->receipt_number }}
        </div>

        {{-- 2. DATOS DEL CLIENTE Y PAGO --}}
        <div class="spacer">
            CLIENTE: {{ substr($client->name, 0, 30) }}<br>
            FECHA PAGO: {{ $payment->payment_date->format('d/m/Y') }}<br>
            CAJERO: {{ $payment->creator->name ?? 'SISTEMA' }}<br>
            METODO: {{ $payment_type->nombre ?? 'N/A' }}
            @if($payment->reference) <br>REF: {{ $payment->reference }} @endif
        </div>

        <div class="divider"></div>
        <div class="center">DETALLE DE VENTA ORIGEN: {{ $receivable->document_number }}</div>
        <div class="divider"></div>

        {{-- 3. DESGLOSE DE PRODUCTOS DE LA VENTA --}}
        @if($sale && $sale->items)
        <table>
            <thead>
                <tr class="table-header">
                    <th align="left" style="width: 70%;">PRODUCTO</th>
                    <th class="right" style="width: 30%;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td style="padding-top: 5px;">
                            {{ (int)$item->quantity }} x {{ substr($item->product->name, 0, 18) }}
                        </td>
                        <td class="right" valign="top" style="padding-top: 5px;">
                            {{ number_format($item->subtotal + $item->tax, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="divider"></div>

        {{-- 4. RESUMEN DE SALDOS --}}
        <table>
            <tr>
                <td>TOTAL FACTURA:</td>
                <td class="right">{{ $currency }}{{ number_format($receivable->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td>SALDO ANTERIOR:</td>
                <td class="right">{{ $currency }}{{ number_format($receivable->current_balance + $payment->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td style="padding: 4px 0;">MONTO ABONADO:</td>
                <td class="right" style="padding: 4px 0;">{{ $currency }}{{ number_format($payment->amount, 2) }}</td>
            </tr>
            <tr class="bg-light">
                <td>PENDIENTE FACTURA:</td>
                <td class="right">{{ $currency }}{{ number_format($receivable->current_balance, 2) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- 5. BALANCE TOTAL --}}
        <div class="center" style="padding: 5px; border: 1px dashed #000;">
            BALANCE TOTAL CLIENTE:<br>
            <span style="font-size: 16px;">{{ $currency }}{{ number_format($client->balance, 2) }}</span>
        </div>

        {{-- 6. PIE --}}
        <div class="center spacer" style="margin-top: 30px;">
            <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto;"></div>
            FIRMA DEL CLIENTE
        </div>

        <div class="center spacer" style="font-size: 10px; text-transform: none; margin-bottom: 20px;">
            *** GRACIAS POR SU ABONO ***
        </div>
    </div>
</body>
</html>