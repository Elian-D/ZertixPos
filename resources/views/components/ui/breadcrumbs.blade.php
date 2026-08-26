{{--
    x-ui.breadcrumbs — adaptado de ComponentsTEMP/breadcrumbs.blade.php (Orvian), con dos
    diferencias deliberadas pedidas por el usuario:

    1. Diseño: en Orvian TODOS los segmentos (incluido el actual) son links planos del mismo
       color gris, solo distinguibles por posición. Acá el segmento actual se resalta como un
       "chip" (fondo + texto zertix-primary) — mismo lenguaje visual que el ítem activo del
       sidebar (ComponentsTEMP/sidebar/item.blade.php: bg-orvian-orange/10 text-orvian-orange),
       para que "dónde estoy parado" se identifique de un vistazo, no solo por ser el último.
    2. El segmento actual NO es un link (ya estás ahí, no tiene sentido navegar a sí mismo) —
       en el original de Orvian sí lo era.

    Adaptado a la estructura de rutas de ZertixPOS (prefijo `app`, no `admin`/`hub` — Fase 3):
    $isAdminContext y el multi-tenant Hub/SuperAdmin de Orvian no aplican acá, se removieron.
--}}
@php
    $segments = request()->segments();
    $ignoredSegments = ['app', 'dashboard'];

    $visibleSegments = [];
    $accUrl = '';
    foreach ($segments as $segment) {
        $accUrl .= '/' . $segment;
        if (!in_array(strtolower($segment), $ignoredSegments) && !is_numeric($segment)) {
            $visibleSegments[] = [
                'label' => str_replace(['-', '_'], ' ', $segment),
                'url'   => $accUrl,
            ];
        }
    }
    $lastIndex = count($visibleSegments) - 1;
@endphp

<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center flex-wrap gap-1 text-xs font-medium tracking-wide">
        <li class="inline-flex items-center">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-zertix-secondary transition flex items-center gap-1">
                <x-heroicon-s-home class="w-3.5 h-3.5" />
                Dashboard
            </a>
        </li>

        @foreach($visibleSegments as $i => $seg)
            <li class="inline-flex items-center gap-1">
                <x-heroicon-s-chevron-right class="w-3 h-3 text-gray-300 flex-shrink-0" />

                @if($i === $lastIndex)
                    {{-- Segmento actual: chip resaltado, no clickeable (ya estás acá) --}}
                    <span class="capitalize font-semibold text-zertix-primary bg-zertix-primary/10 px-2 py-0.5 rounded-md">
                        {{ $seg['label'] }}
                    </span>
                @else
                    <a href="{{ url($seg['url']) }}" class="capitalize text-gray-400 hover:text-zertix-secondary transition">
                        {{ $seg['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
