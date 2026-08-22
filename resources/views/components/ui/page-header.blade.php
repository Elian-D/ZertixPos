{{--
    resources/views/components/ui/page-header.blade.php
    -----------------------------------------------------
    Título de página + acciones. Reemplaza a x-page-toolbar (mismo rol,
    normalizado bajo el namespace x-ui.*, Fase 7 REQ-7.6).

    SLOTS:
      $actions    — derecha: botones primarios/secundarios de la vista
                    (Crear, Exportar, Volver...), en línea junto al título
                    en desktop, debajo del título en mobile.
      $secondary  — acciones que no ameritan un botón propio en la barra:
                    se agrupan en un menú "···" (dropdown en desktop,
                    bottom sheet en mobile). Cada hijo debe ser un
                    <x-ui.button> (o <a>/<button>) de ancho completo, ej:
                    <x-ui.button appearance="ghost" variant="secondary"
                    class="w-full justify-start" iconLeft="...">Exportar</x-ui.button>

    PROPS:
      title       — string, o pasa <x-slot:title> si necesitas markup rico
                    (ej. título con datos dinámicos condicionales).
      description — subtítulo opcional.
      count       — número de registros totales, ej: $users->total().
      countLabel  — etiqueta del badge de conteo (default 'registros').
--}}

@props([
    'title'       => '',
    'description' => null,
    'count'       => null,   // número de registros totales, ej: $users->total()
    'countLabel'  => 'registros',
])

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">

    {{-- Izquierda: título + subtítulo + contador --}}
    <div class="min-w-0">
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-slate-800 leading-tight">
                {{ $title }}
            </h1>

            @if($count !== null)
                <x-ui.badge variant="slate" size="sm" :dot="false">{{ number_format($count) }} {{ $countLabel }}</x-ui.badge>
            @endif
        </div>

        @if($description)
            <p class="text-sm text-slate-400 mt-0.5">
                {{ $description }}
            </p>
        @endif
    </div>

    {{-- Derecha: acciones primarias + menú de acciones secundarias --}}
    @if(isset($actions) || (isset($secondary) && $secondary->isNotEmpty()))
        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">

            @if(isset($actions))
                <div class="flex items-center gap-2 flex-wrap">
                    {{ $actions }}
                </div>
            @endif

            @if(isset($secondary) && $secondary->isNotEmpty())
                <div class="relative flex-shrink-0"
                    x-data="{
                        open: false,
                        isMobile: window.innerWidth < 768,
                        init() {
                            const mq = window.matchMedia('(max-width: 767px)');
                            mq.addEventListener('change', (e) => {
                                this.isMobile = e.matches;
                                if (!this.isMobile) this.open = false;
                            });
                        }
                    }"
                >
                    {{-- Trigger --}}
                    <button
                        @click="open = !open"
                        aria-label="Más acciones"
                        class="flex items-center justify-center w-9 h-9 rounded-lg border transition-all duration-200
                               border-slate-200 bg-white
                               text-slate-500
                               hover:border-slate-300 hover:text-slate-700"
                    >
                        <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
                    </button>

                    {{-- Desktop: dropdown --}}
                    <div
                        x-show="open && !isMobile"
                        @click="open = false"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        x-cloak
                        class="absolute right-0 top-full mt-2 z-50 w-60 rounded-xl border shadow-2xl p-2
                               bg-white border-slate-100
                               flex flex-col gap-0.5"
                    >
                        {{ $secondary }}
                    </div>

                    {{-- Mobile: overlay + bottom sheet --}}
                    <div
                        x-show="open && isMobile"
                        x-cloak
                        @click="open = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm md:hidden"
                    ></div>

                    <div
                        x-show="open && isMobile"
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="translate-y-full"
                        x-transition:enter-end="translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="translate-y-0"
                        x-transition:leave-end="translate-y-full"
                        class="fixed bottom-0 left-0 right-0 z-50 rounded-t-2xl shadow-2xl md:hidden
                               bg-white border-t border-slate-100"
                    >
                        <div class="flex flex-col items-center pt-3">
                            <div class="w-10 h-1 rounded-full bg-slate-200 mb-3"></div>
                        </div>
                        <div @click="open = false" class="px-4 pb-6 flex flex-col gap-1">
                            {{ $secondary }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    @endif

</div>
