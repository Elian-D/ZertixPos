@php
    $config = general_config();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Cobro - {{ $payment->receipt_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1f2937; font-size: 13px; line-height: 1.5; margin: 0; }
        .header { width: 100%; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; }
        .logo { max-height: 70px; max-width: 200px; margin-bottom: 5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .company-info h2 { margin: 0; color: #111827; text-transform: uppercase; font-size: 18px; }
        .company-info p { margin: 1px 0; color: #4b5563; font-size: 11px; }
        .document-title { color: #111827; margin-top: 10px; text-transform: uppercase; font-size: 16px; font-weight: bold; }
        
        .info-section { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .info-box { background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb; }
        
        .details-table { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .details-table th { background-color: #f3f4f6; color: #374151; padding: 8px; text-align: left; font-size: 11px; border-bottom: 2px solid #e5e7eb; }
        .details-table td { padding: 10px; border-bottom: 1px solid #f3f4f6; }
        
        .total-amount { font-size: 18px; font-weight: bold; color: #111827; }
        .footer { position: fixed; bottom: 30px; width: 100%; text-align: center; font-size: 10px; color: #9ca3af; }
        .signature-line { border-top: 1px solid #d1d5db; width: 220px; margin: 40px auto 5px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 60%;" class="company-info">
                @if($config && $config->logo)
                    {{-- Usamos storage_path para que dompdf acceda localmente a la imagen --}}
                    <img src="{{ storage_path('app/public/' . $config->logo) }}" class="logo">
                @endif
                <h2>{{ $config->nombre_empresa ?? 'Plaza Merengue SRL' }}</h2>
                <p><strong>{{ $config->tax_identifier_type?->value ?? 'RNC' }}:</strong> {{ $config->tax_id ?? 'N/A' }}</p>
                <p>{{ $config->direccion ?? 'Dirección no configurada' }}, {{ $config->municipio->name ?? $config->provincia->name ?? '' }}</p>
                <p>Tel: {{ $config->telefono ?? 'N/A' }} | Email: {{ $config->email ?? 'N/A' }}</p>
            </td>
            <td class="text-right" style="width: 40%; vertical-align: top;">
                <h3 class="document-title">Recibo de Cobro</h3>
                <p style="font-size: 14px; margin: 5px 0;"><strong>No: {{ $payment->receipt_number }}</strong></p>
                <p>Fecha: {{ $payment->payment_date->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>

    <div class="info-section">
        <div class="info-box">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 20%;"><strong>Recibimos de:</strong></td>
                    <td style="border-bottom: 1px dashed #d1d5db;">{{ $payment->client->name }}</td>
                </tr>
                <tr>
                    <td style="padding-top: 8px;"><strong>Concepto:</strong></td>
                    <td style="padding-top: 8px; border-bottom: 1px dashed #d1d5db;">
                        Cobro aplicado a {{ $payment->receivable->document_number }} - {{ $payment->note ?? 'Sin observaciones adicionales' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>MÉTODO DE PAGO</th>
                <th>REFERENCIA / OPERACIÓN</th>
                <th class="text-right">MONTO RECIBIDO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->tipoPago?->nombre ?? 'N/A' }}</td>
                <td>{{ $payment->reference ?? 'N/A' }}</td>
                <td class="text-right total-amount">
                    {{ config('regional.currency_symbol') }} {{ number_format($payment->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 65%;">
                    <p style="font-size: 11px; color: #6b7280;">
                        <strong>Balance Pendiente Factura:</strong> {{ config('regional.currency_symbol') }} {{ number_format($payment->receivable->current_balance, 2) }}<br>
                        <strong>Balance Total Cliente:</strong> {{ config('regional.currency_symbol') }} {{ number_format($payment->client->balance, 2) }}
                    </p>
                </td>
                <td class="text-right">
                    <div style="background: #f3f4f6; padding: 10px; border-radius: 4px; border: 1px solid #e5e7eb;">
                        <small>TOTAL</small><br>
                        <span class="total-amount">{{ config('regional.currency_symbol') }} {{ number_format($payment->amount, 2) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 80px;">
        <table style="width: 100%;">
            <tr>
                <td class="text-center" style="width: 50%;">
                    <div class="signature-line"></div>
                    <p style="margin: 0;">{{ $payment->creator->name ?? 'Cajero Autorizado' }}</p>
                    <small style="color: #9ca3af;">Firma Autorizada</small>
                </td>
                <td class="text-center" style="width: 50%;">
                    <div class="signature-line"></div>
                    <p style="margin: 0;">{{ $payment->client->name }}</p>
                    <small style="color: #9ca3af;">Firma del Cliente</small>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ $config->nombre_empresa ?? config('app.name') }} | Generado el {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>