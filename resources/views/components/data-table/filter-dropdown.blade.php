@props(['label' => 'Filtros'])

<div x-data="{ open: false }" class="relative w-full md:w-auto text-left">
    <button @click="open = !open" type="button"
        class="inline-flex justify-center items-center w-full md:w-auto px-4 py-2 border rounded-lg bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition">
        <x-heroicon-s-funnel class="mr-2 h-4 w-4 text-gray-400" />
        {{ $label }}
        <x-heroicon-s-chevron-down class="ml-2 -mr-1 h-4 w-4 text-gray-400 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    {{-- Backdrop oscuro solo en móvil --}}
    <div x-show="open" 
         class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[90] md:hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click="open = false">
    </div>

    {{-- Panel de Filtros: Centrado en móvil, dropdown en desktop --}}
    <div x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
        {{-- Móvil: centrado vertical y horizontal | Desktop: dropdown normal --}}
        class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[calc(100%-2rem)] max-w-md
               md:fixed md:inset-auto md:left-auto md:top-auto md:translate-x-0 md:translate-y-0 md:absolute md:right-0 md:top-full md:mt-2 md:w-80
               rounded-2xl shadow-2xl bg-white ring-1 ring-black/5 z-[100] border border-gray-100 overflow-hidden flex flex-col max-h-[85vh] md:max-h-[550px]">
        
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-b from-gray-50 to-white flex justify-between items-center flex-shrink-0">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Filtros Avanzados</h3>
                <p class="text-[10px] text-gray-500 uppercase tracking-tight">Personaliza tu búsqueda</p>
            </div>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition">
                <x-heroicon-s-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Contenido scrollable --}}
        <div class="overflow-y-auto overscroll-contain flex-1 custom-scrollbar">
            <div class="p-4 space-y-1">
                {{ $slot }}
            </div>
        </div>

        {{-- Footer adaptativo --}}
        <div class="px-4 py-2.5 border-t border-gray-100 bg-gradient-to-b from-white to-gray-50 flex justify-center items-center flex-shrink-0">
            <span class="text-[10px] text-gray-400 font-medium">
                <span class="md:hidden">Desliza para cerrar</span>
                <span class="hidden md:inline">Scroll para más opciones</span>
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