@props(['id', 'icon' => null, 'label', 'activeRoutes' => []])

@php
    // Detección de activo por PATH (request()->is()), no por nombre de ruta
    // (request()->routeIs(), que usa el dropdown.blade.php original de
    // Orvian) — los call sites de app-layout.blade.php pasan patrones de URL
    // tipo 'app/clients*', no nombres de ruta.
    $isActive = false;
    foreach ($activeRoutes as $pattern) {
        $pattern = ltrim(trim($pattern), '/');
        if ($pattern === '') {
            continue;
        }
        if (request()->is($pattern) || request()->is($pattern.'*')) {
            $isActive = true;
            break;
        }
    }
@endphp

{{--
    REQ-7.9 (segunda pasada): con el sidebar colapsado, ya NO se expande el
    panel completo al pasar el mouse ("hover brusco", molestaba incluso en el
    Orvian original). Ahora:
    - Hover sobre el botón colapsado → tooltip con el nombre del módulo.
    - Clic sobre el botón colapsado → flyout a la derecha con los subitems
      (mismo contenido que el submenu inline de cuando está expandido).
    Ambos flotantes usan x-teleport="body" + position:fixed con coordenadas
    de getBoundingClientRect() calculadas al vuelo — evita que el
    overflow-y:auto del <nav> los recorte o los deje mal posicionados (el
    bug real de la captura: el flyout vivía DENTRO del <nav>, heredando su
    contexto de overflow/scroll en vez de flotar libre sobre toda la página).
--}}
<div class="w-full relative"
     x-data="{ hovering: false, tipTop: 0, tipLeft: 0, flyTop: 0, flyLeft: 0 }"
     {{-- Auto-abrir el acordeón en la ruta activa SOLO si el sidebar está
         expandido — si se dejaba sin la condición de sidebarOpen, un módulo
         activo con el sidebar colapsado dejaba openDropdown ya apuntando a
         este id desde el primer render, y el flyout (que se muestra con
         `!sidebarOpen && openDropdown === id`) aparecía abierto solo sin
         que el usuario hiciera clic (bug real reportado). --}}
     x-init="if ({{ $isActive ? 'true' : 'false' }} && sidebarOpen) openDropdown = '{{ $id }}'">

    <button
        @mouseenter="hovering = true; tipTop = $el.getBoundingClientRect().top + $el.offsetHeight / 2; tipLeft = $el.getBoundingClientRect().right + 12"
        @mouseleave="hovering = false"
        @click="
            if (sidebarOpen) {
                openDropdown = (openDropdown === '{{ $id }}' ? null : '{{ $id }}');
            } else {
                flyTop = $el.getBoundingClientRect().top;
                flyLeft = $el.getBoundingClientRect().right + 12;
                openDropdown = (openDropdown === '{{ $id }}' ? null : '{{ $id }}');
            }
        "
        class="flex items-center justify-between w-full px-4 py-2.5 rounded-xl transition-all duration-200 group relative
        {{ $isActive
            ? 'bg-zertix-primary/5 text-zertix-primary shadow-sm'
            : 'text-slate-500 hover:bg-slate-100 hover:text-zertix-secondary'
        }}">

        @if ($isActive)
            <div class="absolute left-0 w-1 h-5 bg-zertix-primary/50 rounded-r-full"></div>
        @endif

        <div class="flex items-center gap-3">
            <x-dynamic-component :component="$icon"
                class="w-5 h-5 flex-shrink-0 transition-colors duration-200
                       {{ $isActive ? 'text-zertix-primary' : 'text-slate-400 group-hover:text-zertix-primary' }}" />

            <span x-show="sidebarOpen" x-cloak class="text-sm font-medium whitespace-nowrap tracking-wide">
                {{ $label }}
            </span>
        </div>

        <x-heroicon-s-chevron-right x-show="sidebarOpen" x-cloak
            class="w-4 h-4 transition-transform duration-200 {{ $isActive ? 'text-zertix-primary' : 'text-slate-400' }}"
            {{-- "::" (no ":"): blade-icons declara `class` como prop propio del
                 componente, así que ":class=" lo evaluaría como PHP en vez de
                 pasarlo a Alpine — mismo bug ya visto esta fase con `x-ui.forms.*`. --}}
            ::class="{ 'rotate-90': openDropdown === '{{ $id }}' }" />
    </button>

    {{-- Tooltip (colapsado, sin abrir el flyout). x-teleport va en un
         <template> — aplicado directo sobre un <div> revienta con "Cannot
         read properties of undefined (reading 'cloneNode')" porque Alpine
         mueve `.content` de un <template>, propiedad que un <div> no tiene;
         ese error abortaba el resto del recorrido de Alpine y dejaba sin
         enlazar hasta el botón de abrir/cerrar el sidebar del header. --}}
    <template x-teleport="body">
        {{-- :style como OBJETO, no string interpolado — un string reemplaza
             TODO el atributo style en cada re-evaluación (cada vez que
             tipTop/tipLeft cambian en @mouseenter), borrando el
             "display:none" que x-show ya había escrito; como x-show solo
             vuelve a tocar el DOM cuando su propia condición CAMBIA, el
             tooltip se quedaba visible ("pisado") aunque sidebarOpen fuera
             true. El objeto le pide a Alpine mezclar solo top/left, sin
             tocar display (bug real encontrado en vivo). --}}
        <div x-show="!sidebarOpen && hovering && openDropdown !== '{{ $id }}'" x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             :style="{ top: tipTop + 'px', left: tipLeft + 'px' }"
             class="fixed -translate-y-1/2 px-2 py-1 bg-white text-slate-800 text-xs font-medium rounded-lg border border-slate-200 whitespace-nowrap pointer-events-none z-[60] shadow-lg">
            {{ $label }}
        </div>
    </template>

    {{-- Submenu inline (sidebar expandido). REQ-7.9, quinta pasada: la primera
         corrección (quitar x-transition:leave para ocultar al instante) cambió
         el problema en vez de resolverlo — el ancho del panel sigue animando
         300ms (layout.blade.php) mientras el submenu desaparecía en 0ms, y ese
         desfase (una cosa instantánea, otra todavía animando alrededor) seguía
         viéndose como un glitch en la esquina. La corrección real es que
         AMBAS animaciones duren y se sincronicen igual — 300ms ease-in-out,
         mismo timing que la transición de ancho del `<aside>` — para que
         terminen exactamente juntas, sin ningún punto intermedio donde una ya
         terminó y la otra no. overflow-hidden evita que el contenido se
         desborde visualmente mientras el ancho todavía se está encogiendo. --}}
    <div x-show="sidebarOpen && openDropdown === '{{ $id }}'" x-cloak
        x-transition:enter="transition ease-in-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in-out duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mt-1 ml-4 pl-6 border-l border-slate-200 space-y-1 overflow-hidden">
        {{ $slot }}
    </div>

    {{-- Flyout (colapsado, al hacer clic) — mismos $slot que el submenu inline
         de arriba, renderizado dos veces (uno visible por vez, según
         sidebarOpen); es más simple que clonar/portalear el slot dinámicamente
         y el costo de DOM extra es insignificante para 3-6 subitems. --}}
    <template x-teleport="body">
        {{-- @click.away con guard (REQ-7.9, cuarta pasada): sin el "if", clic en
             OTRO dropdown mientras este flyout está abierto disparaba dos
             escrituras a openDropdown en el mismo evento — el @click del botón
             nuevo (openDropdown = 'otro-id') y este click.away (openDropdown =
             null) — y por orden de burbuja hasta document, click.away corría
             DESPUÉS y pisaba el valor nuevo con null, dejando todo cerrado en
             vez de abrir el que se acababa de clickear (bug real reportado).
             El guard solo limpia si este flyout sigue siendo el abierto. --}}
        <div x-show="!sidebarOpen && openDropdown === '{{ $id }}'" x-cloak
            @click.away="if (openDropdown === '{{ $id }}') { openDropdown = null }"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 -translate-x-1"
            x-transition:enter-end="opacity-100 scale-100 translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :style="{ top: flyTop + 'px', left: flyLeft + 'px' }"
            class="fixed w-56 bg-white rounded-xl shadow-2xl border border-slate-100 py-2 z-[60] origin-top-left">
            <div class="px-3 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $label }}</div>
            <div class="px-2 pb-1 flex flex-col gap-1">
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
