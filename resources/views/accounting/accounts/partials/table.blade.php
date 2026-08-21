<x-data-table 
    :items="$items" 
    :headers="$allColumns" 
    :visibleColumns="$visibleColumns" 
    :bulkActions="false"
>
    @forelse($items as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            {{-- Código --}}
            @if(in_array('code', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4 text-sm font-mono font-bold text-indigo-600">
                    {{ $item->code }}
                </td>
            @endif

            {{-- Nombre (Con Indentación por nivel) --}}
            @if(in_array('name', $visibleColumns))
                @php
                    $paddingLevels = [
                        1 => 'pl-0',
                        2 => 'pl-4',
                        3 => 'pl-8',
                        4 => 'pl-12',
                        5 => 'pl-16',
                    ];
                    $paddingClass = $paddingLevels[$item->level] ?? 'pl-20';
                @endphp

                <td class="px-6 py-4 text-sm font-medium">
                    <div class="flex items-center {{ $paddingClass }}">
                        @if($item->level > 1)
                            <span class="text-gray-400 mr-2 font-mono">└─</span>
                        @endif
                        {{ $item->name }}
                    </div>
                </td>
            @endif

            {{-- Tipo --}}
            @if(in_array('type', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <x-ui.badge :variant="match($item->type) {
                        \App\Models\Accounting\AccountingAccount::TYPE_ASSET => 'success',
                        \App\Models\Accounting\AccountingAccount::TYPE_LIABILITY => 'error',
                        \App\Models\Accounting\AccountingAccount::TYPE_EQUITY => 'info',
                        \App\Models\Accounting\AccountingAccount::TYPE_REVENUE => 'info',
                        \App\Models\Accounting\AccountingAccount::TYPE_COST => 'warning',
                        \App\Models\Accounting\AccountingAccount::TYPE_EXPENSE => 'warning',
                        default => 'slate',
                    }" size="sm" :dot="false">
                        {{ \App\Models\Accounting\AccountingAccount::getTypes()[$item->type] ?? $item->type }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Padre --}}
            @if(in_array('parent_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $item->parent ? $item->parent->code . ' - ' . $item->parent->name : 'Raíz' }}
                </td>
            @endif

            {{-- Nivel --}}
            @if(in_array('level', $visibleColumns))
                <td class="px-6 py-4 text-sm text-center">
                    <span class="text-gray-400 font-bold">N{{ $item->level }}</span>
                </td>
            @endif

            {{-- Posteable (is_selectable) --}}
            @if(in_array('is_selectable', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    <x-heroicon-o-check-circle @class(['w-5 h-5 mx-auto', 'text-emerald-500' => $item->is_selectable, 'text-gray-200' => !$item->is_selectable]) />
                </td>
            @endif
            
            {{-- Estado --}}
            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4">
                    <x-ui.badge :variant="$item->is_active ? 'success' : 'error'" size="sm" :dot="false">
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
                    {{ $item->updated_at?->diffForHumans() ?? 'Sin cambios' }}
                </td>
            @endif
            
            {{-- Acciones --}}
            <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-3">
                    
                    <button @click="$dispatch('open-modal', 'view-account-{{ $item->id }}')" 
                            class="text-gray-400 hover:text-blue-600 transition p-1"
                            title="Ver detalles completos">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>
                    <button @click="$dispatch('open-modal', 'edit-account-{{ $item->id }}')" 
                            class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50 transition-colors">
                        <x-heroicon-s-pencil class="w-5 h-5" />
                    </button>

                    <button @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                        class="p-1 rounded text-red-600 hover:text-red-900 hover:bg-red-50">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="20" class="text-center py-12">
                <p class="text-gray-500">No hay cuentas contables registradas.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('accounting.accounts.partials.modals')