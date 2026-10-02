{{--
    resources/views/components/ui/action-menu.blade.php
    -----------------------------------------------------
    Menú "···" de acciones genérico (REQ-0.6) — no exclusivo de las filas
    de tabla. Dropdown en desktop, bottom sheet en mobile. Mismo patrón que
    v1.2.0 Fase 7.6 dejó anotado (sin construir) para el kebab de
    x-ui.page-header — se construye una sola vez acá y esa fase lo reusa.

    El dropdown de desktop se teletransporta a <body> (x-teleport) y se
    posiciona con position:fixed, recalculando coords en cada scroll
    mientras está abierto (no solo al abrir).

    Por qué NO alcanza con coordenadas de documento (scrollX/scrollY) ni
    con position:absolute: el layout de la app (app-layout.blade.php) NO
    scrollea el <body> — usa un wrapper exterior `h-screen overflow-hidden`
    y el <main> interno es el que tiene `overflow-y-auto` y realmente
    scrollea. window.scrollY se queda en 0 siempre, así que cualquier
    cálculo basado en scroll de window/document nunca se movía.

    Solución real: recalcular getBoundingClientRect() del trigger en cada
    evento 'scroll' capturado en window con {capture: true} — los eventos
    de scroll no burbujean, pero SÍ se propagan en fase de captura hasta
    window sin importar qué ancestro (main, un div interno, etc.) sea el
    que realmente scrollea. Mientras el menú está abierto, cada scroll
    recalcula top/left en vivo — el dropdown queda "pegado" al botón sin
    depender de asumir cuál elemento scrollea.

    v1.4.0 REQ-3.20 — fix móvil. Antes había dos paneles (dropdown teletransportado
    + bottom sheet dentro de la fila) con criterios distintos: el dropdown dependía
    de un isMobile calculado una sola vez al cargar y no tenía clase que lo ocultara,
    el sheet de md:hidden. Si isMobile quedaba desactualizado salían los dos. Además
    cada instancia agregaba listeners de scroll/resize a window sin quitarlos nunca,
    y con cada re-render de la tabla se acumulaban (menús que quedaban abiertos).
    Ahora: UN solo panel teletransportado (el slot se pinta una vez) que es dropdown
    desde md y bottom sheet abajo, un solo criterio (matchMedia), cierre en cualquier
    cambio de breakpoint y listeners que se quitan en destroy().

    SLOT: los x-ui.action-menu.item que arma el consumidor.

    PROPS:
      label       — texto accesible del trigger (default: "Acciones")
      align       — 'right' | 'left' del dropdown en desktop (default: 'right')
      sheetTitle  — título del bottom sheet en mobile (default: label)

    USO:
      <x-ui.action-menu>
          <x-ui.action-menu.item wire:click="edit({{ $item->id }})" icon="heroicon-o-pencil-square">
              Editar
          </x-ui.action-menu.item>
          <x-ui.action-menu.item wire:click="confirmDelete({{ $item->id }})" icon="heroicon-o-trash" variant="danger">
              Eliminar
          </x-ui.action-menu.item>
      </x-ui.action-menu>
--}}

@props([
    'label'      => 'Acciones',
    'align'      => 'right',
    'sheetTitle' => null,
])

@php
    // Ancho real de w-48 (12rem) — usado para calcular el left del dropdown
    // right-aligned sin depender de que Tailwind lo resuelva en runtime.
    $menuWidth = 192;
@endphp

<div
    x-data="{
        open: false,
        isMobile: false,
        coords: { top: 0, left: 0 },
        mq: null,
        onBreakpoint: null,
        onScroll: null,
        updatePosition() {
            const rect = $refs.trigger.getBoundingClientRect();
            this.coords = {
                top: rect.bottom + 4,
                left: {{ $align === 'right' ? 'Math.max(8, rect.right - ' . $menuWidth . ')' : 'rect.left' }},
            };
        },
        toggle() {
            if (this.open) { this.open = false; return; }
            if (! this.isMobile) this.updatePosition();
            this.open = true;
        },
        init() {
            // Un solo criterio de móvil, el mismo breakpoint que md: de Tailwind.
            this.mq = window.matchMedia('(min-width: 768px)');
            this.isMobile = ! this.mq.matches;
            this.onBreakpoint = (e) => { this.isMobile = ! e.matches; this.open = false; };
            this.mq.addEventListener('change', this.onBreakpoint);

            // capture:true — detecta el scroll de CUALQUIER ancestro scrolleable
            // (ej. <main class=overflow-y-auto>, no solo window/body), ya que
            // 'scroll' no burbujea pero sí se propaga en fase de captura.
            this.onScroll = () => { if (this.open && ! this.isMobile) this.updatePosition(); };
            window.addEventListener('scroll', this.onScroll, true);
        },
        destroy() {
            // Sin esto cada re-render de la tabla dejaba listeners huérfanos.
            this.mq?.removeEventListener('change', this.onBreakpoint);
            window.removeEventListener('scroll', this.onScroll, true);
        }
    }"
    @keydown.escape.window="open = false"
    class="inline-block"
>

    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        aria-label="{{ $label }}"
        class="flex items-center justify-center w-8 h-8 rounded-lg
               text-slate-400 hover:text-slate-700 hover:bg-slate-100
               transition-colors duration-150 focus:outline-none"
    >
        <x-heroicon-s-ellipsis-vertical class="w-5 h-5" />
    </button>

    {{-- UN solo panel, teletransportado al <body> para escapar de cualquier
         contenedor con overflow (la tabla, un modal, etc.). Desde md es un
         dropdown fixed en las coords del trigger; abajo, un bottom sheet con
         overlay. El slot se pinta una sola vez. --}}
    <template x-teleport="body">
        <div>
            <div
                x-show="open && isMobile"
                x-cloak
                @click="open = false"
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm md:hidden"
            ></div>

            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                @click="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                :style="isMobile ? '' : `top: ${coords.top}px; left: ${coords.left}px;`"
                class="fixed z-50 bg-white border-slate-100 shadow-2xl
                       inset-x-0 bottom-0 rounded-t-3xl border-t pb-6
                       md:inset-x-auto md:bottom-auto md:w-48 md:rounded-xl md:border md:py-1.5"
            >
                <div class="md:hidden flex flex-col items-center pt-3 pb-2">
                    <div class="w-10 h-1 rounded-full bg-slate-200 mb-3"></div>
                    <p class="text-sm font-bold text-slate-700 px-5 pb-2 w-full">
                        {{ $sheetTitle ?? $label }}
                    </p>
                </div>
                <div class="px-2 md:px-0">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>

</div>
