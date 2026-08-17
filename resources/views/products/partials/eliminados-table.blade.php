<x-data-table 
    :items="$items" 
    :headers="['sku' => 'SKU', 'name' => 'Producto', 'category' => 'Categoría', 'deleted_at' => 'Eliminado']"
    :visibleColumns="['sku', 'name', 'category', 'deleted_at']">

    @forelse($items as $product)
        <tr class="hover:bg-gray-50 transition duration-150 group">
            {{-- SKU --}}
            <td class="px-6 py-4">
                <span class="text-xs font-mono font-bold text-gray-400 px-2 py-1 bg-gray-100 rounded">
                    {{ $product->sku }}
                </span>
            </td>

            {{-- Info del Producto --}}
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center text-red-600 mr-3 overflow-hidden border border-red-100">
                        @if($product->image_path)
                            <img src="{{ $product->image_url }}" class="w-full h-full object-cover opacity-50 grayscale">
                        @else
                            <x-heroicon-s-cube class="w-5 h-5"/>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900">{{ $product->name }}</div>
                        <div class="text-xs text-gray-500">P. Venta: {{ config('regional.currency_symbol') }}{{ number_format($product->price_with_tax, 2) }}</div>
                    </div>
                </div>
            </td>

            {{-- Categoría --}}
            <td class="px-6 py-4 text-sm text-gray-600">
                <span class="px-2 py-1 bg-gray-50 border border-gray-100 rounded text-xs">
                    {{ $product->category->name ?? 'Sin categoría' }}
                </span>
            </td>

            {{-- Fecha de eliminación --}}
            <td class="px-6 py-4 text-sm text-gray-500 italic">
                {{ $product->deleted_at->diffForHumans() }}
            </td>

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Restaurar --}}
                    <form action="{{ route('inventory.products.restore', $product->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" title="Restaurar Producto"
                                class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-transparent hover:border-emerald-100">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    {{-- Borrado Definitivo --}}
                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $product->id }}')"
                            title="Eliminar permanentemente del servidor"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-archive-box-x-mark class="w-12 h-12 text-gray-200 mb-3"/>
                    <p class="text-gray-500 font-medium">La papelera de productos está vacía.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@foreach($items as $product)
    <x-ui.confirm-deletion-modal 
        :id="$product->id"
        :title="'¿Eliminar Producto Permanentemente?'"
        :itemName="$product->name"
        :route="route('inventory.products.borrarDefinitivo', $product->id)"
        :description="'Estás a punto de borrar definitivamente el producto <strong>' . $product->name . '</strong>. Esta acción eliminará su historial de stock y precios.'"
    >
        <div>
            <p class="">
                <strong>Aviso Crítico:</strong> Esta operación no se puede deshacer. El producto desaparecerá de todos los reportes históricos.
            </p>
        </div>
    </x-ui.confirm-deletion-modal>
@endforeach