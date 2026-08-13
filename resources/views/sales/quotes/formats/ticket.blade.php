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
            font-size: 12px; line-height: 1.2; font-weight: bold; text-transform: uppercase;
        }
        .ticket { width: 72mm; margin: 0 auto; padding: 10px 5px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .border-top { border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <span style="font-size: 16px;">*** COTIZACIÓN ***</span><br>
            <span>{{ $config->nombre_empresa }}</span><br>
            <span style="font-size: 10px;">{{ $config->tax_id }}</span>
        </div>

        <div class="border-top">
            NO. COTIZACIÓN: {{ str_pad($quote->id, 8, '0', STR_PAD_LEFT) }}<br>
            FECHA: {{ $quote->created_at->format('d/m/Y H:i') }}<br>
            VENCE: {{ $quote->expires_at->format('d/m/Y') }}
        </div>

        <div class="border-top">
            CLIENTE: {{ substr($quote->customer->name, 0, 25) }}<br>
            VENDEDOR: {{ $quote->user->name }}
        </div>

        <table style="width: 100%; margin-top: 10px;">
            <tr style="border-bottom: 1px solid #000;">
                <th align="left">CANT/DESC</th>
                <th class="right">TOTAL</th>
            </tr>
            @foreach($quote->items as $item)
            <tr>
                <td>{{ (int)$item->quantity }} x {{ substr($item->product->name, 0, 15) }}</td>
                <td class="right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </table>

        <div class="border-top">
            <table style="width: 100%;">
                <tr>
                    <td>SUBTOTAL:</td>
                    <td class="right">{{ $currency }}{{ number_format($quote->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>DESC.:</td>
                    <td class="right">-{{ $currency }}{{ number_format($quote->discount_total, 2) }}</td>
                </tr>
                <tr style="font-size: 14px;">
                    <td>TOTAL:</td>
                    <td class="right">{{ $currency }}{{ number_format($quote->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="center" style="margin-top: 20px; font-size: 10px;">
            ESTOS PRECIOS SON VÁLIDOS HASTA EL {{ $quote->expires_at->format('d/m/Y') }}<br>
            DOCUMENTO NO VÁLIDO PARA CRÉDITO FISCAL
        </div>
    </div>
</body>
</html>