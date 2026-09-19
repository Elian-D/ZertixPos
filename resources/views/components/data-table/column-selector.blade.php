{{--
    resources/views/components/data-table/column-selector.blade.php
    ----------------------------------------------------------------
    Los checkboxes son Alpine reactivo (:checked), no @checked() de PHP —
    cuando Livewire hace morfing parcial del DOM tras resetColumns()/toggleColumn(),
    PHP no re-corre el template para esos atributos. Alpine leyendo
    $wire.visibleColumns en runtime sí queda siempre en sync.

    Device detection: Alpine detecta el viewport en init() y llama
    $wire.resetColumns(isMobile) si el servidor mandó defaults de desktop
    estando en mobile (carga inicial) o si el usuario rota/redimensiona.

    PROPS:
      columns          — array resuelto ['key' => 'Label'] (viene de base-table)
      visibleColumns    — array de columnas visibles actuales
      desktopDefaults   — columnas por defecto en desktop, para el guard de "Restablecer"
      mobileDefaults    — columnas por defecto en mobile
--}}

@props([
    'columns'         => [],
    'visibleColumns'  => [],
    'desktopDefaults' => [],
    'mobileDefaults'  => [],
])

@php
    $desktopJson = json_encode(array_values($desktopDefaults));
    $mobileJson  = json_encode(array_values($mobileDefaults));
@endphp

<div
    x-data="{
        open: false,
        isMobile: window.innerWidth < 768,

        get visible() {
            return $wire.visibleColumns ?? [];
        },

        get currentDefaults() {
            return this.isMobile ? {{ $mobileJson }} : {{ $desktopJson }};
        },

        get isCustomized() {
            const vis  = this.visible;
            const defs = this.currentDefaults;
            return vis.length !== defs.length
                || defs.some(d => !vis.includes(d))
                || vis.some(v => !defs.includes(v));
        },

        isVisible(key) {
            return this.visible.includes(key);
        },

        isLastOne(key) {
            return this.isVisible(key) && this.visible.length === 1;
        },

        init() {
            const mq = window.matchMedia('(max-width: 767px)');
            mq.addEventListener('change', (e) => {
                const wasMobile = this.isMobile;
                this.isMobile = e.matches;
                if (!this.isMobile) this.open = false;

                if (wasMobile !== this.isMobile) {
                    $wire.resetColumns(this.isMobile);
                }
            });

            if (this.isMobile) {
                const serverCols  = JSON.stringify([...(this.visible)].sort());
                const desktopCols = JSON.stringify([...{{ $desktopJson }}].sort());
                const mobileCols  = JSON.stringify([...{{ $mobileJson }}].sort());

                if (serverCols === desktopCols && serverCols !== mobileCols) {
                    $wire.resetColumns(true);
                }
            }
        }
    }"
    class="relative flex-shrink-0"
>

    <button
        @click="open = !open"
        class="flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-semibold
               transition-all duration-200 focus:outline-none"
        :class="isCustomized
            ? 'border-zertix-primary/40 bg-zertix-primary/8 text-zertix-primary-dark'
            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
    >
        <x-heroicon-o-view-columns class="w-4 h-4" />
        <span class="hidden sm:block">Columnas</span>

        <span
            x-show="isCustomized"
            x-text="visible.length"
            class="flex items-center justify-center w-5 h-5 rounded-full
                   bg-zertix-primary text-white text-[10px] font-black leading-none flex-shrink-0"
        ></span>

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
        class="absolute right-0 top-full mt-2 z-50 w-60 rounded-2xl border shadow-2xl
               bg-white border-slate-100"
    >
        <div class="flex items-center justify-between px-4 py-3
                    border-b border-slate-100">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Columnas visibles
            </p>
            <button
                @click="$wire.resetColumns(isMobile)"
                class="text-[11px] font-semibold transition-colors"
                :class="isCustomized
                    ? 'text-zertix-primary-dark hover:text-zertix-primary-dark/70 cursor-pointer'
                    : 'text-slate-300 cursor-default pointer-events-none'"
            >
                Restablecer
            </button>
        </div>

        <div class="p-3 space-y-0.5 max-h-64 overflow-y-auto custom-scroll">
            @foreach($columns as $key => $colLabel)
                <label
                    class="flex items-center gap-3 px-2 py-2 rounded-lg transition-colors"
                    :class="isLastOne('{{ $key }}')
                        ? 'opacity-50 cursor-not-allowed'
                        : 'cursor-pointer hover:bg-slate-50 group'"
                >
                    <input
                        type="checkbox"
                        :checked="isVisible('{{ $key }}')"
                        :disabled="isLastOne('{{ $key }}')"
                        @change="!isLastOne('{{ $key }}') && $wire.toggleColumn('{{ $key }}')"
                        class="w-4 h-4 rounded border-slate-300
                               text-zertix-primary focus:ring-zertix-primary focus:ring-offset-0
                               transition-colors"
                        :class="isLastOne('{{ $key }}') ? 'cursor-not-allowed' : 'cursor-pointer'"
                    />
                    <span
                        class="text-sm text-slate-700 transition-colors"
                        :class="!isLastOne('{{ $key }}') ? 'group-hover:text-zertix-primary-dark' : ''"
                    >
                        {{ $colLabel }}
                    </span>
                    <span
                        x-show="isLastOne('{{ $key }}')"
                        class="ml-auto text-[10px] text-slate-400"
                    >mín.</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- MOBILE OVERLAY --}}
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

    {{-- MOBILE DRAWER --}}
    <div
        x-show="open && isMobile"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-50 rounded-t-3xl shadow-2xl
               bg-white border-t border-slate-100
               md:hidden"
    >
        <div class="flex flex-col items-center pt-3">
            <div class="w-10 h-1 rounded-full bg-slate-200 mb-3"></div>
            <div class="w-full flex items-center justify-between px-5 pb-3
                        border-b border-slate-100">
                <p class="text-sm font-bold text-slate-700">Columnas visibles</p>
                <div class="flex items-center gap-3">
                    <button
                        @click="$wire.resetColumns(true); open = false"
                        class="text-xs font-semibold transition-colors"
                        :class="isCustomized
                            ? 'text-zertix-primary-dark'
                            : 'text-slate-300 pointer-events-none'"
                    >
                        Restablecer
                    </button>
                    <button @click="open = false" class="text-slate-400">
                        <x-heroicon-s-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 py-3 space-y-0.5 max-h-[60vh] overflow-y-auto custom-scroll">
            @foreach($columns as $key => $colLabel)
                <label
                    class="flex items-center gap-3 px-2 py-3 rounded-xl transition-colors"
                    :class="isLastOne('{{ $key }}')
                        ? 'opacity-50 cursor-not-allowed'
                        : 'cursor-pointer hover:bg-slate-50 group'"
                >
                    <input
                        type="checkbox"
                        :checked="isVisible('{{ $key }}')"
                        :disabled="isLastOne('{{ $key }}')"
                        @change="!isLastOne('{{ $key }}') && $wire.toggleColumn('{{ $key }}')"
                        class="w-4 h-4 rounded border-slate-300
                               text-zertix-primary focus:ring-zertix-primary"
                        :class="isLastOne('{{ $key }}') ? 'cursor-not-allowed' : 'cursor-pointer'"
                    />
                    <span
                        class="text-sm text-slate-700 transition-colors"
                        :class="!isLastOne('{{ $key }}') ? 'group-hover:text-zertix-primary-dark' : ''"
                    >
                        {{ $colLabel }}
                    </span>
                </label>
            @endforeach
        </div>

        <div class="px-5 pb-6 pt-3 border-t border-slate-100">
            <button
                @click="open = false"
                class="w-full py-3 rounded-xl bg-zertix-primary text-white text-sm font-bold
                       hover:opacity-90 active:scale-[0.98] transition-all">
                Listo
            </button>
        </div>
    </div>

</div>
