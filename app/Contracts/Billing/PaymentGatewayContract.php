<?php

namespace App\Contracts\Billing;

use App\Models\Configuration\Plan;
use App\Models\Landlord\Subscription;
use App\Models\Tenant;

/**
 * Abstracción de pasarela de pago (REQ-3.3, v1.3.0 Fase 3) — PayPalGateway
 * es la única implementación real hoy; el día que entre Stripe (o cualquier
 * otra), implementa este mismo contrato sin tocar el resto del sistema.
 */
interface PaymentGatewayContract
{
    /**
     * Inicia una suscripción real para $tenant sobre $plan. No queda activa
     * de inmediato — la pasarela exige que el comprador apruebe el cobro
     * recurrente en su propia interfaz (por eso devuelve un approval_url al
     * que hay que redirigirlo); la activación real llega después, vía
     * handleWebhook().
     *
     * @return array{gateway_subscription_id: string, approval_url: string}
     */
    public function createSubscription(
        Tenant $tenant,
        Plan $plan,
        string $payerName,
        string $payerEmail,
        string $returnUrl,
        string $cancelUrl,
    ): array;

    /**
     * Cancela la suscripción real en la pasarela y refleja el resultado en
     * $subscription (status/cancelled_at) de inmediato — no depende de que
     * llegue el webhook de confirmación para que el registro local quede
     * consistente.
     */
    public function cancelSubscription(Subscription $subscription, string $reason): void;

    /**
     * Verifica la firma del webhook entrante y, si es válida, actualiza
     * Subscription/SubscriptionInvoice según el tipo de evento.
     *
     * @param array<string, mixed> $payload Payload ya decodificado (para inspección)
     * @param array<string, string> $headers Cabeceras crudas de la request (case-insensitive)
     * @param string $rawBody Cuerpo crudo sin decodificar — la verificación de firma lo necesita byte a byte
     * @return bool true si la firma era válida y el evento se procesó
     */
    public function handleWebhook(array $payload, array $headers, string $rawBody): bool;
}
