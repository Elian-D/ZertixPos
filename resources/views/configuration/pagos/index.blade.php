@php
    // Cuenta Contable solo aplica con accounting.advanced activo (REQ-02.15) —
    // Tipos de Pago es configuración base, siempre accesible.
    $showAccountingColumn = module_enabled('accounting.advanced');
@endphp
<x-app-layout>

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

            {{-- TÍTULO + ACCIONES --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b pb-3">
                <h2 class="text-xl font-semibold text-gray-800">
                    Métodos de Pago
                </h2>

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

            {{-- GRID DE CARDS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($tipoPago as $pago)
                    <div class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold text-gray-900">{{ $pago->nombre }}</h3>
                                @if($showAccountingColumn)
                                    <span class="inline-block mt-1 whitespace-nowrap font-mono text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                        {{ $pago->account->code ?? 'S/N' }} - {{ $pago->account->name ?? 'Sin cuenta' }}
                                    </span>
                                @endif
                            </div>

                            <span class="px-2 py-1 text-xs rounded-full font-bold whitespace-nowrap
                                {{ $pago->estado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $pago->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                            {{-- TOGGLE ESTADO --}}
                            <form action="{{ route('configuration.pagos.toggle', $pago) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button class="text-xs px-3 py-1 rounded border
                                    {{ $pago->estado ? 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100' : 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' }}">
                                    {{ $pago->estado ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>

                            <div class="flex-1"></div>

                            {{-- EDITAR --}}
                            @if($pago->estado)
                                @if($pago->isSystemProtected())
                                    <button type="button"
                                        class="text-gray-300 p-1 cursor-not-allowed"
                                        title="Este método es requerido por el sistema y no puede ser editado">
                                        <x-heroicon-s-pencil class="w-5 h-5" />
                                    </button>
                                @else
                                    <button type="button" @click="$dispatch('open-modal', 'edit-tipo-pago-{{ $pago->id }}')"
                                        class="text-zertix-secondary p-1 rounded hover:bg-zertix-secondary/10">
                                        <x-heroicon-s-pencil class="w-5 h-5" />
                                    </button>
                                @endif
                            @endif

                            {{-- ELIMINAR --}}
                            @if($pago->isSystemProtected())
                                <button type="button"
                                    class="text-gray-300 p-1 cursor-not-allowed"
                                    title="Protegido por el sistema">
                                    <x-heroicon-s-trash class="w-5 h-5" />
                                </button>
                            @else
                                <button type="button" @click="$dispatch('open-modal', 'confirm-payment-deletion-{{ $pago->id }}')"
                                    class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50">
                                    <x-heroicon-s-trash class="w-5 h-5" />
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10 text-gray-500 italic">
                        No hay tipos de pago registrados.
                    </div>
                @endforelse
            </div>
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

            @if ($showAccountingColumn)
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
            @endif

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

            @if ($showAccountingColumn)
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
            @endif

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
</x-app-layout>
