@php
    $config = general_config();
    $impuestoConfig = $config->impuesto;
    $sale = $invoice->sale;
    $client = $sale->client;
    $currency = $config->currency_symbol ?? '$';

    $taxIdentifier = \DB::table('tax_identifier_types')
                        ->where('id', $config->tax_identifier_type_id)
                        ->first();
    $taxLabel = $taxIdentifier->code ?? 'RNC';
    $taxName = $impuestoConfig->nombre ?? 'ITBIS';
    
    $vencimientoPago = $sale->payment_type === 'credit' 
        ? $sale->created_at->addDays($client->credit_limit_days ?? 30)->format('d/m/Y') 
        : null;

    $ncfLog = $sale->ncfLog;
    $vencimientoNcf = $ncfLog?->sequence?->expiry_date 
        ? $ncfLog->sequence->expiry_date->format('d/m/Y') 
        : null;
    
    $isCancelled = $sale->status === 'canceled';

    // Lógica para Multipay
    $payments = $sale->payments;
    $isMultiPay = $payments->count() > 1;

    // NUEVO: Determinar si mostramos info fiscal
    $mostrarFiscal = $config->usa_ncf && $sale->ncf;

    // Usar directamente el valor de la base de datos, si es nulo o cero, mostrará 0.00
    $taxCalculado = $sale->tax_amount ?? 0.00; 
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
        body { background: #fff; -webkit-print-color-adjust: exact; }
        .ticket { width: 72mm; margin: 0 auto; padding: 10px 2px; }
        .center { text-align: center; }
        .right { text-align: right; }
        
        .spacer { margin-top: 12px; }
        .small-spacer { margin-top: 6px; }
        .info-section { margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; }
        
        .footer-message {
            margin-top: 25px;
            padding-bottom: 10mm;
            font-size: 11px;
            text-transform: none;
        }

        .ncf-row {
            white-space: nowrap;
            display: block;
            width: 100%;
            margin-bottom: 4px;
            font-size: 11px;
        }

        .table-header { border-bottom: 1.5px solid #000; }
        .total-row { font-size: 14px; }

        .cancelled-banner {
            border: 2px solid #000;
            padding: 5px;
            margin: 10px 0;
            text-align: center;
        }
        .cancelled-text {
            font-size: 18px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- 1. ENCABEZADO EMPRESA --}}
        <div class="center">
            <span style="font-size: 16px; display: block; margin-bottom: 2px;">{{ $config->nombre_empresa }}</span>
            <div class="header-info" style="font-size: 11px;">
                {{ $config->direccion }}<br>
                TEL: {{ $config->telefono }}<br>
                {{ $taxLabel }}: {{ $config->tax_id }}<br>
                {{-- Solo mostrar si NCF está activo --}}
                @if($mostrarFiscal)
                    <span style="font-size: 9px;">COMPROBANTE AUTORIZADO POR LA DGII</span>
                @endif
            </div>
        </div>

        @if($isCancelled)
            <div class="cancelled-banner">
                <span class="cancelled-text">*** CANCELADA ***</span>
                <div class="cancellation-reason" style="font-size: 10px; margin-top: 3px; text-transform: none;">
                    MOTIVO: {{ $ncfLog->cancellation_reason ?? 'SIN MOTIVO REGISTRADO' }}
                </div>
            </div>
        @endif

        {{-- 2. DATOS DE LA FACTURA Y NCF --}}
        <div class="info-section spacer">
            <table>
                <tr>
                    <td>FACTURA: {{ $invoice->invoice_number }}</td>
                    <td class="right">{{ $sale->payment_type === 'cash' ? 'CONTADO' : 'CREDITO' }}</td>
                </tr>
            </table>

            {{-- Bloque NCF: Solo si existe y está habilitado --}}
            @if($mostrarFiscal)
                <div style="margin-top: 4px;">
                    <span style="font-size: 11px; display:block;">{{ $ncfLog->type->name ?? 'COMPROBANTE' }}</span>
                    <div class="ncf-row">
                        {{ $ncfLog?->type?->is_electronic ? 'E-NCF:' : 'NCF:' }} {{ $sale->ncf }} 
                        @if($vencimientoNcf)
                            <span style="font-size: 10px;"> VENCE:{{ $vencimientoNcf }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- 3. DATOS DEL CLIENTE Y TPV --}}
        <div class="info-section small-spacer">
            <div style="border-top: 0.5px solid #000; margin-bottom: 4px;"></div>
            CLIENTE: {{ substr($client->name, 0, 30) }}<br>
            @if($client->tax_id)
                {{ $taxLabel }}: {{ $client->tax_id }}<br>
            @endif
            
            <div style="margin-top: 4px;">
                VENDEDOR: {{ $sale->user->name ?? 'SISTEMA' }}<br>
                @if($sale->pos_terminal_id)
                    TPV: {{ $sale->posTerminal->name }}<br>
                @endif
                {{ $isMultiPay ? 'METODOS DE PAGO:' : 'METODO PAGO:' }} {{ $isMultiPay ? 'MIXTO' : ($sale->tipoPago->nombre ?? 'EFECTIVO') }}<br>
                FECHA: {{ $sale->created_at->format('d/m/Y G:i A') }}
                @if($vencimientoPago)
                    <br>VENCE PAGO: {{ $vencimientoPago }}
                @endif
            </div>
        </div>

        {{-- 4. DETALLE DE PRODUCTOS --}}
        <div class="spacer">
            <table>
                <thead>
                    <tr class="table-header">
                        <th align="left" style="width: 15%; padding-bottom: 2px;">CANT</th>
                        <th align="left" style="width: 55%; padding-bottom: 2px;">DESC.</th>
                        <th class="right" style="width: 30%; padding-bottom: 2px;">SUBT.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td valign="top" style="padding-top: 5px;">
                                {{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format($item->quantity, 2) }}
                            </td>
                            <td valign="top" style="padding-top: 5px;">
                                {{ substr($item->product->name, 0, 22) }}<br>
                                <span style="font-size: 10px;">@ {{ number_format($item->unit_price, 2) }}</span>
                            </td>
                            <td class="right" valign="top" style="padding-top: 5px;">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 5. TOTALES --}}
        <div class="spacer">
            <table style="border-top: 1px solid #000; padding-top: 4px;">
                <tr>
                    <td>SUBTOTAL BRUTO:</td>
                    <td class="right">{{ $currency }}{{ number_format($sale->total_amount, 2) }}</td>
                </tr>
                @if($sale->discount_total > 0)
                <tr>
                    <td>DESCUENTO:</td>
                    <td class="right">-{{ $currency }}{{ number_format($sale->discount_total, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>SUBTOTAL NETO:</td>
                    <td class="right">{{ $currency }}{{ number_format($sale->total_amount - $sale->discount_total, 2) }}</td>
                </tr>
                @if($taxCalculado > 0)
                    <tr>
                        <td>{{ $taxName ?? 'ITBIS' }}:</td>
                        <td class="text-right right">{{ $currency }}{{ number_format($taxCalculado, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td style="padding-top: 4px;">TOTAL</td>
                    <td class="right" style="padding-top: 4px;">{{ $currency }}{{ number_format($sale->total_amount - $sale->discount_total, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- 6. DESGLOSE DE PAGOS (Multipay Ready) --}}
        <div class="spacer" style="border-top: 0.5px dashed #000; padding-top: 6px;">
            @if($isMultiPay)
                @foreach($payments as $payment)
                    <table>
                        <tr>
                            <td style="font-size: 11px;">{{ $payment->tipoPago->nombre }}:</td>
                            <td class="right" style="font-size: 11px;">{{ $currency }}{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    </table>
                @endforeach
            @endif

            @if($sale->payment_type === 'cash' && $sale->cash_received > 0)
                <table style="{{ $isMultiPay ? 'margin-top: 4px; border-top: 0.5px solid #000; padding-top: 2px;' : '' }}">
                    <tr>
                        <td>RECIBIDO:</td>
                        <td class="right">{{ $currency }}{{ number_format($sale->cash_received ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>CAMBIO:</td>
                        <td class="right">{{ $currency }}{{ number_format($sale->cash_change ?? 0, 2) }}</td>
                    </tr>
                </table>
            @elseif($sale->payment_type === 'credit')
                <div class="center" style="padding-top: 15px;">
                    <p style="font-size: 10px;">ACEPTO LOS TÉRMINOS DE PAGO.</p>
                    <div style="border-top: 1.5px solid #000; width: 85%; margin: 35px auto 5px auto;"></div>
                    <span style="font-size: 10px;">FIRMA DEL CLIENTE</span>
                </div>
            @endif
        </div>

        {{-- 7. PIE DE PAGINA --}}
        <div class="center footer-message">
            *** GRACIAS POR PREFERIRNOS ***<br>
            {{ $config->nombre_empresa }}
        </div>
    </div>
</body>
</html>