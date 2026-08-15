@php
    $config = general_config();
    $impuestoConfig = $config->impuesto;
    $sale = $invoice->sale;
    $client = $sale->client;
    $currency = config('regional.currency_symbol');
    
    // Identificador fiscal de la EMPRESA
    $taxLabel = $config->tax_identifier_type?->value ?? 'RNC';

    // Identificador fiscal del CLIENTE (RNC/Cédula)
    $clientTaxLabel = $client->tax_identifier_type?->value ?? 'RNC/CED';

    // Lógica de impuestos dinámica
    $taxName = $impuestoConfig->nombre ?? 'ITBIS';

    // Usar directamente el valor de la base de datos, si es nulo o cero, mostrará 0.00
    $taxCalculado = $sale->tax_amount ?? 0.00; 

    // Vencimiento de factura (Crédito comercial) — se lee de la Receivable, nunca
    // se recalcula acá (REQ-11.10): es la única fuente de verdad, la misma que
    // controla esMoroso().
    $vencimientoPago = $sale->payment_type === 'credit'
        ? $sale->receivable?->due_date?->format('d/m/Y')
        : null;

    // Lógica de NCF y su Vencimiento Fiscal
    $ncfLog = $sale->ncfLog;
    $vencimientoNcf = $ncfLog?->sequence?->expiry_date 
        ? $ncfLog->sequence->expiry_date->format('d/m/Y') 
        : null;

    // Lógica para Multipay
    $payments = $sale->payments;
    $isMultiPay = $payments->count() > 1;

    // NUEVO: Lógica de visibilidad fiscal
    $mostrarFiscal = module_enabled('sales.ncf') && $sale->ncf;

    // 11.2.6: mismo desglose y misma lógica de reconstrucción que ticket.blade.php —
    // ver el comentario allá para el porqué de usar discount_percentage como señal.
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; margin: 0; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; text-transform: uppercase; color: #1a1a1a; }
        .info-label { color: #666; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        
        /* BANNER FISCAL */
        .invoice-banner { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .ncf-value { font-size: 18px; font-weight: bold; color: #1e293b; font-family: 'Courier New', Courier, monospace; }
        .invoice-type { font-size: 14px; font-weight: bold; color: #475569; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th { background: #1e293b; color: white; padding: 10px; text-transform: uppercase; font-size: 10px; }
        .items-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }

        .totals-container { margin-top: 20px; width: 320px; float: right; }
        .grand-total { font-size: 22px; border-top: 2px solid #1e293b; padding-top: 10px; margin-top: 5px; color: #0f172a; }

        .footer-notes { margin-top: 50px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 15px; clear: both; }
        .dgii-stamp { font-size: 9px; font-weight: bold; color: #94a3b8; text-align: center; margin-top: 5px; text-transform: uppercase; }
        
        .payment-method-badge { 
            background: #f1f5f9; 
            padding: 2px 6px; 
            border-radius: 4px; 
            font-size: 10px; 
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

    {{-- 1. ENCABEZADO --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $config->nombre_empresa }}</div>
                <div style="margin-top: 5px;">{{ $config->direccion }}</div>
                <div>Teléfono: {{ $config->telefono }}</div>
                <div class="bold">{{ $taxLabel }}: {{ $config->tax_id }}</div>
            </td>
            <td class="text-right" style="width: 40%;">
                @if($config->logo)
                    <img src="{{ public_path('storage/'.$config->logo) }}" style="max-height: 70px;">
                @else
                    <div style="height: 70px;"></div>
                @endif
                
                {{-- Ocultar sello DGII si no es fiscal --}}
                @if($mostrarFiscal)
                    <div class="dgii-stamp">Comprobante Autorizado por la DGII</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- 2. BLOQUE FISCAL (NCF / e-NCF) --}}
    <div class="invoice-banner">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%;">
                    <span class="info-label">Número de Factura:</span><br>
                    <span class="bold" style="font-size: 14px;">{{ $invoice->invoice_number }}</span>
                </td>
                <td style="width: 33%; border-left: 1px solid #cbd5e1; padding-left: 15px;">
                    @if($mostrarFiscal)
                        <span class="info-label">{{ $ncfLog?->type?->is_electronic ? 'e-NCF (Secuencia Electrónica):' : 'NCF (Número de Comprobante):' }}</span><br>
                        <span class="ncf-value">{{ $sale->ncf }}</span>
                    @else
                        <span class="info-label">Tipo de Documento:</span><br>
                        <span class="bold">DOCUMENTO</span>
                    @endif
                </td>
                <td class="text-right" style="width: 33%;">
                    <span class="info-label">Condición de Pago:</span><br>
                    <span class="invoice-type">{{ $sale->payment_type === 'credit' ? 'CRÉDITO' : 'CONTADO' }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- 3. CLIENTE Y FECHAS --}}
    <table class="header-table" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
        <tr>
            <td style="width: 45%;">
                <span class="info-label">Cliente:</span><br>
                <span class="bold" style="font-size: 13px;">{{ $client->name }}</span><br>
                {{ $clientTaxLabel }}: <span class="bold">{{ $client->tax_id ?? 'N/A' }}</span><br>
                Tel: {{ $client->phone ?? 'S/N' }}
            </td>
            <td style="width: 30%; border-left: 1px solid #f1f5f9; padding-left: 10px;">
                <span class="info-label">Vendedor / Terminal:</span><br>
                <span>{{ $sale->user->name ?? 'Sistema' }} {{ $sale->posTerminal ? '('.$sale->posTerminal->name.')' : '' }}</span><br>
                <span class="info-label">Fecha de Emisión:</span><br>
                <span>{{ $sale->created_at->format('d/m/Y g:i A') }}</span>
            </td>
            <td style="width: 25%;" class="text-right">
                @if($mostrarFiscal && $vencimientoNcf)
                    <span class="info-label">Vencimiento NCF:</span><br>
                    <span class="bold">{{ $vencimientoNcf }}</span><br><br>
                @endif
                @if($vencimientoPago)
                    <span class="info-label" style="color: #dc2626;">Vence Factura:</span><br>
                    <span class="bold" style="color: #dc2626;">{{ $vencimientoPago }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- 4. TABLA DE PRODUCTOS --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" width="8%">Cant.</th>
                <th style="text-align: left;">Descripción / Producto</th>
                <th class="text-right" width="15%">Precio Unit.</th>
                <th class="text-right" width="15%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td class="text-center bold">{{ (int)$item->quantity }}</td>
                    <td>
                        <span class="bold">{{ $item->product->name }}</span>
                        @if($item->product->sku)
                            <br><small style="color: #64748b;">SKU: {{ $item->product->sku }}</small>
                        @endif
                        @if(($item->discount_percentage ?? 0) > 0)
                            <br><small style="color: #dc2626;">Desc. ítem {{ number_format($item->discount_percentage, 0) }}%: -{{ $currency }}{{ number_format(($item->quantity * $item->unit_price * $item->discount_percentage) / 100, 2) }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $currency }}{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right bold">{{ $currency }}{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 5. TOTALES Y DESGLOSE DE PAGO --}}
    <div style="width: 100%; margin-top: 30px;">
        <div style="width: 45%; float: left; padding: 10px;">
            <span class="info-label" style="display: block; margin-bottom: 5px;">Detalle de Pago:</span>
            @if($isMultiPay)
                @foreach($payments as $payment)
                    <div style="margin-bottom: 3px;">
                        <span class="payment-method-badge">
                            {{ $payment->tipoPago->nombre }}: {{ $currency }}{{ number_format($payment->amount, 2) }}
                        </span>
                    </div>
                @endforeach
            @elseif($sale->payment_type !== 'credit')
                <span class="bold">{{ $sale->tipoPago->nombre ?? 'EFECTIVO' }}</span>
            @endif

            @if($sale->payment_type === 'credit')
                <div style="margin-top: 40px; border-top: 1px solid #94a3b8; text-align: center; width: 250px;">
                    <span class="info-label">Recibido Conforme (Firma y Sello)</span>
                </div>
            @endif
        </div>

        <div class="totals-container">
            <table style="width: 100%;">
                <tr>
                    <td class="info-label" style="padding: 5px 0;">Subtotal Bruto:</td>
                    <td class="text-right bold" style="font-size: 14px;">{{ $currency }}{{ number_format($sale->total_amount, 2) }}</td>
                </tr>
                @if($itemDiscountTotal > 0.01)
                <tr style="color: #dc2626;">
                    <td class="info-label" style="padding: 5px 0;">Desc. por Ítems:</td>
                    <td class="text-right bold" style="font-size: 14px; color: #dc2626;">-{{ $currency }}{{ number_format($itemDiscountTotal, 2) }}</td>
                </tr>
                @endif
                @if($globalDiscountTotal > 0.01)
                <tr style="color: #dc2626;">
                    <td class="info-label" style="padding: 5px 0;">Desc. Global ({{ number_format($globalDiscountPct, 0) }}%):</td>
                    <td class="text-right bold" style="font-size: 14px; color: #dc2626;">-{{ $currency }}{{ number_format($globalDiscountTotal, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="info-label" style="padding: 5px 0;">Subtotal Neto:</td>
                    <td class="text-right bold" style="font-size: 14px;">{{ $currency }}{{ number_format($sale->total_amount - $sale->discount_total, 2) }}</td>
                </tr>
                @if($taxCalculado > 0)
                    <tr>
                        <td class="info-label" style="padding: 5px 0;">{{ $taxName }}:</td>
                        <td class="text-right bold" style="font-size: 14px;">{{ $currency }}{{ number_format($taxCalculado, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td class="bold">TOTAL:</td>
                    <td class="text-right bold">{{ $currency }}{{ number_format($sale->total_amount - $sale->discount_total, 2) }}</td>
                </tr>
                @if($sale->payment_type === 'cash' && $sale->cash_received > 0)
                <tr>
                    <td class="info-label" style="padding: 5px 0;">Efectivo Recibido:</td>
                    <td class="text-right">{{ $currency }}{{ number_format($sale->cash_received, 2) }}</td>
                </tr>
                <tr>
                    <td class="info-label" style="padding: 5px 0;">Cambio:</td>
                    <td class="text-right">{{ $currency }}{{ number_format($sale->cash_change, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    {{-- 6. PIE DE PÁGINA --}}
    <div class="footer-notes">
        @if($sale->notes)
            <p><strong>Observaciones:</strong> {{ $sale->notes }}</p>
        @endif
        <p class="text-center bold" style="color: #475569; font-size: 11px;">
            @if($mostrarFiscal)
                {{ $ncfLog?->type?->name ?? 'Factura con Valor Fiscal' }}
            @else
                Documento de Venta Interna
            @endif
            - {{ $config->nombre_empresa }}
        </p>
    </div>

</body>
</html>