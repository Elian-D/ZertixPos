<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Landlord\SubscriptionInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Fase 4.9, REQ-4.9 — sirve el PDF de una factura de suscripción, generado al
 * vuelo (no se guarda en disco). Ruta central (routes/web.php):
 * `SubscriptionInvoice` es landlord, no depende de ningún tenant resuelto.
 *
 * Sin `auth` — es el link que llega por correo (`InvoicePaid`), el cliente lo
 * abre sin necesitar sesión iniciada. Protegido por `signed` en la ruta en
 * vez de un token propio: reutiliza la firma de Laravel, vence sola (30 días,
 * ver `InvoicePaid::content()`), nadie puede adivinar la URL de otra factura.
 */
class InvoicePdfController extends Controller
{
    public function __invoke(SubscriptionInvoice $invoice): Response
    {
        $invoice->load(['plan', 'subscription.plan', 'tenant']);

        $pdf = Pdf::loadView('billing.invoice-pdf', ['invoice' => $invoice])
            ->setPaper('letter', 'portrait');

        return $pdf->stream("factura-{$invoice->id}.pdf");
    }
}
