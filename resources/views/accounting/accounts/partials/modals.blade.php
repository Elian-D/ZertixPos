{{-- MODAL CREAR --}}
<x-modal name="create-account" maxWidth="md">
    <x-form-header
        title="Nueva Cuenta Contable"
        subtitle="Defina la ubicación y el tipo de cuenta en el catálogo."
        :back-route="route('finance.accounts.index')" />

    <form action="{{ route('finance.accounts.store') }}" method="POST" class="p-6">
        @csrf

        <div class="space-y-4">
            {{-- Código --}}
            <div>
                <x-input-label for="code" value="Código Contable" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full font-mono" placeholder="Ej: 1.1.01" required />
            </div>

            {{-- Nombre --}}
            <div>
                <x-input-label for="name" value="Nombre de la Cuenta" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Ej: Caja General" required />
            </div>

            {{-- Cuenta Padre --}}
            <div>
                <x-input-label for="parent_id" value="Cuenta Superior (Padre)" />
                <select name="parent_id" id="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">-- Ninguna (Cuenta Raíz) --</option>
                    @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} - {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tipo de Cuenta --}}
            <div>
                <x-input-label for="type" value="Tipo de Cuenta" />
                <select name="type" id="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach($accountTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Es Posteable (Recibe Asientos) --}}
            <div class="flex items-center gap-2 py-2">
                <input type="checkbox" name="is_selectable" id="is_selectable" value="1" {{ old('is_selectable', '1') == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <x-input-label for="is_selectable" value="¿Esta cuenta recibe asientos contables?" class="inline" />
            </div>

            {{-- Estado --}}
            <div>
                <x-input-label value="Disponibilidad" />
                <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                    <label class="flex-1">
                        <input type="radio" name="is_active" value="1" class="peer hidden" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-green-500 peer-checked:text-white">
                            Activa
                        </span>
                    </label>
                    <label class="flex-1">
                        <input type="radio" name="is_active" value="0" class="peer hidden" {{ old('is_active') == '0' ? 'checked' : '' }}>
                        <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-red-500 peer-checked:text-white">
                            Bloqueada
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
            <x-ui.button type="submit" variant="primary">Guardar Cuenta</x-ui.button>
        </div>
    </form>
</x-modal>


@foreach($items as $item)

{{-- MODAL: DETALLES COMPLETOS DE LA CUENTA --}}
<x-modal name="view-account-{{ $item->id }}" maxWidth="2xl">
    <div class="overflow-hidden rounded-xl">
        {{-- Header dinámico según el Tipo de Cuenta --}}
        @php
            $typeColors = [
                'asset'     => 'from-emerald-50 to-white text-emerald-700 bg-emerald-100',
                'liability' => 'from-red-50 to-white text-red-700 bg-red-100',
                'equity'    => 'from-blue-50 to-white text-blue-700 bg-blue-100',
                'revenue'   => 'from-indigo-50 to-white text-indigo-700 bg-indigo-100',
                'expense'   => 'from-amber-50 to-white text-amber-700 bg-amber-100',
            ];
            $style = $typeColors[$item->type] ?? 'from-gray-50 to-white text-gray-700 bg-gray-100';
            $gradient = explode(' ', $style)[0] . ' ' . explode(' ', $style)[1];
            $iconStyle = explode(' ', $style)[2] . ' ' . explode(' ', $style)[3];
        @endphp

        <div class="bg-gradient-to-r {{ $gradient }} px-8 py-6 border-b relative">
            <div class="flex justify-between items-start">
                <div class="flex gap-4 items-center">
                    <div class="w-12 h-12 {{ $iconStyle }} rounded-xl flex items-center justify-center shadow-sm">
                        <x-heroicon-s-building-library class="w-7 h-7"/>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $item->name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-mono font-bold px-2 py-0.5 bg-white border border-gray-200 text-indigo-600 rounded">
                                {{ $item->code }}
                            </span>
                            <span class="text-gray-400 text-xs">•</span>
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Nivel {{ $item->level }}</span>
                        </div>
                    </div>
                </div>
                
                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase ring-1 ring-inset shadow-sm {{ $item->is_active ? 'bg-emerald-100 text-emerald-700 ring-emerald-600/20' : 'bg-red-100 text-red-700 ring-red-600/20' }}">
                    {{ $item->is_active ? 'Cuenta Activa' : 'Cuenta Bloqueada' }}
                </span>
            </div>
        </div>

        <div class="p-8 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                {{-- Columna Izquierda: Clasificación --}}
                <div class="space-y-6">
                    {{-- Bloque de Cliente Vinculado (NUEVO) --}}
                        @if($item->client)
                        <section class="animate-in fade-in slide-in-from-left-4 duration-500">
                            <h4 class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <x-heroicon-s-user-circle class="w-4 h-4"/> Enlace Externo
                            </h4>
                            <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm border border-indigo-100">
                                    <span class="text-indigo-600 font-black text-xs">{{ substr($item->client->name, 0, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-indigo-400 uppercase font-bold block leading-none mb-1">Cliente Asociado</span>
                                    <p class="text-sm font-bold text-gray-800">{{ $item->client->name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[9px] px-1.5 py-0.5 bg-indigo-100 text-indigo-700 rounded font-mono">{{ $item->client->tax_label }}:  {{ $item->client->tax_id }}</span>
                                        <a href="{{ route('clients.edit', $item->client->id) }}" class="text-[9px] text-indigo-600 hover:underline font-bold uppercase">Ver Perfil</a>
                                    </div>
                                </div>
                            </div>
                        </section>
                        @endif
                    <section>
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <x-heroicon-s-tag class="w-4 h-4"/> Clasificación Contable
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-3">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Naturaleza / Tipo</span>
                                <p class="text-sm font-bold text-gray-700 uppercase">{{ \App\Models\Accounting\AccountingAccount::getTypes()[$item->type] ?? $item->type }}</p>
                            </div>
                            <div class="pt-2 border-t border-gray-200/50">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Cuenta Superior</span>
                                <p class="text-sm font-medium text-gray-600">
                                    {{ $item->parent ? $item->parent->code . ' - ' . $item->parent->name : 'Nivel Raíz (Principal)' }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Columna Derecha: Atributos y Estado --}}
                <div class="space-y-6">
                    <section>
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <x-heroicon-s-adjustments-horizontal class="w-4 h-4"/> Propiedades
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2 {{ $item->is_selectable ? 'bg-blue-50 rounded-lg' : '' }}">
                                <span class="text-xs text-gray-500 font-medium">¿Es Posteable?</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold {{ $item->is_selectable ? 'text-blue-700' : 'text-gray-400' }}">
                                        {{ $item->is_selectable ? 'SÍ (Recibe Asientos)' : 'NO (Solo Totales)' }}
                                    </span>
                                    <x-heroicon-s-check-circle @class(['w-5 h-5', 'text-blue-600' => $item->is_selectable, 'text-gray-300' => !$item->is_selectable]) />
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center px-2">
                                <span class="text-xs text-gray-500 font-medium">Fecha de Registro</span>
                                <span class="text-xs font-bold text-gray-700">
                                    {{ $item->created_at?->format('d/m/Y') ?? 'N/A' }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center px-2">
                                <span class="text-xs text-gray-500 font-medium">Último Cambio</span>
                                <span class="text-xs font-bold text-gray-700">
                                    {{ $item->updated_at?->diffForHumans() ?? 'Sin cambios' }}
                                </span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Footer con acciones rápidas --}}
            <div class="mt-10 pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-[10px] text-gray-300 uppercase tracking-tighter font-mono">UUID: {{ $item->id }}</div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="flex-1 sm:flex-none justify-center">
                        Cerrar Detalle
                    </x-ui.button>
                    
                    <button @click="$dispatch('close'); $dispatch('open-modal', 'edit-account-{{ $item->id }}')" 
                            class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition shadow-md shadow-indigo-100">
                        <x-heroicon-s-pencil class="w-4 h-4 mr-2" /> Editar Cuenta
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-modal>

{{-- MODAL EDITAR --}}
<x-modal name="edit-account-{{ $item->id }}" maxWidth="md">
    <x-form-header
        title="Editar Cuenta: {{ $item->code }}"
        subtitle="Actualice la información de la cuenta contable."
        :back-route="route('finance.accounts.index')" />

    <form method="POST" action="{{ route('finance.accounts.update', $item) }}" class="p-6">
        @csrf @method('PUT')

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label value="Código" />
                    <x-text-input name="code" type="text" class="mt-1 block w-full font-mono bg-gray-50" value="{{ $item->code }}" required />
                </div>
                <div>
                    <x-input-label value="Tipo" />
                    <select name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach($accountTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('type', $item->type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <x-input-label value="Nombre de la Cuenta" />
                <x-text-input name="name" type="text" class="mt-1 block w-full" value="{{ $item->name }}" required />
            </div>

            <div>
                <x-input-label value="Cuenta Superior (Padre)" />
                <select name="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">-- Ninguna (Cuenta Raíz) --</option>
                    @foreach($parentAccounts as $parent)
                        @continue($parent->id == $item->id) {{-- No puede ser su propio padre --}}
                        <option value="{{ $parent->id }}" {{ old('parent_id', $item->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} - {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 py-2 border-t border-b border-gray-50">
                <input type="checkbox" name="is_selectable" id="edit_selectable_{{ $item->id }}" value="1" {{ old('is_selectable', $item->is_selectable) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                <x-input-label for="edit_selectable_{{ $item->id }}" value="¿Recibe asientos contables?" class="inline" />
            </div>

            <div>
                <x-input-label value="Estado" />
                <div class="flex p-1 bg-gray-100 rounded-lg mt-1 w-full">
                    <label class="flex-1">
                        <input type="radio" name="is_active" value="1" class="peer hidden" {{ old('is_active', $item->is_active) == '1' ? 'checked' : '' }}>
                        <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-green-500 peer-checked:text-white">
                            Activa
                        </span>
                    </label>
                    <label class="flex-1">
                        <input type="radio" name="is_active" value="0" class="peer hidden" {{ old('is_active', $item->is_active) == '0' ? 'checked' : '' }}>
                        <span class="block text-center px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-all text-gray-500 hover:text-gray-700 peer-checked:bg-red-500 peer-checked:text-white">
                            Bloqueada
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
            <x-ui.button type="submit" variant="primary">Actualizar Cuenta</x-ui.button>
        </div>
    </form>
</x-modal>

<x-ui.confirm-deletion-modal 
    :id="$item->id"
    :title="'¿Eliminar Cuenta Contable?'"
    :itemName="$item->code . ' - ' . $item->name"
    :type="'la cuenta'"
    :route="route('finance.accounts.destroy', $item)"
/>
@endforeach