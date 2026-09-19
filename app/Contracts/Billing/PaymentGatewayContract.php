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
     * REQ-4.7.1 — cambia el plan de una suscripción YA activa (upgrade o
     * downgrade), sin crear un acuerdo de pasarela nuevo — a diferencia de
     * `createSubscription()`, que sí crea uno. PayPal (confirmado en vivo
     * contra el sandbox real) no prorratea nada solo, y el precio nuevo
     * recién aplica en el ciclo siguiente sin importar cuándo se apruebe —
     * pero SIEMPRE exige que el comprador reconsienta el cambio (devuelve un
     * `approval_url`, mismo patrón que `createSubscription()`), no hay forma
     * de aplicarlo en silencio ni siquiera para un downgrade. Que las
     * funcionalidades del plan nuevo se reflejen ya (upgrade) o recién al
     * terminar el ciclo (downgrade, `Subscription::scheduled_plan_id`) es una
     * decisión de la app, no de la pasarela — ver REQ-4.7.1 en
     * docs/features/v1.3.0.md.
     *
     * @return array{approval_url: string}
     */
    public function reviseSubscription(
        Subscription $subscription,
        Plan $newPlan,
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
     * Pregunta el estado real de una suscripción DIRECTO a la pasarela (no
     * espera un webhook) y, si ya está activa del otro lado, actualiza
     * `$subscription` localmente de la misma forma que `handleWebhook()` lo
     * haría. Pensado para el `return_url` (REQ-3.11, `billing.approved`):
     * el comprador ya aprobó en PayPal en ese momento — no hay razón para
     * hacerlo esperar al webhook asíncrono para ver su cuenta activa, y el
     * webhook puede tardar, fallar, o no llegar nunca (hallazgo real
     * 2026-09-06 probando el sandbox — ver docs/features/v1.3.0.md §3.11).
     *
     * @return bool true si la suscripción quedó (o ya estaba) activa
     */
    public function syncSubscriptionStatus(Subscription $subscription): bool;

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
