<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $movement)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <span class="block font-medium text-gray-700">{{ $movement->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px]">{{ $movement->created_at->format('h:i A') }}</span>
                </td>
            @endif

            @if(in_array('product', $visibleColumns))
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {{ $movement->product->name ?? 'N/A' }}
                </td>
            @endif

            @if(in_array('warehouse', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $movement->warehouse->name ?? 'N/A' }}
                </td>
            @endif

            @if(in_array('type', $visibleColumns))
                <td class="px-6 py-4">
                    <x-ui.badge :variant="match($movement->type) {
                        'input' => 'success',
                        'output' => 'error',
                        'transfer' => 'info',
                        default => 'slate',
                    }" size="sm" :dot="false">
                        {{ $types[$movement->type] ?? $movement->type }}
                    </x-ui.badge>
                </td>
            @endif

            @if(in_array('toWarehouse', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    @if($movement->type === 'transfer' && $movement->to_warehouse_id)
                        <div class="flex items-center text-blue-700 font-medium">
                            <x-heroicon-s-arrow-right-circle class="w-4 h-4 mr-1.5 opacity-70" />
                            {{ $movement->toWarehouse->name ?? 'N/A' }}
                        </div>
                    @elseif($movement->reference_type === 'App\Models\Inventory\InventoryMovement')
                        {{-- Para los movimientos "espejo" de entrada, mostramos que viene de otro lado --}}
                        <span class="text-gray-400 italic text-xs">Origen de transferencia</span>
                    @else
                        <span class="text-gray-300 italic text-[10px]">No aplica</span>
                    @endif
                </td>
            @endif

            @if(in_array('quantity', $visibleColumns))
                <td class="px-6 py-4 text-sm font-bold">
                    <div class="flex items-center {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                        @if($movement->quantity > 0)
                            <x-heroicon-s-arrow-trending-up class="w-4 h-4 mr-1" />
                        @else
                            <x-heroicon-s-arrow-trending-down class="w-4 h-4 mr-1" />
                        @endif
                        {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                    </div>
                </td>
            @endif

            {{-- Reemplaza o añade dentro del bucle forelse --}}

            @if(in_array('balance', $visibleColumns))
                <td class="px-6 py-4 whitespace-nowrap text-xs">
                    <div class="flex flex-col border-l-2 border-gray-100 pl-2">
                        <span class="text-gray-400">Previo: {{ number_format($movement->previous_stock, 2) }}</span>
                        <span class="font-bold text-gray-800">Final: {{ number_format($movement->current_stock, 2) }}</span>
                    </div>
                </td>
            @endif

            @if(in_array('user', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-600">
                    {{ $movement->user->name ?? 'Sistema' }}
                </td>
            @endif

            <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-3">
                    {{-- BOTÓN VER DETALLES --}}
                    <button @click="$dispatch('open-modal', 'view-movement-{{ $movement->id }}')" 
                            class="bg-gray-50 text-gray-500 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm"
                            title="Ver auditoría completa">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="text-center py-12 text-gray-400 italic">
                No se encontraron movimientos con los filtros seleccionados.
            </td>
        </tr>
    @endforelse
</x-data-table>