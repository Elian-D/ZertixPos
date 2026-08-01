<x-config-layout>

    <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow-xl rounded-lg p-6">

            {{-- MENSAJES --}}
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

            {{-- TÍTULO --}}
            <h2 class="text-xl font-semibold text-gray-800 mb-6 border-b pb-3">
                Tipos de pagos
            </h2>

            {{-- Toolbar --}}
            <div class="flex flex-col md:flex-row justify-between gap-4 mb-6">

                <form method="GET" class="w-full md:w-2/3 space-y-3">
                    <div class="flex gap-2 items-center">

                        {{-- Buscador --}}
                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar tipo de pago..."
                               class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">

                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            <x-heroicon-s-magnifying-glass class="w-5 h-5" />
                        </button>

                        {{-- Dropdown filtros (Se mantiene la misma lógica) --}}
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open"
                                     class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-100">
                                <x-heroicon-s-funnel class="w-4 h-4" />
                                Filtros
                                <x-heroicon-s-chevron-down class="w-4 h-4" />
                            </button>

                            <div x-show="open" @click.outside="open = false" x-transition
                                 class="absolute right-0 z-20 mt-2 w-72 bg-white border border-gray-200 rounded-lg shadow-lg p-4 space-y-4">

                                {{-- Estado --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                    <select name="estado" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Todos</option>
                                        <option value="activo" {{ $estado === 'activo' ? 'selected' : '' }}>Activos</option>
                                        <option value="inactivo" {{ $estado === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                                    </select>
                                </div>

                                {{-- Acciones --}}
                                <div class="flex justify-end gap-2 pt-2 border-t">
                                    <a href="{{ route('configuration.pagos.index') }}"
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100">
                                        Limpiar
                                    </a>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                        Aplicar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Acciones --}}
                <div class="flex gap-2 self-start md:self-center">
                    
                    {{-- PAPELERA --}}
                    <a href="{{ route('configuration.pagos.eliminados') }}"
                    class="inline-flex items-center px-4 py-2
                            border border-gray-300 rounded-md
                            text-sm font-medium text-gray-700
                            bg-white hover:bg-gray-100
                            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
                        <x-heroicon-s-trash class="w-5 h-5 mr-2" />
                        Papelera
                    </a>

                    {{-- BOTÓN NUEVO --}}
                    <x-primary-button
                        class="bg-green-600 hover:bg-green-700 self-start md:self-center"
                        x-data
                        x-on:click="$dispatch('open-modal', 'crear-pago')">
                        <x-heroicon-s-plus class="w-5 h-5 mr-2" />
                        Nuevo Tipo Pago
                    </x-primary-button>
                </div>
            </div>

            {{-- TABLA RESPONSIVA --}}
            <x-data-table
                :items="$tipoPago"
                :headers="['Nombre', 'Cuenta Contable', 'Estado', 'Creado', 'Actualizado']">

                @forelse($tipoPago as $pago)
                    {{-- Fila responsiva: Card en móvil, Fila de tabla en md+ --}}
                    <tr class="block md:table-row hover:bg-gray-50 transition duration-150 p-4 border-b border-gray-200 md:border-b-0">

                        {{-- COLUMNA 1: NOMBRE + ESTADO EN MÓVIL --}}
                        <td class="block md:table-cell px-6 py-4 text-sm text-gray-600 w-full md:w-4/12">
                            <div class="font-bold text-gray-900 text-base mb-1 md:font-normal md:text-sm flex items-center gap-2">
                                {{ $pago->nombre }}
                                {{-- Estado visible en móvil, oculto en desktop (Se muestra en su columna dedicada) --}}
                                <span class="md:hidden">
                                    @if($pago->estado)
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Activo</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Inactivo</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        
                        {{-- COLUMNA 2: CUENTA CONTABLE (Oculto en móvil, visible en md+) --}}
                        <td class="hidden md:table-cell px-6 py-4 text-sm text-gray-600">
                            <span class="whitespace-nowrap font-mono text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                {{ $pago->account->code ?? 'S/N' }} - {{ $pago->account->name ?? 'Sin cuenta' }}
                            </span>
                        </td>

                        {{-- COLUMNA 2: ESTADO (Oculto en móvil, visible en md+) --}}
                        <td class="hidden md:table-cell px-6 py-4 text-sm text-gray-600 w-2/12">
                            @if($pago->estado)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Activo</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Inactivo</span>
                            @endif
                        </td>
                        
                        {{-- COLUMNA 3: CREADO (Oculto en móvil, visible en md+) --}}
                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-gray-600 w-2/12">
                            {{ $pago->created_at->format('d/m/Y') }}
                        </td>

                        {{-- COLUMNA 4: ACTUALIZADO (Oculto en móvil, visible en md+) --}}
                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-gray-600 w-2/12">
                            {{ $pago->updated_at->format('d/m/Y') }}
                        </td>

                        {{-- COLUMNA 5: ACCIONES (Visible en móvil y desktop) --}}
                        <td class="block md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium w-full md:w-2/12">
                            <div class="flex gap-2 mt-2 md:mt-0">
                                {{-- TOGGLE ESTADO: Siempre permitido si quieres pausar un método --}}
                                <form action="{{ route('configuration.pagos.toggle', $pago) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm px-3 py-1 rounded {{ $pago->estado ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $pago->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                {{-- BOTÓN EDITAR --}}
                                @if($pago->estado)
                                    @if($pago->isSystemProtected())
                                        <button type="button" 
                                            class="text-gray-400 p-1 cursor-not-allowed" 
                                            title="Este método es requerido por el sistema y no puede ser editado">
                                            <x-heroicon-s-pencil class="w-5 h-5" />
                                        </button>
                                    @else
                                        <button type="button" @click="$dispatch('open-modal', 'edit-tipo-pago-{{ $pago->id }}')"
                                            class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-100">
                                            <x-heroicon-s-pencil class="w-5 h-5" />
                                        </button>
                                    @endif
                                @endif

                                {{-- ELIMINAR --}}
                                @if($pago->isSystemProtected())
                                    <button type="button" 
                                        class="text-gray-400 p-1 cursor-not-allowed" 
                                        title="Protegido por el sistema">
                                        <x-heroicon-s-trash class="w-5 h-5" />
                                    </button>
                                @else
                                    <button type="button" @click="$dispatch('open-modal', 'confirm-payment-deletion-{{ $pago->id }}')" 
                                        class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-100">
                                        <x-heroicon-s-trash class="w-5 h-5" />
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-500">
                            No hay tipos de pago registrados.
                        </td>
                    </tr>
                @endforelse

            </x-data-table>
        </div>
    </div>

<x-modal name="crear-pago" :show="false" maxWidth="md">
        <form action="{{ route('configuration.pagos.store') }}" method="POST" class="p-6">
            @csrf

            {{-- Título --}}
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Crear nuevo tipo de pago') }}
            </h2>

            {{-- Input --}}
            <div class="mt-4">
                <x-input-label for="nombre" value="Nombre del tipo de pago" />
                <x-text-input
                    id="nombre"
                    name="nombre"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Nuevo tipo de pago"
                    required
                />
                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="accounting_account_id" value="Cuenta Contable Asociada" />
                <select name="accounting_account_id" id="accounting_account_id" 
                        class="mt-1 block w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Seleccione una cuenta --</option>
                    @foreach($cuentasContables as $cuenta)
                        <option value="{{ $cuenta->id }}">{{ $cuenta->code }} - {{ $cuenta->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('accounting_account_id')" class="mt-2" />
            </div>

            {{-- Botones --}}
            <div class="mt-6 flex justify-end">
                {{-- Cancelar --}}
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                {{-- Guardar --}}
                <x-primary-button class="ms-3 bg-green-600 hover:bg-green-700">
                    {{ __('Agregar') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    @if ($errors->any())
        <script>
            window.addEventListener('load', () => {
                window.dispatchEvent(
                    new CustomEvent('open-modal', {
                        detail: 'crear-pago'
                    })
                )
            })
        </script>
    @endif

    @foreach($tipoPago as $pago)
    <x-modal name="edit-tipo-pago-{{ $pago->id }}" :show="false" maxWidth="md">
        <form method="POST" action="{{ route('configuration.pagos.update', $pago) }}" class="p-6">
            @csrf
            @method('PUT')

            {{-- TÍTULO --}}
            <h2 class="text-lg font-medium text-gray-900">
                Editar Tipo de Pago
            </h2>

            {{-- DESCRIPCIÓN --}}
            <p class="mt-1 text-sm text-gray-600">
                Modifica el nombre del tipo de pago.
            </p>

            {{-- INPUT --}}
            <div class="mt-4">
                <x-input-label for="nombre-{{ $pago->id }}" value="Nombre" />
                
                <x-text-input
                    id="nombre-{{ $pago->id }}"
                    name="nombre"
                    type="text"
                    class="mt-1 block w-full"
                    value="{{ old('nombre', $pago->nombre) }}"
                    required
                    autofocus
                />

                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="edit_account_{{ $pago->id }}" value="Cuenta Contable Asociada" />
                <select name="accounting_account_id" id="edit_account_{{ $pago->id }}" 
                        class="mt-1 block w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Seleccione una cuenta --</option>
                    @foreach($cuentasContables as $cuenta)
                        <option value="{{ $cuenta->id }}" {{ $pago->accounting_account_id == $cuenta->id ? 'selected' : '' }}>
                            {{ $cuenta->code }} - {{ $cuenta->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- BOTONES --}}
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button class="bg-green-600 hover:bg-green-700 self-start md:self-center">
                    Guardar cambios
                </x-primary-button>
            </div>
        </form>
    </x-modal>
    @endforeach

    @foreach($tipoPago as $pago)
        <x-modal name="confirm-payment-deletion-{{ $pago->id }}" :show="false" maxWidth="md">
            <form method="post" action="{{ route('configuration.pagos.destroy', $pago) }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900">
                    ¿Enviar Tipo de pago a la papelera?
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    El estado
                    <span class="font-semibold text-gray-900">
                        {{ $pago->nombre }}
                    </span>
                    será movida a la
                    <span class="font-semibold text-yellow-600">papelera</span>.
                </p>

                <p class="mt-1 text-sm text-gray-500">
                    Esta acción se puede revertir desde la papelera.
                </p>

                {{-- Área de Botones del Modal --}}
                <div class="mt-6 flex justify-end">
                    
                    {{-- Botón Cancelar --}}
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancelar') }}
                    </x-secondary-button>

                    {{-- Botón Eliminar (Rojo) --}}
                    <x-danger-button class="ms-3">
                        <x-heroicon-s-trash class="w-4 h-4 mr-2" />
                        {{ __('Eliminar Tipo de pago') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-config-layout>