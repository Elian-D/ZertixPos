<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#1E4F8C; padding:24px 32px;">
                            <span style="color:#ffffff; font-size:18px; font-weight:700;">ZertixPOS</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 4px; color:#6b7280; font-size:13px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;">Pago confirmado</p>
                            <h1 style="margin:0 0 20px; color:#111827; font-size:24px; font-weight:800;">
                                {{ $invoice->currency }} ${{ number_format((float) $invoice->amount, 2) }}
                            </h1>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:12px 0; color:#6b7280; font-size:14px;">Negocio</td>
                                    <td style="padding:12px 0; color:#111827; font-size:14px; font-weight:600; text-align:right;">{{ $businessName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; color:#6b7280; font-size:14px; border-top:1px solid #f3f4f6;">Plan</td>
                                    <td style="padding:12px 0; color:#111827; font-size:14px; font-weight:600; text-align:right; border-top:1px solid #f3f4f6;">{{ $planName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; color:#6b7280; font-size:14px; border-top:1px solid #f3f4f6;">Fecha de pago</td>
                                    <td style="padding:12px 0; color:#111827; font-size:14px; font-weight:600; text-align:right; border-top:1px solid #f3f4f6;">{{ $invoice->paid_at?->translatedFormat('d \d\e F, Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; color:#6b7280; font-size:14px; border-top:1px solid #f3f4f6;">Método</td>
                                    <td style="padding:12px 0; color:#111827; font-size:14px; font-weight:600; text-align:right; border-top:1px solid #f3f4f6;">PayPal</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="border-radius:10px; background-color:#7AC943;">
                                        <a href="{{ $pdfUrl }}" style="display:inline-block; padding:12px 28px; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none;">
                                            Ver factura (PDF)
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px; background-color:#f9fafb; text-align:center;">
                            <p style="margin:0; color:#9ca3af; font-size:12px;">
                                Este correo confirma un pago real procesado por PayPal. Si no reconocés este cargo, contactanos de inmediato.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
