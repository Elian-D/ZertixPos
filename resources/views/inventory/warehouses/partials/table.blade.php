<x-data-table 
    :items="$warehouses" 
    :headers="$allColumns" 
    :visibleColumns="$visibleColumns" 
    :bulkActions="false"
>
    @forelse($warehouses as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">

            {{-- Nombre --}}
            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {{ $item->name }}
                </td>
            @endif

            {{-- Tipo --}}
            @if(in_array('types', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <span class="flex items-center gap-1">
                        @if($item->type === \App\Models\Inventory\Warehouse::TYPE_MOBILE)
                            <x-heroicon-s-truck class="w-4 h-4 text-amber-500" />
                        @elseif($item->type === \App\Models\Inventory\Warehouse::TYPE_POS)
                            <x-heroicon-s-shopping-cart class="w-4 h-4 text-blue-500" />
                        @else
                            <x-heroicon-s-building-office class="w-4 h-4 text-gray-500" />
                        @endif
                        {{ $item->type_label }}
                    </span>
                </td>
            @endif

            {{-- Cuenta Contable (Nueva Columna) --}}
            @if(in_array('accounting_account_id', $visibleColumns))
                <td class="px-6 py-4">
                    @if($item->accountingAccount)
                        <div class="flex flex-col">
                            <span class="text-xs font-mono font-bold text-zertix-primary-600 bg-zertix-primary-50 px-2 py-0.5 rounded w-fit">
                                {{ $item->accountingAccount->code }}
                            </span>
                            <span class="text-[10px] text-gray-400 truncate max-w-[150px]" title="{{ $item->accountingAccount->name }}">
                                {{ $item->accountingAccount->name }}
                            </span>
                        </div>
                    @else
                        <span class="text-xs text-amber-500 italic flex items-center gap-1">
                            <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                            Sin vincular
                        </span>
                    @endif
                </td>
            @endif

            {{-- Ubicación --}}
            @if(in_array('address', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">
                    {{ $item->address ?? '—' }}
                </td>
            @endif

            {{-- Descripción --}}
            @if(in_array('description', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-400 italic">
                    {{ $item->description ?? '—' }}
                </td>
            @endif
            
            {{-- Estado --}}
            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4">
                    <x-ui.badge :variant="$item->is_active ? 'success' : 'error'" size="sm">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Fechas --}}
            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->created_at->format('d/m/Y') }}
                </td>
            @endif

            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                    <form action="{{ route('inventory.warehouses.toggle', $item) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                            title="Cambiar estado"
                            class="whitespace-nowrap text-xs px-2 py-1 rounded border {{ $item->is_active ? 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100' : 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' }}">
                            {{ $item->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>

                    {{-- Ver Detalles --}}
                    <button @click="$dispatch('open-modal', 'view-warehouse-{{ $item->id }}')"
                            class="text-gray-400 hover:text-zertix-primary-600 p-1 rounded hover:bg-gray-100 transition-colors"
                            title="Ver detalles completos">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>

                    <button @click="$dispatch('open-modal', 'edit-warehouse-{{ $item->id }}')"
                            class="text-zertix-primary-600 hover:text-zertix-primary-900 p-1 rounded hover:bg-zertix-primary-50 transition-colors">
                        <x-heroicon-s-pencil class="w-5 h-5" />
                    </button>

                    <button
                        @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                        class="p-1 rounded text-red-600 hover:text-red-900 hover:bg-red-50"
                        title="Eliminar">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="11" class="text-center py-12">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-building-office class="w-12 h-12 text-gray-200 mb-2" />
                    <p class="text-gray-500 font-medium">No hay almacenes registrados con estos filtros</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>
@include('inventory.warehouses.partials.modals')