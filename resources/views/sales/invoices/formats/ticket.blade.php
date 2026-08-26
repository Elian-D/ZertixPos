@php
    $config = general_config();
    $sale = $invoice->sale;
    $client = $sale->client;
    $currency = config('regional.currency_symbol');

    $taxLabel = $config->tax_identifier_type?->value ?? 'RNC';

    // Se lee de la Receivable, nunca se recalcula acá (REQ-11.10) — es la única
    // fuente de verdad del vencimiento, la misma que controla esMoroso().
    $vencimientoPago = $sale->payment_type === 'credit'
        ? $sale->receivable?->due_date?->format('d/m/Y')
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
    $mostrarFiscal = module_enabled('sales.ncf') && $sale->ncf;

    // Desglose real por tipo de impuesto (Fase 5, REQ-5.6) — agrupa el snapshot
    // congelado de cada línea, ya no una sola tasa global aplicada a todo el carrito.
    $taxBreakdown = $sale->items->pluck('tax_breakdown')->filter()->flatten(1)->groupBy('key');

    $paperWidth = $paperWidth ?? '80mm';
    $isNarrow = $paperWidth === '58mm';

    // El ancho nominal del rollo (58mm/80mm) NO es el ancho imprimible real: todo cabezal
    // térmico tiene una zona muerta a cada lado (en la Epson TM-m30, ~3mm por lado) donde
    // no imprime nada. Usar el ancho nominal tal cual corta contenido en papel físico,
    // aunque en el preview/PDF se vea completo. Se descuenta un margen de seguridad de
    // 4mm por lado (8mm en total) — mismo colchón que ya traía el ticket original
    // hardcodeado en 72mm para 80mm, y que coincide con el ancho imprimible estándar
    // publicado para rollos de 58mm (50mm).
    $printSafetyMarginMm = 4;
    $printableWidthMm = max(1, ((int) str_replace('mm', '', $paperWidth)) - ($printSafetyMarginMm * 2));

    // 11.2.6: desglosar el descuento total en "por ítem" vs. "global" para el ticket.
    // `discount_percentage` de cada línea es la señal confiable de descuento PROPIO del
    // ítem (nunca tocado por el reparto del descuento global bajo la Regla de Exclusión,
    // ver pos-workspace.blade.php::recalculateTotals() y SaleService::validateDiscounts()
    // — misma lógica de reconstrucción en los tres lugares). Lo que sobra de
    // sale->discount_total tras restar esa parte es lo que aportó el descuento global.
    $itemDiscountTotal = 0;
    $eligibleGrossForGlobal = 0;

    foreach ($sale->items as $saleItem) {
        $lineGross = $saleItem->quantity * $saleItem->unit_price;
        $itemPct = $saleItem->discount_percentage ?? 0;

        if ($itemPct > 0) {
            $itemDiscountTotal += ($lineGross * $itemPct) / 100;
        } else {
            $eligibleGrossForGlobal += $lineGross;
        }
    }

    $globalDiscountTotal = max(0, ($sale->discount_total ?? 0) - $itemDiscountTotal);
    $globalDiscountPct = $eligibleGrossForGlobal > 0 ? ($globalDiscountTotal / $eligibleGrossForGlobal) * 100 : 0;
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; size: auto; }
        * {
            font-family: 'Courier New', Courier, monospace;
            font-size: {{ $isNarrow ? '12px' : '14px' }};
            line-height: 1.2;
            color: #000000 !important;
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-weight: normal;
            text-transform: uppercase;
        }
        body { background: #fff; -webkit-print-color-adjust: exact; }
        .ticket { width: {{ $printableWidthMm }}mm; margin: 0 auto; padding: 10px {{ $isNarrow ? '1px' : '2px' }}; }
        .center { text-align: center; }
        .right { text-align: right; }
        
        .spacer { margin-top: 12px; }
        .small-spacer { margin-top: 6px; }
        .info-section { margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; }
        
        .footer-message {
            margin-top: 25px;
            padding-bottom: 10mm;
            font-size: 13px;
            text-transform: none;
        }

        .ncf-row {
            white-space: nowrap;
            display: block;
            width: 100%;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .table-header { border-bottom: 1.5px solid #000; }
        .total-row, .total-row * { font-size: 16px; font-weight: bold; }

        .cancelled-banner {
            border: 2px solid #000;
            padding: 5px;
            margin: 10px 0;
            text-align: center;
        }
        .cancelled-text {
            font-size: 21px;
            display: block;
            font-weight: bold;
        }

        /* Jerarquía visual: solo lo que el cajero/cliente necesita ver de un
           vistazo (total, vuelto, encabezado, N° de factura) va en negrita real.
           No existe un "semi-bold" auténtico en Courier New, así que el resto
           del ticket se queda en normal en vez de un peso intermedio falso. */
        /* El selector universal `*` fija font-weight:normal directamente en cada
           elemento (no por herencia), así que .bold en un contenedor no le
           llega a sus hijos si solo se define en el propio .bold — hace falta
           forzarlo también en los descendientes. */
        .bold, .bold * { font-weight: bold; }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- 1. ENCABEZADO EMPRESA --}}
        <div class="center">
            <span class="bold" style="font-size: 18px; display: block; margin-bottom: 2px;">{{ $config->nombre_empresa }}</span>
            <div class="header-info" style="font-size: 13px;">
                {{ $config->direccion }}<br>
                TEL: {{ $config->telefono }}<br>
                {{ $taxLabel }}: {{ $config->tax_id }}<br>
                {{-- Solo mostrar si NCF está activo --}}
                @if($mostrarFiscal)
                    <span style="font-size: 10px;">COMPROBANTE AUTORIZADO POR LA DGII</span>
                @endif
            </div>
        </div>

        @if($isCancelled)
            <div class="cancelled-banner">
                <span class="cancelled-text">*** CANCELADA ***</span>
                <div class="cancellation-reason" style="font-size: 12px; margin-top: 3px; text-transform: none;">
                    MOTIVO: {{ $ncfLog->cancellation_reason ?? 'SIN MOTIVO REGISTRADO' }}
                </div>
            </div>
        @endif

        {{-- 2. DATOS DE LA FACTURA Y NCF --}}
        <div class="info-section spacer">
            <table>
                <tr class="bold">
                    <td>FACTURA: {{ $invoice->invoice_number }}</td>
                    <td class="right">{{ $sale->payment_type === 'cash' ? 'CONTADO' : 'CREDITO' }}</td>
                </tr>
            </table>

            {{-- Bloque NCF: Solo si existe y está habilitado --}}
            @if($mostrarFiscal)
                <div style="margin-top: 4px;">
                    <span style="font-size: 13px; display:block;">{{ $ncfLog->type->name ?? 'COMPROBANTE' }}</span>
                    <div class="ncf-row">
                        {{ $ncfLog?->type?->is_electronic ? 'E-NCF:' : 'NCF:' }} {{ $sale->ncf }}
                        @if($vencimientoNcf)
                            <span style="font-size: 12px;"> VENCE:{{ $vencimientoNcf }}</span>
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
                @if($isMultiPay || $sale->payment_type !== 'credit')
                    {{ $isMultiPay ? 'METODOS DE PAGO:' : 'METODO PAGO:' }} {{ $isMultiPay ? 'MIXTO' : ($sale->tipoPago->nombre ?? 'EFECTIVO') }}<br>
                @endif
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
                        <th align="left" style="width: 60%; padding-bottom: 2px;">DESCRIPCIÓN</th>
                        <th class="right" style="width: 25%; padding-bottom: 2px;">SUBT.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td valign="top" style="padding-top: 5px;">
                                {{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format($item->quantity, 2) }}
                            </td>
                            <td valign="top" style="padding-top: 5px;">
                                {{ substr($item->product->name, 0, $isNarrow ? 16 : 22) }}<br>
                                <span style="font-size: 12px;">@ {{ number_format($item->unit_price, 2) }}</span>
                                @if(($item->discount_percentage ?? 0) > 0)
                                    <br><span style="font-size: 11px;">(DESC. {{ number_format($item->discount_percentage, 0) }}%: -{{ $currency }}{{ number_format(($item->quantity * $item->unit_price * $item->discount_percentage) / 100, 2) }})</span>
                                @endif
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
                @if($itemDiscountTotal > 0.01)
                <tr>
                    <td>DESC. POR ÍTEMS:</td>
                    <td class="right">-{{ $currency }}{{ number_format($itemDiscountTotal, 2) }}</td>
                </tr>
                @endif
                @if($globalDiscountTotal > 0.01)
                <tr>
                    <td>DESC. GLOBAL ({{ number_format($globalDiscountPct, 0) }}%):</td>
                    <td class="right">-{{ $currency }}{{ number_format($globalDiscountTotal, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>SUBTOTAL NETO:</td>
                    <td class="right">{{ $currency }}{{ number_format($sale->net_amount, 2) }}</td>
                </tr>
            </table>
            {{-- Separación real entre grupos (margen), no una segunda línea divisoria —
                 el borde de arriba de la tabla de TOTALES ya cumple ese rol una vez. --}}
            <table style="margin-top: 6px; padding-top: 4px;">
                @foreach($taxBreakdown as $key => $lines)
                    <tr>
                        <td>{{ $lines->first()['label'] }}:</td>
                        <td class="right">{{ $currency }}{{ number_format($lines->sum('amount'), 2) }}</td>
                    </tr>
                @endforeach
                {{-- Línea de Propina Legal (REQ-5.7) se agrega cuando esa fase se retome —
                     service_charge_amount no existe como columna todavía, ver 5.2. --}}
                <tr class="total-row">
                    <td style="padding-top: 4px;">TOTAL</td>
                    <td class="right" style="padding-top: 4px;">{{ $currency }}{{ number_format($sale->grand_total, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- 6. DESGLOSE DE PAGOS (Multipay Ready) --}}
        <div class="spacer" style="border-top: 0.5px dashed #000; padding-top: 6px;">
            @if($isMultiPay)
                @foreach($payments as $payment)
                    <table>
                        <tr>
                            <td style="font-size: 13px;">{{ $payment->tipoPago?->nombre ?? 'N/A' }}:</td>
                            <td class="right" style="font-size: 13px;">{{ $currency }}{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    </table>
                @endforeach
            @endif

            @if($sale->payment_type === 'cash' && $sale->cash_received > 0)
                <table class="bold" style="{{ $isMultiPay ? 'margin-top: 4px; border-top: 0.5px solid #000; padding-top: 2px;' : '' }}">
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
                    <p style="font-size: 12px;">ACEPTO LOS TÉRMINOS DE PAGO.</p>
                    <div style="border-top: 1.5px solid #000; width: 85%; margin: 35px auto 5px auto;"></div>
                    <span style="font-size: 12px;">FIRMA DEL CLIENTE</span>
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