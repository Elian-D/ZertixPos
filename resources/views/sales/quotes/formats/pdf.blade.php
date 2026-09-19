@php
    $config = general_config();
    $currency = config('regional.currency_symbol');
    $quote = isset($quote) ? $quote : null;
    
    $taxLabel = $config->tax_identifier_type?->value ?? 'RNC';
    
    // ESTILOS DE ESTADO
    $statusStyles = \App\Models\Sales\Quotes\Quote::getStatusStyles();
    $statusLabels = \App\Models\Sales\Quotes\Quote::getStatuses();
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
        
        /* ESTADO - Estilos mapeados desde Quote model */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid;
        }

        .status-draft {
            background-color: #f3f4f6;
            color: #374151;
            border-color: #d1d5db;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #047857;
            border-color: #6ee7b7;
        }

        .status-converted {
            background-color: #dbeafe;
            color: #0369a1;
            border-color: #93c5fd;
        }

        .status-expired {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        .status-cancelled {
            background-color: #f3e8ff;
            color: #6b21a8;
            border-color: #e9d5ff;
        }

        .quote-banner { background: #fef3c7; border: 2px solid #f59e0b; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .quote-value { font-size: 18px; font-weight: bold; color: #92400e; font-family: 'Courier New', Courier, monospace; }
        .quote-status { font-size: 14px; font-weight: bold; color: #d97706; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th { background: #1e293b; color: white; padding: 10px; text-transform: uppercase; font-size: 10px; }
        .items-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }

        .totals-container { margin-top: 20px; width: 320px; float: right; }
        .grand-total { font-size: 22px; border-top: 2px solid #1e293b; padding-top: 10px; margin-top: 5px; color: #0f172a; }

        .footer-notes { margin-top: 50px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 15px; clear: both; }
        .quote-stamp { font-size: 9px; font-weight: bold; color: #f59e0b; text-align: center; margin-top: 5px; text-transform: uppercase; }
        
        .expiry-badge { 
            background: #fee2e2; 
            padding: 2px 6px; 
            border-radius: 4px; 
            font-size: 10px; 
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .validity-box {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            padding: 10px;
            border-radius: 6px;
            margin-top: 20px;
            text-align: center;
            color: #1e40af;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $config->nombre_empresa }}</div>
                <div style="margin-top: 5px;">{{ $config->direccion }}</div>
                <div>Telefono: {{ $config->telefono }}</div>
                <div class="bold">{{ $taxLabel }}: {{ $config->tax_id }}</div>
            </td>
            <td class="text-right" style="width: 40%;">
                @if($config->logo)
                    <img src="{{ storage_path('app/public/'.$config->logo) }}" style="max-height: 70px;">
                @else
                    <div style="height: 70px;"></div>
                @endif
                <div class="quote-stamp">COTIZACION COMERCIAL</div>
            </td>
        </tr>
    </table>

    <div class="quote-banner">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%;">
                    <span class="info-label">Numero de Cotizacion:</span><br>
                    <span class="quote-value">#{{ str_pad($quote->id, 8, '0', STR_PAD_LEFT) }}</span>
                </td>
                <td style="width: 33%; border-left: 1px solid #d97706; padding-left: 15px;">
                    <span class="info-label">Estado:</span><br>
                    <span class="status-badge status-{{ $quote->status }}">
                        {{ $statusLabels[$quote->status] ?? ucfirst($quote->status) }}
                    </span>
                </td>
                <td class="text-right" style="width: 33%;">
                    <span class="info-label">Fecha de Emision:</span><br>
                    <span class="bold">{{ $quote->created_at->format('d/m/Y') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="header-table" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
        <tr>
            <td style="width: 45%;">
                <span class="info-label">Cliente:</span><br>
                <span class="bold" style="font-size: 13px;">{{ $quote->customer->name }}</span><br>
                RNC/Cedula: <span class="bold">{{ $quote->customer->tax_id ?? 'N/A' }}</span><br>
                Tel: {{ $quote->customer->phone ?? 'S/N' }}
            </td>
            <td style="width: 30%; border-left: 1px solid #f1f5f9; padding-left: 10px;">
                <span class="info-label">Vendedor:</span><br>
                <span>{{ $quote->user->name ?? 'Sistema' }}</span><br>
                <span class="info-label">Origen:</span><br>
                <span>{{ ucfirst($quote->origin) }}</span>
            </td>
            <td style="width: 25%;" class="text-right">
                <span class="info-label">Valida Hasta:</span><br>
                <span class="bold">{{ $quote->expires_at->format('d/m/Y') }}</span><br><br>
                @php
                    $now = now();
                    $expiresAt = $quote->expires_at;
                    
                    // Calcular días restantes como entero
                    $daysRemaining = (int) $now->diffInDays($expiresAt, false);
                    $isExpired = $daysRemaining < 0;
                    $daysAbsolute = abs($daysRemaining);
                @endphp
                
                <span class="expiry-badge">
                    @if($isExpired)
                        EXPIRADA HACE {{ $daysAbsolute }} DÍA{{ $daysAbsolute !== 1 ? 'S' : '' }}
                    @else
                        {{ $daysRemaining }} DÍA{{ $daysRemaining !== 1 ? 'S' : '' }} DISPONIBLE{{ $daysRemaining !== 1 ? 'S' : '' }}
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" width="8%">Cant.</th>
                <th style="text-align: left;">Descripcion / Producto</th>
                <th class="text-right" width="12%">P. Unitario</th>
                <th class="text-right" width="12%">Descuento</th>
                <th class="text-right" width="15%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
                <tr>
                    <td class="text-center bold">{{ (int)$item->quantity }}</td>
                    <td>
                        <span class="bold">{{ $item->product->name }}</span>
                        @if($item->product->sku)
                            <br><small style="color: #64748b;">SKU: {{ $item->product->sku }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $currency }}{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">
                        @if($item->discount_amount > 0)
                            -{{ $currency }}{{ number_format($item->discount_amount, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right bold">{{ $currency }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="width: 100%; margin-top: 30px;">
        <div class="totals-container">
            <table style="width: 100%;">
                <tr>
                    <td class="info-label" style="padding: 5px 0;">Subtotal:</td>
                    <td class="text-right bold" style="font-size: 14px;">{{ $currency }}{{ number_format($quote->subtotal, 2) }}</td>
                </tr>
                @if($quote->discount_total > 0)
                <tr>
                    <td class="info-label" style="padding: 5px 0;">Descuento Total:</td>
                    <td class="text-right bold" style="font-size: 14px; color: #dc2626;">-{{ $currency }}{{ number_format($quote->discount_total, 2) }}</td>
                </tr>
                @endif
                {{-- Desglose real por tipo de impuesto (Fase 5, REQ-5.12) — mismo patrón
                     que las facturas (REQ-5.6), snapshot de quote_items.tax_breakdown. --}}
                @foreach($quote->items->pluck('tax_breakdown')->filter()->flatten(1)->groupBy('key') as $key => $lines)
                    <tr>
                        <td class="info-label" style="padding: 5px 0;">{{ $lines->first()['label'] }}:</td>
                        <td class="text-right bold" style="font-size: 14px;">{{ $currency }}{{ number_format($lines->sum('amount'), 2) }}</td>
                    </tr>
                @endforeach
                <tr class="grand-total">
                    <td class="bold">TOTAL:</td>
                    <td class="text-right bold">{{ $currency }}{{ number_format($quote->grand_total, 2) }}</td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="validity-box">
        <span class="bold">VALIDA HASTA EL {{ $quote->expires_at->format('d/m/Y') }}</span><br>
        <span style="font-size: 9px;">Estos precios y condiciones expiran en la fecha indicada</span>
    </div>

    <div class="footer-notes">
        @if($quote->notes)
            <p><strong>Observaciones:</strong> {{ $quote->notes }}</p>
        @endif
        <p class="text-center bold" style="color: #475569; font-size: 11px;">
            COTIZACION COMERCIAL - DOCUMENTO NO VALIDO PARA CREDITO FISCAL
            <br>{{ $config->nombre_empresa }}
        </p>
    </div>

</body>
</html>