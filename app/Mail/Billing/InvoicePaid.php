<?php

namespace App\Mail\Billing;

use App\Models\Landlord\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Fase 4.9, REQ-4.9 — correo real de "gracias por tu pago", disparado desde
 * `PayPalGateway::onPaymentCompleted()` (el único punto donde de verdad se
 * confirma un cobro, vía webhook). `ShouldQueue` a propósito: no debe hacer
 * esperar la respuesta al webhook de PayPal por un envío de correo lento.
 *
 * Mismo patrón que Stripe/Claude: el cuerpo trae el resumen (monto, plan,
 * fecha), el PDF NO va adjunto — un link firmado a `billing.invoice.pdf`
 * (`InvoicePdfController`, ruta central porque `SubscriptionInvoice` es
 * landlord) que genera el PDF al vuelo. Más liviano que un adjunto (algunos
 * proveedores SMTP transaccionales cobran/limitan por tamaño de adjunto) y
 * siempre queda actualizado al diseño vigente, no al de cuando se envió.
 */
class InvoicePaid extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SubscriptionInvoice $invoice, public string $businessName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu pago de ZertixPOS — {$this->invoice->currency} {$this->invoice->amount}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.billing.invoice-paid',
            with: [
                'businessName' => $this->businessName,
                'planName' => $this->invoice->plan?->name ?? $this->invoice->subscription?->plan?->name ?? '—',
                'pdfUrl' => URL::temporarySignedRoute(
                    'billing.invoice.pdf',
                    now()->addDays(30),
                    ['invoice' => $this->invoice->id],
                ),
            ],
        );
    }
}
