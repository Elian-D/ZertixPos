<?php

namespace App\Http\Controllers\Billing;

use App\Contracts\Billing\PaymentGatewayContract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * REQ-3.13, v1.3.0 Fase 3 — receptor real del webhook de PayPal. Ruta central
 * (routes/web.php), no de tenant: Subscription/SubscriptionInvoice son tablas
 * landlord, y PayPal no manda ningún dato de subdominio de tenant en el POST.
 */
class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayContract $gateway): Response
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        // array_change_key_case adentro de verifyWebHookLocally() normaliza el
        // casing — no hace falta tocarlo acá, solo aplanar el HeaderBag.
        $headers = collect($request->headers->all())
            ->map(fn (array $values) => $values[0] ?? '')
            ->all();

        try {
            $handled = $gateway->handleWebhook($payload, $headers, $rawBody);
        } catch (Throwable $e) {
            Log::error('PayPalWebhookController: excepción procesando webhook.', [
                'event_id' => $payload['id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            // 500 le pide a PayPal que reintente — correcto para un fallo nuestro
            // (ej. la base caída), no para un evento simplemente inválido.
            return response('', 500);
        }

        // PayPal reintenta automáticamente ante cualquier respuesta fuera de 2xx.
        // 200 solo cuando la firma era válida — un webhook con firma inválida
        // (posible replay/spoof) no debe animar a que seguir reintentando.
        return response('', $handled ? 200 : 400);
    }
}
