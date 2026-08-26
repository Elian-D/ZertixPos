@props([
    'type' => 'full', // 'full' (imagotipo, ícono + wordmark) o 'icon' (solo isotipo)
])

@php
    $isFull = $type === 'full';
    $src = $isFull ? asset('img/logos/imagotipo.svg') : asset('img/logos/isotipo.svg');
    $alt = $isFull ? 'ZertixPOS' : 'ZertixPOS';
@endphp

{{--
    Logo real de ZertixPOS (vectorizado), en public/img/logos/:
    - imagotipo.svg → ícono + wordmark completo ("ZERTIXPOS Punto de venta e
      inventario inteligente"), viewBox 2074x644 (~3.22:1, ancho).
    - isotipo.svg   → solo el ícono "Z" con los pines de cobertura, viewBox
      497x644 (~0.77:1, más alto que ancho) — usado en el sidebar colapsado.
    Ambos ya traen sus colores de marca embebidos en el propio SVG (verde
    #7AC943 / navy #0E253D), no dependen de las clases de Tailwind.
--}}
<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => $isFull ? 'h-13 w-auto' : 'h-11 w-auto']) }}
>
