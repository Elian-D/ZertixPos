<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $item)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            
            {{-- Fechas --}}
            @if(in_array('emission_date', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $item->emission_date->format('d/m/Y') }}
                </td>
            @endif

            @if(in_array('due_date', $visibleColumns))
                <td class="px-6 py-4 text-sm {{ $item->is_overdue ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                    {{ $item->due_date->format('d/m/Y') }}
                </td>
            @endif

            {{-- Documento y Cliente --}}
            @if(in_array('document_number', $visibleColumns))
                <td class="px-6 py-4 text-sm font-mono font-bold text-zertix-primary-700">
                    {{ $item->document_number }}
                </td>
            @endif

            @if(in_array('client', $visibleColumns))
                <td class="px-6 py-4 text-sm">
                    <div class="font-medium text-gray-900">{{ $item->client->name }}</div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ $item->client->tax_id ?? 'Sin RNC/Cédula' }}</div>
                </td>
            @endif

            @if(in_array('description', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500 max-w-[200px] truncate">
                    {{ $item->description }}
                </td>
            @endif

            {{-- Montos y Semáforo de Saldo --}}
            @if(in_array('total_amount', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-medium text-gray-600">
                    {{ number_format($item->total_amount, 2) }}
                </td>
            @endif

            @if(in_array('current_balance', $visibleColumns))
                <td class="px-6 py-4 text-sm text-right font-bold">
                    @php
                        // Lógica de Semáforo de Saldo
                        $percentageLeft = ($item->total_amount > 0) ? ($item->current_balance / $item->total_amount) * 100 : 0;
                        $balanceColor = 'text-gray-900';
                        
                        if ($item->current_balance <= 0) $balanceColor = 'text-emerald-600';
                        elseif ($percentageLeft <= 30) $balanceColor = 'text-blue-600'; // Casi pagado
                        elseif ($percentageLeft <= 70) $balanceColor = 'text-amber-600'; // Mitad
                        else $balanceColor = 'text-red-600'; // Deuda alta o total
                    @endphp
                    <span class="{{ $balanceColor }}">
                        {{ number_format($item->current_balance, 2) }}
                    </span>
                </td>
            @endif

            @if(in_array('accounting_account_id', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    @if($item->client->accounting_account_id)
                        {{-- Cuenta propia del cliente --}}
                        <p class="text-xs font-mono text-zertix-primary-600">
                            {{ $item->client->accountingAccount->code }} <br>
                        </p>
                    @else
                        {{-- Cuenta general de la CxC --}}
                        <p class="text-xs font-mono text-gray-600">
                            {{ $item->accountingAccount->code ?? '1.1.02' }} <br>
                        </p>
                    @endif
                </td>
            @endif

            {{-- Estado (Sincronizado con Modelo) --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4 text-center">
                    <x-ui.badge :variant="match($item->status) {
                        \App\Models\Accounting\Receivable::STATUS_UNPAID => 'error',
                        \App\Models\Accounting\Receivable::STATUS_PARTIAL => 'warning',
                        \App\Models\Accounting\Receivable::STATUS_PAID => 'success',
                        \App\Models\Accounting\Receivable::STATUS_CANCELLED => 'slate',
                        default => 'info',
                    }" size="sm" :dot="false">
                        {{ $item->status_label }}
                    </x-ui.badge>
                </td>
            @endif


            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $item->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    {{-- Ver Detalle --}}
                    <button @click="$dispatch('open-modal', 'view-receivable-{{ $item->id }}')" 
                            class="bg-white border border-gray-200 text-gray-500 hover:bg-zertix-primary-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                            title="Ver Detalle">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-16 text-gray-400 bg-gray-50/30">
                <x-heroicon-o-banknotes class="w-12 h-12 mx-auto text-gray-200 mb-2"/>
                <p>No se encontraron cuentas por cobrar.</p>
            </td>
        </tr>
    @endforelse
</x-data-table>

@include('accounting.receivables.partials.modals')