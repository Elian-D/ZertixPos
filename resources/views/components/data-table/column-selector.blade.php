@props([
    'allColumns' => [],
    'visibleColumns' => [],
    'defaultDesktop' => [], 
    'defaultMobile' => [],
    'formId' => ''
])

<div x-data="{ open: false }" 
     id="column-selector-container"
     data-default-desktop='@json($defaultDesktop)'
     data-default-mobile='@json($defaultMobile)'
     class="relative inline-block text-left w-full sm:w-auto">
    
    <button @click="open = !open" type="button" 
        class="inline-flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition shadow-sm">
        <x-heroicon-s-view-columns class="w-4 h-4 mr-2" />
        Columnas
        <x-heroicon-s-chevron-down class="ml-2 w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    {{-- Backdrop oscuro solo en móvil --}}
    <div x-show="open" 
         class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[90] md:hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click="open = false">
    </div>

    {{-- Panel de Columnas: Centrado en móvil, dropdown en desktop --}}
    <div x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
        {{-- Móvil: centrado vertical y horizontal | Desktop: dropdown normal --}}
        class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[calc(100%-2rem)] max-w-sm
               md:fixed md:inset-auto md:left-auto md:top-auto md:translate-x-0 md:translate-y-0 md:absolute md:right-0 md:top-full md:mt-2 md:w-72
               rounded-2xl shadow-2xl bg-white ring-1 ring-black/5 z-[100] border border-gray-100 overflow-hidden flex flex-col max-h-[85vh] md:max-h-[500px]">
        
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-b from-gray-50 to-white flex justify-between items-center flex-shrink-0">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Configurar Columnas</h3>
                <p class="text-[10px] text-gray-500 uppercase tracking-tight">Personaliza tu vista</p>
            </div>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition">
                <x-heroicon-s-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Botón Restablecer --}}
        <div class="px-4 py-2 border-b border-gray-50 bg-white flex-shrink-0">
            <button type="button" 
                onclick="window.resetTableColumns()"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 rounded-lg transition-all">
                <x-heroicon-s-arrow-path class="w-4 h-4" />
                Restablecer por defecto
            </button>
        </div>

        {{-- Lista de columnas (scrollable) --}}
        <div class="overflow-y-auto overscroll-contain flex-1 custom-scrollbar">
            <div class="p-3 space-y-0.5">
                @foreach($allColumns as $key => $label)
                    <label class="flex items-center px-3 py-2.5 hover:bg-indigo-50 rounded-lg cursor-pointer group transition-all">
                        <input type="checkbox" 
                            name="columns[]" 
                            value="{{ $key }}" 
                            data-column-key="{{ $key }}"
                            @if($formId) form="{{ $formId }}" @endif
                            @checked(in_array($key, $visibleColumns))
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-3 flex-shrink-0">
                        <span class="text-sm text-gray-700 group-hover:text-indigo-700 font-medium transition-colors">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-2.5 border-t border-gray-100 bg-gradient-to-b from-white to-gray-50 flex justify-center items-center flex-shrink-0">
            <span class="text-[10px] text-gray-400 font-medium">
                Selecciona las columnas a mostrar
            </span>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>