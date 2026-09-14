{{--
    REQ-3.11, v1.3.0 Fase 3 — destino del `return_url` que PayPal usa tras la
    aprobación del comprador. Rediseño 2026-09-07: antes era una vista
    estática que solo decía "esperá al webhook" — ahora corre
    `SubscriptionApproved`, que confirma el estado DIRECTO contra la API de
    PayPal apenas el comprador vuelve, en vez de depender de que el webhook
    llegue (hallazgo real: en el sandbox, no llegó nunca — ver docblock del
    componente). Dentro de `<x-app-layout>`, como el resto del flujo de
    facturación rediseñado — el comprador ya está autenticado en este punto
    (solo se llega acá desde `billing.manage`, que requiere `auth`).
--}}
<x-app-layout>
    <livewire:billing.subscription-approved />
</x-app-layout>
