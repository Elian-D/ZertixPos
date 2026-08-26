@props(['href' => '#', 'icon' => null])

@php
    // Misma detección de activo que la versión anterior de este componente
    // (no la de Orvian, que exige `active` explícito por el caller) — evita
    // tener que tocar cada call site de app-layout.blade.php para pasarlo.
    $path = parse_url($href, PHP_URL_PATH) ?: $href;
    $path = ltrim($path, '/');
    $active = request()->is($path) || request()->is($path.'*') || url()->current() === url($href);
@endphp

<div class="relative" x-data="{ hovering: false, tipTop: 0, tipLeft: 0 }">
    <a href="{{ $href }}"
        @mouseenter="hovering = true; tipTop = $el.getBoundingClientRect().top + $el.offsetHeight / 2; tipLeft = $el.getBoundingClientRect().right + 12"
        @mouseleave="hovering = false"
        {{ $attributes->merge([
            'class' => 'group flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 relative ' .
            ($active
                ? 'bg-zertix-primary/10 text-zertix-primary shadow-sm'
                : 'text-slate-500 hover:bg-slate-100 hover:text-zertix-secondary')
        ]) }}>

        @if ($active)
            <div class="absolute left-0 w-1 h-5 bg-zertix-primary rounded-r-full"></div>
        @endif

        <x-dynamic-component :component="$icon"
            class="w-5 h-5 flex-shrink-0 transition-colors duration-200
                   {{ $active ? 'text-zertix-primary' : 'text-slate-400 group-hover:text-zertix-primary' }}" />

        <span x-show="sidebarOpen" x-cloak
              class="text-sm font-medium whitespace-nowrap overflow-hidden tracking-wide">
            {{ $slot }}
        </span>
    </a>

    {{-- Tooltip (sidebar colapsado): x-teleport SOLO funciona sobre un
         <template> (mueve su .content — un <div> normal no tiene esa
         propiedad, y usarlo ahí revienta con "Cannot read properties of
         undefined (reading 'cloneNode')" en pleno arranque de Alpine, lo que
         abortaba el resto del recorrido y dejaba sin enlazar hasta el botón
         de abrir/cerrar el sidebar del header — bug real encontrado en esta
         misma pasada). Mueve el contenido a <body> preservando el
         scope/reactividad de Alpine (su función documentada) para que no
         quede recortado por el overflow-y:auto del <nav> — position:fixed +
         coordenadas de getBoundingClientRect(), calculadas en el propio
         @mouseenter, en vez de depender de "hover brusco" expandiendo el
         sidebar entero (REQ-7.9, segunda pasada). --}}
    <template x-teleport="body">
        {{-- :style como OBJETO, no string interpolado — un string reemplaza
             TODO el atributo style en cada re-evaluación (cada vez que
             tipTop/tipLeft cambian en @mouseenter), borrando el
             "display:none" que x-show ya había escrito; como x-show solo
             vuelve a tocar el DOM cuando su propia condición CAMBIA (no en
             cada render), el tooltip se quedaba visible ("pisado") aunque
             sidebarOpen fuera true. El objeto le pide a Alpine mezclar solo
             top/left, sin tocar display (bug real encontrado en vivo). --}}
        <div x-show="!sidebarOpen && hovering" x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             :style="{ top: tipTop + 'px', left: tipLeft + 'px' }"
             class="fixed -translate-y-1/2 px-2 py-1 bg-white text-slate-800 text-xs font-medium rounded-lg border border-slate-200 whitespace-nowrap pointer-events-none z-[60] shadow-lg">
            {{ $slot }}
        </div>
    </template>
</div>
