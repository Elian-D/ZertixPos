@php
    $config = general_config();
    $currency = config('regional.currency_symbol');
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');

    // Ancho dinámico (58mm/80mm) — mismo mecanismo que finance/collections/ticket.blade.php.
    $paperWidth = $paperWidth ?? '80mm';
    $isNarrow = $paperWidth === '58mm';
    $printSafetyMarginMm = 4;
    $printableWidthMm = max(1, ((int) str_replace('mm', '', $paperWidth)) - ($printSafetyMarginMm * 2));
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; size: auto; }
        * {
            font-family: 'Courier New', Courier, monospace;
            font-size: {{ $isNarrow ? '11px' : '12px' }};
            line-height: 1.2;
            color: #000000 !important;
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ticket { width: {{ $printableWidthMm }}mm; margin: 0 auto; padding: 10px {{ $isNarrow ? '1px' : '2px' }}; }
        .center { text-align: center; }
        .spacer { margin-top: 12px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        .total-row { font-size: 14px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <span style="font-size: 16px; display: block; margin-bottom: 2px;">{{ $config->nombre_empresa }}</span>
            <div style="font-size: 11px;">
                {{ $config->direccion }}<br>
                TEL: {{ $config->telefono }}<br>
                {{ $config->tax_id }}
            </div>
        </div>

        <div class="spacer center" style="border: 1.5px solid #000; padding: 4px;">
            DEVOLUCIÓN {{ $return->number }}
            @if($return->isVoided())
                <br>*** ANULADA ***
            @endif
        </div>

        <div class="spacer">
            FECHA: {{ $return->created_at->format('d/m/Y h:i A') }}<br>
            ATENDIDO POR: {{ $return->user->name ?? 'SISTEMA' }}<br>
            VENTA ORIGEN: {{ $return->sale->number }}
        </div>

        <div class="divider"></div>

        <table>
            @foreach($return->items as $item)
                <tr>
                    <td style="width: 75%;">
                        {{ $item->saleItem->product->name ?? 'PRODUCTO' }}
                        @unless($item->restock)
                            <br><span style="font-size: 10px;">(NO REGRESA A INVENTARIO)</span>
                        @endunless
                    </td>
                    <td align="right" valign="top">x{{ $fmtQty($item->quantity) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="divider"></div>

        <div class="total-row">
            @switch($return->refund_method)
                @case(\App\Models\Sales\Returns\SaleReturn::METHOD_CASH)
                    REEMBOLSADO EN EFECTIVO: {{ $currency }}{{ number_format($return->cash_amount, 2) }}
                    @break
                @case(\App\Models\Sales\Returns\SaleReturn::METHOD_RECEIVABLE)
                    DEUDA REDUCIDA: {{ $currency }}{{ number_format($return->refund_value, 2) }}
                    @break
                @default
                    @if($return->exchangeSale)
                        @php
                            $newLine = $return->exchangeSale->items->first();
                            $cashIn = (float) $return->exchangeSale->payments()->whereHas('tipoPago', fn ($q) => $q->where('slug', \App\Models\Configuration\TipoPago::EFECTIVO))->sum('amount');
                        @endphp
                        CAMBIO POR: {{ $newLine->product->name ?? 'PRODUCTO' }} x{{ $fmtQty($newLine->quantity) }}<br>
                        @if($cashIn > 0)
                            COBRADO: {{ $currency }}{{ number_format($cashIn, 2) }}<br>
                        @endif
                        @if($return->cash_amount > 0)
                            ENTREGADO: {{ $currency }}{{ number_format($return->cash_amount, 2) }}<br>
                        @endif
                        <span style="font-size: 10px;">VENTA DEL CAMBIO: {{ $return->exchangeSale->number }}</span>
                    @else
                        CAMBIO POR EL MISMO PRODUCTO<br>
                        SIN COSTO
                    @endif
            @endswitch
        </div>

        <div class="spacer">
            MOTIVO: {{ $return->reason_label }}
        </div>

        <div class="spacer center" style="font-size: 10px;">
            GRACIAS POR SU PREFERENCIA
        </div>
    </div>
</body>
</html>
