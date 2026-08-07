<x-data-table :items="$clients" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="$bulkActions" >
    @forelse($clients as $client)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">

<tr class="hover:bg-gray-50 transition border-b border-gray-100">
            @if($bulkActions)
                <td class="px-4 py-4 text-center">
                    <input type="checkbox" value="{{ $client->id }}" class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                </td>
            @endif

            @if(in_array('id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500">#{{ $client->id }}</td>
            @endif

            @if(in_array('name', $visibleColumns))
                <td class="px-6 py-4 text-sm ">
                    @if($client->commercial_name)
                        <span class="text-gray-900">{{ $client->commercial_name }}</span>
                    @else
                        <span class="text-gray-900">{{ $client->name }}</span>
                    @endif
                </td>
            @endif

            @if(in_array('tax_identifier_types', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-700">
                    {{ $client->tax_label ?? '—' }}
                </td>
            @endif

            @if(in_array('tax_id', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-700">
                    {{ $client->tax_id ?? '—' }}
                </td>
            @endif

            {{-- Columnas Financieras --}}
            @if(in_array('balance', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <span class="font-semibold {{ $client->balance > 0 ? 'text-red-600' : 'text-green-600' }}">{{ config('regional.currency_symbol') }}{{ number_format($client->balance, 2) }}</span>
                </td>
            @endif

            @if(in_array('credit_limit', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    @if($client->credit_limit > 0)
                        <div class="flex items-center gap-2">
                            <span class="text-gray-700 font-medium">{{ config('regional.currency_symbol') }}{{ number_format($client->credit_limit, 2) }}</span>
                            
                            @if($client->balance > $client->credit_limit)
                                <span class="whitespace-nowrap inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 animate-pulse ring-1 ring-red-400">
                                    EXCEDIDO
                                </span>
                            @endif
                        </div>
                    @else
                        <span class="whitespace-nowrap text-[10px] font-bold text-gray-400 uppercase tracking-tighter bg-gray-100 px-2 py-1 rounded">
                            Solo Contado
                        </span>
                    @endif
                </td>
            @endif

            @if(in_array('accounting_account', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    {{ $client->accountingAccount->code ?? 'General' }}
                </td>
            @endif

            @if(in_array('type', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-700">
                    {{ $client->type === 'company' ? 'Empresa' : 'Individual' }}
                </td>
            @endif

            @if(in_array('email', $visibleColumns))
                <td class="px-6 py-4 text-sm text-blue-600">
                    {{ $client->email ?? '—' }}
                </td>
            @endif

            @if(in_array('phone', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $client->phone ?? '—' }}
                </td>
            @endif

            @if(in_array('state', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $client->provincia->name ?? '—' }}</td>
            @endif

            @if(in_array('city', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-700">{{ $client->municipio->name ?? '—' }}</td>
            @endif


            @if(in_array('address', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">{{ $client->address ?? '—' }}</td>
            @endif

            @if(in_array('estado_cliente', $visibleColumns))
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded font-bold {{ $client->estadoCliente->clase_fondo }} {{ $client->estadoCliente->clase_texto }}">
                        {{ $client->estadoCliente->nombre }}
                    </span>
                </td>
            @endif

            @if(in_array('created_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $client->created_at->format('d/m/Y h:i A') }}
                </td>
            @endif

            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-400">
                    {{ $client->updated_at->diffForHumans() }}
                </td>
            @endif

            
            <td class="px-6 py-4">
                <div class="flex items-center gap-3 mt-2 md:mt-0">
                    {{-- BOTÓN RADICAL: VER TODO (MODAL) --}}
                    <button @click="$dispatch('open-modal', 'view-client-{{ $client->id }}')" 
                            class="bg-gray-100 text-gray-600 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-all shadow-sm"
                            title="Ver detalles completos">
                        <x-heroicon-s-eye class="w-5 h-5" />
                    </button>

                    <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:text-indigo-900 p-2 rounded-full hover:bg-indigo-50">
                        <x-heroicon-s-pencil class="w-5 h-5" />
                    </a>

                    <button @click="$dispatch('open-modal', 'confirm-deletion-{{ $client->id }}')" class="text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center py-10 text-gray-500 italic">
                No hay clientes registrados
            </td>
        </tr>
    @endforelse
</x-data-table>
@include('clients.partials.modals')