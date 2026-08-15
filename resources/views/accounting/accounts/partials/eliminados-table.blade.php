{{-- 1. La Tabla de Cuentas Eliminadas --}}
<x-data-table 
    :items="$items" 
    :headers="['code' => 'Código', 'account' => 'Cuenta Contable', 'deleted_at' => 'Eliminado']"
    :visibleColumns="['code', 'account', 'deleted_at', 'acciones']">

    @forelse($items as $item)
        <tr class="hover:bg-red-50/30 transition duration-150 group">
            <td class="px-6 py-4 text-sm font-mono font-bold text-gray-500">
                {{ $item->code }}
            </td>
            
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mr-3">
                        <x-heroicon-s-building-library class="w-4 h-4"/>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-700">{{ $item->name }}</div>
                        <div class="text-[10px] uppercase text-gray-400 font-medium tracking-wider">
                            Nivel {{ $item->level }} • {{ $item->type }}
                        </div>
                    </div>
                </div>
            </td>

            <td class="px-6 py-4 text-sm text-gray-400 italic">
                {{ $item->deleted_at->diffForHumans() }}
            </td>

            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                    {{-- Botón Restaurar --}}
                    <form action="{{ route('finance.accounts.restaurar', $item->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" title="Restaurar Cuenta" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
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
            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <x-heroicon-s-trash class="w-12 h-12 text-gray-200 mb-2" />
                    <p class="font-medium text-gray-400">La papelera contable está vacía.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-data-table>

{{-- 2. Modales de Confirmación Permanente --}}
@foreach($items as $item)
    <x-ui.confirm-deletion-modal 
        :id="$item->id"
        :title="'¿Borrar Cuenta Permanentemente?'"
        :itemName="$item->code . ' - ' . $item->name"
        :route="route('finance.accounts.borrarDefinitivo', $item->id)"
    >
        <div class="space-y-3">
            <p class="text-sm text-red-600 font-bold">
                ADVERTENCIA CONTABLE CRÍTICA
            </p>
            <p class="text-xs text-gray-600 leading-relaxed">
                Estás a punto de borrar definitivamente la cuenta <strong>{{ $item->name }}</strong>. 
                Si esta cuenta tiene registros vinculados en el libro mayor o asientos contables históricos, esta acción causará <strong>errores graves de integridad en el sistema</strong>.
            </p>
            <p class="text-[11px] bg-red-50 p-2 border border-red-100 text-red-700 rounded italic">
                Se recomienda únicamente si la cuenta fue creada por error y no tiene movimientos.
            </p>
        </div>
    </x-ui.confirm-deletion-modal>
@endforeach