<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura #{{ $invoice->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { border-bottom: 3px solid #1E4F8C; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: 700; color: #1E4F8C; }
        .badge { display: inline-block; background-color: #7AC943; color: #ffffff; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 10px; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.details td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        table.details td.label { color: #6b7280; width: 40%; }
        table.details td.value { font-weight: 700; text-align: right; }
        .total-row td { border-bottom: none; border-top: 2px solid #111827; padding-top: 14px; font-size: 16px; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <span class="brand">ZertixPOS</span>
        <span class="badge" style="float: right;">PAGADA</span>
    </div>

    <p style="margin: 0 0 4px; color: #6b7280;">Factura #{{ $invoice->id }}</p>
    <p style="margin: 0; font-size: 14px; font-weight: 700;">{{ $invoice->tenant?->business_name ?: $invoice->tenant_id }}</p>

    <table class="details">
        <tr>
            <td class="label">Plan</td>
            <td class="value">{{ $invoice->plan?->name ?? $invoice->subscription?->plan?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de pago</td>
            <td class="value">{{ $invoice->paid_at?->translatedFormat('d \d\e F, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Método de pago</td>
            <td class="value">PayPal</td>
        </tr>
        <tr>
            <td class="label">Id. de transacción</td>
            <td class="value">{{ $invoice->gateway_transaction_id }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">Total pagado</td>
            <td class="value">{{ $invoice->currency }} ${{ number_format((float) $invoice->amount, 2) }}</td>
        </tr>
    </table>

    <p class="footer">
        ZertixPOS — Este documento confirma un pago procesado por PayPal. No es un comprobante fiscal (NCF).
    </p>
</body>
</html>
