@props(['actions' => []])
<div x-data="{ 
        selectedIds: [], 
        open: false,
        init() {
            document.addEventListener('table-selection-changed', (e) => {
                this.selectedIds = e.detail.ids;
            });
        },
        clearSelection() {
            if (window.clearTableSelection) {
                window.clearTableSelection();
            }
            this.open = false;
        }
    }" 
    x-show="selectedIds.length > 0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    class=" flex items-center w-full sm:w-auto"
    style="display: none;"
>
    {{-- Contenedor principal SIN overflow-hidden --}}
    <div class="relative flex items-center shadow-sm border-2 border-zertix-primary-600 rounded-lg w-full sm:w-auto bg-white">
        
        {{-- Botón Principal: Redondeado a la izquierda --}}
        <button @click="open = !open" type="button" 
            class="flex-1 sm:flex-none flex items-center justify-center sm:justify-start px-3 py-1.5 bg-zertix-primary-50 hover:bg-zertix-primary-100 transition-colors border-r border-zertix-primary-200 whitespace-nowrap rounded-l-[5px]">
            <span x-text="selectedIds.length" class="flex items-center justify-center min-w-[20px] h-5 px-1 bg-zertix-primary-700 text-white rounded-full text-[10px] font-bold mr-2"></span>
            <span class="text-xs font-bold text-zertix-primary-700">Acciones Masivas</span>
            <x-heroicon-s-chevron-down class="ml-2 w-4 h-4 text-zertix-primary-600" />
        </button>

        {{-- Botón de Limpiar: Redondeado a la derecha --}}
        <button @click="clearSelection()" type="button"
            title="Limpiar selección"
            class="p-1.5 px-3 sm:px-1.5 bg-white hover:bg-red-50 text-red-500 transition-colors group rounded-r-[5px]">
            <x-heroicon-s-x-mark class="w-4 h-4 group-hover:scale-110 transition-transform" />
        </button>

        {{-- Dropdown: Ahora con Z-Index alto y posicionado correctamente --}}
        <div x-show="open" 
            x-cloak
            @click.away="open = false" 
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            {{-- El z-[100] y la posición absoluta ahora funcionarán --}}
            class="absolute left-0 top-full mt-2 w-full sm:w-56 rounded-xl shadow-2xl bg-white ring-1 ring-black ring-opacity-5 z-[100] p-2 border border-gray-100">
            
            <div class="px-3 py-2 border-b border-gray-100 mb-1">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Ejecutar en selección</span>
            </div>

            <div class="max-h-64 overflow-y-auto">
                @foreach($actions as $action)
                    <button type="button" 
                        @click="open = false; $dispatch('execute-bulk-action', { 
                            action: '{{ $action['id'] }}', 
                            label: '{{ $action['label'] }}',
                            type: '{{ $action['type'] ?? 'text' }}',
                            ids: selectedIds,
                            requiresValue: {{ (isset($action['options']) || (isset($action['type']) && $action['type'] !== 'none')) ? 'true' : 'false' }},
                            options: @js($action['options'] ?? [])
                        })"
                        class="flex items-center w-full px-3 py-2 text-sm text-gray-600 hover:bg-zertix-primary-50 hover:text-zertix-primary-700 rounded-lg transition group">
                        @if(isset($action['icon']))
                            <x-dynamic-component :component="$action['icon']" class="w-4 h-4 mr-3 text-gray-400 group-hover:text-zertix-primary-500" />
                        @endif
                        <span class="truncate">{{ $action['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>