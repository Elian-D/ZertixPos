<x-data-table :items="$items" :headers="$allColumns" :visibleColumns="$visibleColumns" :bulkActions="false">
    @forelse($items as $entry)
        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
            {{-- Fecha del Asiento --}}
            @if(in_array('entry_date', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <span class="block font-medium text-gray-700">{{ $entry->created_at->format('d/m/Y') }}</span>
                    <span class="text-[10px]">{{ $entry->created_at->format('h:i A') }}</span>
                </td>
            @endif

            {{-- Número (ID Correlativo) --}}
            @if(in_array('number', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                    #{{ str_pad($entry->id, 6, '0', STR_PAD_LEFT) }}
                </td>
            @endif

            {{-- Referencia --}}
            @if(in_array('reference', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $entry->reference ?? 'N/A' }}
                </td>
            @endif

            {{-- Descripción / Glosa --}}
            @if(in_array('description', $visibleColumns))
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $entry->description }}">
                    {{ $entry->description }}
                </td>
            @endif

            {{-- Total Débito --}}
            @if(in_array('debit', $visibleColumns))
                <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">
                    {{ number_format($entry->total_debit, 2) }}
                </td>
            @endif

            {{-- Total Crédito --}}
            @if(in_array('credit', $visibleColumns))
                <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">
                    {{ number_format($entry->total_credit, 2) }}
                </td>
            @endif

            {{-- Estado (Usando estilos del Modelo) --}}
            @if(in_array('status', $visibleColumns))
                <td class="px-6 py-4">
                    <x-ui.badge :variant="match($entry->status) {
                        \App\Models\Accounting\JournalEntry::STATUS_DRAFT => 'slate',
                        \App\Models\Accounting\JournalEntry::STATUS_POSTED => 'success',
                        \App\Models\Accounting\JournalEntry::STATUS_CANCELLED => 'error',
                        default => 'info',
                    }" size="sm" :dot="false">
                        {{ $statuses[$entry->status] ?? $entry->status }}
                    </x-ui.badge>
                </td>
            @endif

            {{-- Creado por --}}
            @if(in_array('created_by', $visibleColumns))
                <td class="px-6 py-4 text-xs text-gray-500">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-700">{{ $entry->creator->name ?? 'Sistema' }}</span>
                    </div>
                </td>
            @endif

            {{-- Última Actualización --}}
            @if(in_array('updated_at', $visibleColumns))
                <td class="px-6 py-4 text-[11px] text-gray-400">
                    {{ $entry->updated_at->diffForHumans() }}
                </td>
            @endif

            {{-- Acciones --}}
            <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                    @if($entry->status === \App\Models\Accounting\JournalEntry::STATUS_DRAFT)
                        @can('journal_entries.edit')
                            <a href="{{ route('finance.journal_entries.edit', $entry) }}" 
                               class="bg-white border border-amber-200 text-amber-600 hover:bg-amber-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                               title="Editar Asiento">
                                <x-heroicon-s-pencil-square class="w-4 h-4" />
                            </a>
                        @endcan
                        
                        @can('journal_entries.post')
                            <button type="button" 
                                    @click="$dispatch('open-modal', 'confirm-post-{{ $entry->id }}')"
                                    class="bg-white border border-emerald-200 text-emerald-600 hover:bg-emerald-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                                    title="Asentar Asiento">
                                <x-heroicon-s-check-badge class="w-4 h-4" />
                            </button>
                        @endcan
                    @endif

                    <button @click="$dispatch('open-modal', 'view-entry-{{ $entry->id }}')" 
                            class="bg-white border border-gray-200 text-gray-500 hover:bg-zertix-primary-600 hover:text-white p-2 rounded-lg transition-all shadow-sm"
                            title="Ver Detalle Contable">
                        <x-heroicon-s-eye class="w-4 h-4" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="100%" class="text-center py-12 text-gray-400 italic bg-gray-50/50">
                <div class="flex flex-col items-center justify-center">
                    <x-heroicon-o-document-magnifying-glass class="w-12 h-12 text-gray-300 mb-2"/>
                    <p>No se encontraron asientos contables con los criterios seleccionados.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

@foreach($items as $entry)
    <x-modal name="view-entry-{{ $entry->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl">
            {{-- Header Dinámico --}}
            @php
                $statusStyles = \App\Models\Accounting\JournalEntry::getStatusStyles();
                $currentStyle = $statusStyles[$entry->status] ?? 'bg-gray-50 text-gray-700';
            @endphp
            
            <div class="px-6 py-4 border-b relative {{ $currentStyle }}">
                <div class="flex justify-between items-center">
                    <div class="flex gap-3 items-center">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-white shadow-sm border border-current opacity-80">
                            <x-heroicon-s-book-open class="w-6 h-6"/>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold leading-tight">Asiento #{{ str_pad($entry->id, 6, '0', STR_PAD_LEFT) }}</h3>
                            <p class="text-xs font-medium opacity-70 italic">
                                {{ $entry->entry_date->format('d/m/Y') }} — {{ $statuses[$entry->status] ?? $entry->status }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] block font-mono opacity-70">REF: {{ $entry->reference ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white">
                {{-- Glosa/Concepto --}}
                <div class="mb-6">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Concepto General</h4>
                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100 italic">
                        "{{ $entry->description }}"
                    </p>
                </div>

                {{-- Tabla de Movimientos Contables --}}
                <div class="rounded-lg border border-gray-100 overflow-hidden mb-6">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-2">Cuenta Contable</th>
                                <th class="px-4 py-2 text-right">Débito</th>
                                <th class="px-4 py-2 text-right">Crédito</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($entry->items as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-gray-800">{{ $item->account->code }}</span><br>
                                        <span class="text-gray-500">{{ $item->account->name }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono {{ $item->debit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                        {{ number_format($item->debit, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono {{ $item->credit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                        {{ number_format($item->credit, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold border-t-2">
                            <tr>
                                <td class="px-4 py-2 text-gray-500">TOTALES</td>
                                <td class="px-4 py-2 text-right text-zertix-primary-600">{{ number_format($entry->total_debit, 2) }}</td>
                                <td class="px-4 py-2 text-right text-zertix-primary-600">{{ number_format($entry->total_credit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Footer de Auditoría --}}
                <div class="flex justify-between items-center pt-4 border-t text-[11px] text-gray-400">
                    <div>
                        <span class="block"><strong>Creado por:</strong> {{ $entry->creator->name ?? 'Sistema' }}</span>
                        <span><strong>Fecha:</strong> {{ $entry->created_at->format('d/m/Y h:i A') }}</span>
                    </div>
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cerrar</x-ui.button>
                </div>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-post-{{ $entry->id }}" maxWidth="sm">
        <form action="{{ route('finance.journal_entries.post', $entry) }}" method="POST" class="p-6">
            @csrf
            @method('PATCH')

            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 mb-4">
                    <x-heroicon-s-check-badge class="h-6 w-6 text-emerald-600" />
                </div>
                
                <h2 class="text-lg font-bold text-gray-900">
                    Confirmar Asentamiento
                </h2>
                
                <p class="mt-2 text-sm text-gray-500">
                    ¿Estás seguro de asentar el asiento <span class="font-mono font-bold text-gray-700">#{{ str_pad($entry->id, 6, '0', STR_PAD_LEFT) }}</span>?
                </p>
                <p class="mt-1 text-xs text-amber-600 font-medium bg-amber-50 p-2 rounded border border-amber-100">
                    Esta acción es irreversible y afectará los saldos de las cuentas contables.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">
                    Cancelar
                </x-ui.button>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Asentar Ahora
                </button>
            </div>
        </form>
    </x-modal>
@endforeach