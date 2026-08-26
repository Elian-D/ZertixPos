@props([
    'activeCount' => 0,
])

<div
    x-data="{
        open: false,
        isMobile: window.innerWidth < 768,
        init() {
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
                if (!this.isMobile) this.open = false;
            });
        }
    }"
    class="relative flex-shrink-0"
>

    <button
        @click="open = !open"
        @class([
            'flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-semibold
             transition-all duration-200 focus:outline-none',
            'border-zertix-primary/40 bg-zertix-primary/8 text-zertix-primary-dark' => $activeCount > 0,
            'border-slate-200 bg-white
             text-slate-600
             hover:border-slate-300
             hover:text-slate-800' => $activeCount === 0,
        ])
        :class="open && !isMobile ? 'border-zertix-primary/40 bg-zertix-primary/5' : ''"
    >
        <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
        <span class="hidden sm:block">Filtros</span>

        @if($activeCount > 0)
            <span class="flex items-center justify-center w-5 h-5 rounded-full
                         bg-zertix-primary text-white text-[10px] font-black leading-none
                         flex-shrink-0">
                {{ $activeCount }}
            </span>
        @endif

        <x-heroicon-s-chevron-down
            class="w-3.5 h-3.5 transition-transform duration-200 hidden sm:block"
            ::class="open && !isMobile ? 'rotate-180' : ''" />
    </button>

    {{-- DESKTOP DROPDOWN --}}
    <div
        x-show="open && !isMobile"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
        x-cloak
        class="absolute right-0 top-full mt-2 z-50
               w-72 rounded-2xl border shadow-2xl
               bg-white
               border-slate-100"
    >
        <div class="flex items-center justify-between px-4 py-3
                    border-b border-slate-100">
            <p class="text-xs font-bold uppercase tracking-wider
                      text-slate-500">
                Filtros
            </p>
            @if($activeCount > 0)
                <button
                    wire:click="clearAllFilters"
                    @click="open = false"
                    class="text-[11px] font-semibold text-zertix-primary-dark hover:text-zertix-primary-dark/80
                           transition-colors duration-200">
                    Limpiar todo
                </button>
            @endif
        </div>

        <div class="p-4 space-y-4 max-h-[60vh] overflow-y-auto custom-scroll">
            {{ $slot }}
        </div>
    </div>

    {{-- MOBILE DRAWER --}}
    <div
        x-show="open && isMobile"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click="open = false"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm md:hidden"
    ></div>

    <div
        x-show="open && isMobile"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        x-cloak
        class="fixed bottom-0 left-0 right-0 z-50 rounded-t-3xl shadow-2xl
               bg-white
               border-t border-slate-100
               md:hidden"
    >
        <div class="flex flex-col items-center pt-3 pb-2">
            <div class="w-10 h-1 rounded-full bg-slate-200 mb-3"></div>
            <div class="w-full flex items-center justify-between px-5 pb-2
                        border-b border-slate-100">
                <p class="text-sm font-bold text-slate-700">Filtros</p>
                <div class="flex items-center gap-3">
                    @if($activeCount > 0)
                        <button
                            wire:click="clearAllFilters"
                            @click="open = false"
                            class="text-xs font-semibold text-zertix-primary-dark">
                            Limpiar todo
                        </button>
                    @endif
                    <button @click="open = false"
                            class="text-slate-400 hover:text-slate-600">
                        <x-heroicon-s-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 space-y-5 max-h-[70vh] overflow-y-auto pb-safe">
            {{ $slot }}
        </div>

        <div class="px-5 pb-6 pt-3 border-t border-slate-100">
            <button
                @click="open = false"
                class="w-full py-3 rounded-xl bg-zertix-primary text-white text-sm font-bold
                       hover:opacity-90 active:scale-[0.98] transition-all duration-200">
                Aplicar filtros
            </button>
        </div>
    </div>

</div>
