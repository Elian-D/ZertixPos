{{--
    REQ-3.11, v1.3.0 Fase 3 — rediseño 2026-09-07 (mockups Stitch "Resumen de
    Suscripción" ×3 + "Facturación y Suscripción" + "Facturación y Pago
    PayPal"): pasó de tener su propio chrome (header/footer a mano, sin
    sidebar) a vivir dentro de <x-app-layout>, como cualquier otra pantalla
    autenticada del sistema — pedido explícito del usuario. Antes vivía
    fuera de app-layout porque en su primera versión (solo el selector de
    plan) no tenía sentido el sidebar completo; ahora que es el resumen de
    cuenta real (estado, próxima factura, método de pago), sí es una pantalla
    más de la app, no un checkout aislado.
--}}
<x-app-layout title="Suscripción y Facturación">
    <livewire:billing.manage-subscription />
</x-app-layout>
