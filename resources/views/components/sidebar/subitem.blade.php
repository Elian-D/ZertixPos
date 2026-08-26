@props(['href', 'icon' => null, 'active' => null])

@php
    // REQ-7.9 (séptima pasada): el comodín de respaldo (request()->is($path.'*'))
    // existe para que un subitem siga resaltado en sus propias páginas hijas (ej.
    // "Clientes" resaltado en /app/clients/5/editar) — pero el mismo comodín
    // también atrapa a CUALQUIER OTRO subitem hermano cuya URL viva anidada bajo
    // ese mismo prefijo (ej. "Cotizaciones" en /app/clients/quotes, tras la
    // migración de rutas), resaltando dos subitems del mismo dropdown a la vez
    // (bug real reportado). No hay forma de que este componente adivine solo
    // cuál es cuál con solo un $href — Orvian real (ComponentsTEMP/sidebar.blade.php)
    // resuelve esto pasando `:active` explícito desde el caller, que sí conoce el
    // alcance real de cada link. Mismo patrón acá: si el caller pasa `:active`,
    // se usa tal cual; si no, se mantiene el comodín de antes para no romper los
    // ~25 subitems que no tienen este conflicto.
    if ($active === null) {
        $path = ltrim(parse_url($href, PHP_URL_PATH) ?: $href, '/');
        $active = request()->is($path) || request()->is($path.'*');
    }
@endphp

{{-- Sin x-show en el texto (REQ-7.9, segunda pasada): este componente solo se
     renderiza dentro del submenu inline (sidebar expandido) o del flyout
     (colapsado) de dropdown.blade.php — en ambos casos el texto debe verse
     siempre que el subitem esté visible, ya no hay un estado intermedio de
     "hover expandiendo" que lo mantuviera oculto. --}}
<a href="{{ $href }}"
   {{-- min-w-0 + whitespace-nowrap en el <span> (no aquí, ver abajo): defensa
        extra contra el texto comprimiéndose/deformándose si el <nav> llega a
        angostarse con este subitem todavía en el DOM (REQ-7.9, tercera pasada). --}}
   class="flex items-center gap-3 px-3 py-1.5 rounded-lg text-sm transition-all duration-200 group min-w-0
          {{ $active
            ? 'text-zertix-primary font-medium bg-zertix-primary/5'
            : 'text-slate-500 hover:text-zertix-secondary hover:bg-slate-50'
          }}">

    @if ($icon)
        <x-dynamic-component :component="$icon"
            class="w-4 h-4 flex-shrink-0 transition-colors duration-200
                   {{ $active ? 'text-zertix-primary' : 'text-slate-400 group-hover:text-zertix-secondary' }}" />
    @else
        <div class="w-1.5 h-1.5 rounded-full transition-all duration-200
                    {{ $active ? 'bg-zertix-primary shadow-sm' : 'bg-slate-300 group-hover:bg-zertix-secondary' }}">
        </div>
    @endif

    <span class="tracking-wide whitespace-nowrap overflow-hidden text-ellipsis">
        {{ $slot }}
    </span>
</a>
