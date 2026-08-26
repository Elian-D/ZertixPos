@props([
    'type' => 'full', // 'full' (imagotipo, ícono + wordmark) o 'icon' (solo isotipo)
    'dark' => false,  // true → variante "-dark" (sin el navy #0E253D, solo el verde) para fondos oscuros
])

@php
    $isFull = $type === 'full';
    $file = ($isFull ? 'imagotipo' : 'isotipo') . ($dark ? '-dark' : '');
    $src = asset("img/logos/{$file}.svg");
    $alt = 'ZertixPOS';
@endphp

{{--
    Logo real de ZertixPOS (vectorizado), en public/img/logos/:
    - imagotipo.svg → ícono + wordmark completo ("ZERTIXPOS Punto de venta e
      inventario inteligente"), viewBox 2074x644 (~3.22:1, ancho).
    - isotipo.svg   → solo el ícono "Z" con los pines de cobertura, viewBox
      497x644 (~0.77:1, más alto que ancho) — usado en el sidebar colapsado.
    Ambos traen sus colores de marca embebidos en el propio SVG (verde
    #7AC943 / navy #0E253D), no dependen de las clases de Tailwind. Las
    variantes "-dark" (creadas para el favicon en prefers-color-scheme:
    dark, ver app-layout.blade.php) quitan el fill navy y dejan solo el
    verde — por eso REQ-7.11 las reutiliza acá con `:dark="true"` para el
    panel navy del login/guest layout, donde el navy del logo original se
    perdería contra el fondo (mismo archivo, uso distinto: "contexto de
    fondo oscuro", no "tema oscuro del navegador").
--}}
<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => $isFull ? 'h-13 w-auto' : 'h-11 w-auto']) }}
>
