<x-data-table 
    :items="$items" 
    :headers="['doc' => 'Documento', 'client' => 'Cliente', 'amount' => 'Monto Original', 'deleted_at' => 'Eliminado']"
    :visibleColumns="['doc', 'client', 'amount', 'deleted_at']">

    @forelse($items as $item)
        <tr class="hover:bg-red-50/30 transition duration-150 group">
            {{-- Documento --}}
            <td class="px-6 py-4 text-sm font-mono font-bold text-gray-600">
                {{ $item->document_number }}
            </td>
            
            {{-- Cliente --}}
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mr-3">
                        <x-heroicon-s-user class="w-4 h-4"/>
                    </div>
                    <div class="text-sm font-bold text-gray-700">
                        {{ $item->client->name ?? 'Cliente no encontrado' }}
                    </div>
                </div>
            </td>

            {{-- Monto --}}
            <td class="px-6 py-4 text-sm font-bold text-gray-900">
                {{ config('regional.currency_symbol') }}{{ number_format($item->total_amount, 2) }}
            </td>

            {{-- Fecha de eliminación --}}
            <td class="px-6 py-4 text-sm text-gray-400 italic">
                {{ $item->deleted_at->diffForHumans() }}
            </td>

            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Botón Restaurar --}}
                    <form action="{{ route('accounting.receivables.restaurar', $item->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" title="Restaurar Registro" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                            <x-heroicon-s-arrow-path class="w-5 h-5" />
                        </button>
                    </form>

                    {{-- Botón Eliminar Definitivo --}}
                    <button type="button" 
                            @click="$dispatch('open-modal', 'confirm-deletion-{{ $item->id }}')"
                            title="Borrado permanente"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <x-heroicon-s-trash class="w-5 h-5" />
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-trash class="w-12 h-12 text-gray-200 mb-2" />
                    <p class="font-medium text-gray-400">No hay cuentas por cobrar en la papelera.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

{{-- Modales de Confirmación Permanente --}}
@foreach($items as $item)
    <x-ui.confirm-deletion-modal 
        :id="$item->id"
        :title="'¿Eliminar registro permanentemente?'"
        :itemName="$item->document_number . ' - ' . ($item->client->name ?? '')"
        :route="route('accounting.receivables.borrarDefinitivo', $item->id)"
    >
        <div class="space-y-3">
            <p class="text-sm text-red-600 font-bold uppercase tracking-tight">
                Acción Irreversible
            </p>
            <p class="text-xs text-gray-600 leading-relaxed">
                Estás borrando físicamente la cuenta <strong>{{ $item->document_number }}</strong> de la base de datos. 
                Esto eliminará la vinculación con el asiento contable original y <strong>no podrá ser consultada en reportes históricos</strong>.
            </p>
            @if($item->current_balance > 0)
                <p class="text-[11px] bg-amber-50 p-2 border border-amber-100 text-amber-700 rounded font-medium">
                    Nota: Este registro aún tenía un saldo pendiente de {{ config('regional.currency_symbol') }}{{ number_format($item->current_balance, 2) }}.
                </p>
            @endif
        </div>
    </x-ui.confirm-deletion-modal>
@endforeach