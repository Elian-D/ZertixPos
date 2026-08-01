<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            @if(in_array('id', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-gray-700">{{ $item->id }}</td>
            @endif

            @if(in_array('name', $visibleColumns))
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                    <div class="flex items-center">
                        @if($item->is_mobile)
                            <x-heroicon-s-device-phone-mobile class="w-4 h-4 mr-2 text-indigo-500" title="Dispositivo Móvil" />
                        @else
                            <x-heroicon-s-computer-desktop class="w-4 h-4 mr-2 text-gray-400" title="Terminal Fija" />
                        @endif
                        {{ $item->name }}
                    </div>
                </td>
            @endif

            @if(in_array('warehouse_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <span class="whitespace-nowrap px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium">
                        {{ $item->warehouse->name ?? 'No asignado' }}
                    </span>
                </td>
            @endif

            @if(in_array('cash_account_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex flex-col">
                        <span class="text-xs font-mono text-gray-400">{{ $item->cashAccount->code ?? '' }}</span>
                        <span>{{ $item->cashAccount->name ?? '—' }}</span>
                    </div>
                </td>
            @endif

            @if(in_array('default_ncf_type_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">
                    @if(!general_config()?->esModoFiscal())
                        <span class="text-gray-300 italic text-xs">No Fiscal</span>
                    @else
                        {{ $item->defaultNcfType->name ?? 'Global' }}
                    @endif
                </td>
            @endif

            @if(in_array('default_client_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $item->defaultClient->name ?? pos_config('default_walkin_customer_name') ?? 'Consumidor Final' }}
                    @if(is_null($item->default_client_id))
                        <span class="text-[9px] text-blue-500 font-bold ml-1">(HEREDADO)</span>
                    @endif
                </td>
            @endif

            @if(in_array('is_mobile', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    <x-heroicon-s-check-circle class="w-5 h-5 mx-auto {{ $item->is_mobile ? 'text-green-500' : 'text-gray-200' }}" />
                </td>
            @endif

            {{-- Columna Formato de Impresión --}}
            @if(in_array('printer_format', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    @php $isInherited = is_null($item->printer_format); @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $isInherited ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-gray-100 text-gray-800 border-gray-200' }} border">
                        <x-heroicon-s-printer class="w-3 h-3 mr-1" />
                        {{ $item->printer_format ?? pos_config('receipt_size') }}
                        @if($isInherited)
                            <span class="ml-1 text-[9px] opacity-70">(H)</span>
                        @endif
                    </span>
                </td>
            @endif

            @if(in_array('is_active', $visibleColumns))
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-bold {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
            @endif

            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $item->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- Columna de Acciones --}}
            <td class="px-6 py-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                    @can('view pos terminals')
                        <button @click="$dispatch('open-modal', 'view-terminal-{{ $item->id }}')" 
                                class="bg-gray-100 text-gray-600 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm">
                            <x-heroicon-s-eye class="w-4 h-4" />
                        </button>
                    @endcan

                    @can('edit pos terminals')
                        <a href="{{ route('sales.pos.terminals.edit', $item) }}" 
                           class="text-indigo-600 hover:text-indigo-900 p-2 rounded-full hover:bg-indigo-50 transition">
                            <x-heroicon-s-pencil class="w-4 h-4" />
                        </a>
                    @endcan
                    
                    @can('delete pos terminals')
                        <button @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')" 
                                class="text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50 transition">
                            <x-heroicon-s-trash class="w-4 h-4" />
                        </button>
                    @endcan
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-10 text-gray-500 italic">
                No se encontraron terminales configuradas.
            </td>
        </tr>
    @endforelse
</x-data-table>

{{-- Cargamos Modales de Vista Previa y Confirmación --}}
@include('sales.pos.terminals.partials.modals')