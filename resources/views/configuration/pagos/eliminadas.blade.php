<x-app-layout>

    <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow-xl rounded-lg p-6">

            {{-- Mensajes --}}
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Título --}}
            <h2 class="text-xl font-semibold text-gray-800 mb-6 border-b pb-3">
                Tipos de Pago Eliminados
            </h2>

            {{-- Toolbar --}}
            <div class="flex mb-6">
                <a href="{{ route('configuration.pagos.index') }}"
                class="inline-flex items-center px-4 py-2
                        border border-gray-300 rounded-md
                        text-sm font-medium text-gray-700
                        bg-white hover:bg-gray-100
                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
                    <x-heroicon-s-arrow-left class="w-5 h-5 mr-2" />
                    Volver al listado
                </a>
            </div>


            {{-- TABLA RESPONSIVA --}}
            <x-data-table
                :items="$items"
                :headers="['Nombre', 'Eliminado']"> {{-- Añadí 'Acciones' al header --}}

                @forelse($items as $pago)
                    {{-- La magia responsiva: block en móvil, table-row en md+ --}}
                    <tr class="block md:table-row hover:bg-gray-50 transition duration-150 p-4 border-b border-gray-200 md:border-b-0">

                        {{-- COLUMNA 1: NOMBRE (El cuerpo principal de la tarjeta en móvil) --}}
                        <td class="block md:table-cell px-6 py-4 font-semibold text-gray-900 md:font-normal md:text-sm">
                             <div class="font-bold text-base mb-1 md:font-normal md:text-sm">
                                {{ $pago->nombre }}
                            </div>
                        </td>

                        {{-- COLUMNA 2: FECHA DE ELIMINACIÓN (Visible en móvil como información secundaria) --}}
                        <td class="block md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 md:text-gray-600">
                            <span class="md:hidden font-bold text-gray-700 mr-2">Eliminado:</span>
                            {{ $pago->deleted_at->format('d/m/Y') }}
                        </td>

                        {{-- COLUMNA 3: ACCIONES (Visible en móvil y desktop) --}}
                        <td class="block md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2 mt-2 md:mt-0"> {{-- Ajuste de margen en móvil --}}

                                {{-- Restaurar --}}
                                <form action="{{ route('configuration.pagos.restaurar', $pago->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded hover:bg-green-200">
                                        Restaurar
                                    </button>
                                </form>


                                {{-- Eliminar Definitivamente --}}
                                <form action="{{ route('configuration.pagos.borrarDefinitivo', $pago->id) }}" method="POST" x-data>
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" @click="$dispatch('open-modal', 'confirm-delete-{{ $pago->id }}')"
                                        class="px-3 py-1 text-sm bg-red-100 text-red-800 rounded hover:bg-red-200">
                                        Eliminar
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        {{-- Aumentamos el colspan a 3 para cubrir las columnas Nombre, Eliminado y Acciones --}}
                        <td colspan="3" class="text-center py-6 text-gray-500">
                            No hay tipo de pagos eliminados.
                        </td>
                    </tr>
                @endforelse

            </x-data-table>
        </div>
    </div>

    {{-- Modales para eliminación definitiva --}}
    @foreach($items as $pago)
        <x-modal name="confirm-delete-{{ $pago->id }}" :show="false" maxWidth="md">
            <form action="{{ route('configuration.pagos.borrarDefinitivo', $pago->id) }}" method="POST" class="p-6">
                @csrf
                @method('DELETE')

                <h2 class="text-lg font-medium text-gray-900">
                    ¿Eliminar definitivamente el tipo de pago?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Esta acción no se puede deshacer. Tipo de pago: 
                    <span class="font-bold text-red-600">{{ $pago->nombre }}</span>.
                </p>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        Cancelar
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        <x-heroicon-s-trash class="w-4 h-4 mr-2" />
                        Eliminar
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @endforeach

</x-app-layout>