@props([
    'items', 
    'headers', 
    'visibleColumns' => [],
    'bulkActions' => false
])

<div class="flex flex-col w-full">
    <div id="bulk-actions-container"></div>

    <div class="w-full overflow-x-auto border border-gray-200 rounded-lg custom-scrollbar">
        <table class="w-full min-w-full divide-y divide-gray-200 shadow-sm table-auto">
            <thead class="bg-gray-50 hidden md:table-header-group">
                <tr>
                    @if($bulkActions)
                        <th scope="col" class="px-4 py-3 text-center w-10">
                            <input type="checkbox" 
                                   id="select-all-main" 
                                   class="rounded border-gray-300 text-zertix-primary-600 focus:ring-zertix-primary-500 cursor-pointer">
                        </th>
                    @endif
                    
                    @foreach($headers as $key => $label)
                        @if(in_array($key, $visibleColumns))
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $label }}
                            </th>
                        @endif
                    @endforeach
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(method_exists($items, 'links'))
        <div class="mt-4 pagination">
            {{ $items->links() }}
        </div>
    @endif
</div>

<style>
    /* Estilo para la barra de scroll lateral en móviles */
    .custom-scrollbar::-webkit-scrollbar { height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>