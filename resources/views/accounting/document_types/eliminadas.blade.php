<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">
        <x-ui.toasts />

        <x-page-toolbar title="Papelera de Documentos" subtitle="Tipos de documentos eliminados del sistema">
            <x-slot name="actions">
                <a href="{{ route('configuration.document_types.index') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <x-heroicon-s-arrow-left class="w-4 h-4 mr-2" />
                    Volver al Listado
                </a>
            </x-slot>
        </x-page-toolbar>

        <div class="bg-white shadow-xl rounded-xl border border-gray-100 mt-6 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                <form id="document-types-trash-filters" class="flex gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-s-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </span>
                        <input type="text" name="search" 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white placeholder-gray-500 focus:ring-indigo-500 sm:text-sm shadow-sm" 
                                placeholder="Buscar documento por código o nombre...">
                    </div>
                </form>
            </div>

            <div class="p-6">
                @include('accounting.document_types.partials.eliminados-table', ['items' => $items])
            </div>
        </div>
    </div>
</x-app-layout>