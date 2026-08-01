{{-- MODAL: Selector de cliente con buscador (desktop; en móvil el equivalente es el
     bottom sheet de pos-workspace/mobile.blade.php). Comparte filteredClients/clientSearch
     con el móvil, y la búsqueda/lista con partials/client-search-input y
     partials/client-results-list (Fase 7.10). --}}
<x-modal name="pos-client-modal" maxWidth="md">
    <div class="p-5 bg-white flex flex-col" style="max-height: 80vh;">
        <div class="flex items-center justify-between mb-3 shrink-0">
            <h2 class="font-bold text-gray-900">Seleccionar Cliente</h2>
            <button type="button" @click="$dispatch('close-modal', 'pos-client-modal')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                <x-heroicon-s-x-mark class="w-4 h-4 text-gray-500" />
            </button>
        </div>

        <div class="mb-3 shrink-0">
            @include('livewire.sales.pos.pages.pos-workspace.partials.client-search-input', ['autofocus' => true])
        </div>

        <div class="overflow-y-auto space-y-1 -mx-1 px-1">
            @include('livewire.sales.pos.pages.pos-workspace.partials.client-results-list', ['closeAction' => "\$dispatch('close-modal', 'pos-client-modal')"])
        </div>
    </div>
</x-modal>
