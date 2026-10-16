{{-- Formulario de una cuenta de Cajas y Bancos (v1.5.0 REQ-3.5, /filament-form). Mismo
     partial para crear y editar; el saldo inicial solo se pide al crear, porque después el
     saldo solo cambia con movimientos o conciliando.
     Recibe: $types; opcional $account (editar) y $assignedToPos (recibe los pagos del POS). --}}
@php
    $currency = config('regional.currency_symbol');
    $editing = isset($account);
@endphp

<div class="flex flex-col gap-6" x-data="{ type: @js(old('type', $account->type ?? 'cash')) }">

    {{-- Fila 1: datos --}}
    <x-ui.infolist.section title="Datos de la cuenta" icon="heroicon-o-identification" :cols="0"
        description="Cómo se llama y si es una caja física o una cuenta de banco.">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            <x-ui.forms.input label="Nombre" name="name" required maxlength="100"
                :value="old('name', $account->name ?? '')" placeholder="Ej: Caja fuerte, Banco Popular"
                hint="El nombre con el que la vas a reconocer en los movimientos."
                :error="$errors->first('name')" />

            <div>
                <span class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo <span class="text-state-error">*</span></span>
                <div class="flex flex-wrap gap-x-6 gap-y-2 sm:pt-2.5">
                    @foreach($types as $value => $label)
                        <x-ui.forms.radio :label="$label" name="type" :value="$value" x-model="type" />
                    @endforeach
                </div>
                @error('type')
                    <p class="mt-1.5 text-xs font-medium text-state-error">{{ $message }}</p>
                @else
                    <p class="mt-1.5 text-xs text-slate-400">
                        <span x-show="type === 'cash'">Efectivo que se cuenta a mano: caja fuerte, caja chica.</span>
                        <span x-show="type === 'bank'" x-cloak>Una cuenta en un banco. Puede recibir los pagos con tarjeta o transferencia del POS.</span>
                    </p>
                @enderror
            </div>

            <div x-show="type === 'bank'" x-cloak>
                <x-ui.forms.input label="Banco" name="bank_name" maxlength="100"
                    :value="old('bank_name', $account->bank_name ?? '')" placeholder="Ej: Banco Popular"
                    :error="$errors->first('bank_name')" />
            </div>
            <div x-show="type === 'bank'" x-cloak>
                <x-ui.forms.input label="Últimos 4 dígitos" name="last_four_digits" inputmode="numeric" maxlength="4"
                    :value="old('last_four_digits', $account->last_four_digits ?? '')" placeholder="1234"
                    hint="Solo para reconocerla. Nunca escribas el número completo."
                    :error="$errors->first('last_four_digits')" />
            </div>
        </div>

        @if($editing && ($assignedToPos ?? false))
            <x-ui.alert variant="neutral" class="mt-5">
                Recibe los pagos del POS que no son efectivo: no se puede cambiar a Caja ni desactivar mientras esté asignada.
            </x-ui.alert>
        @endif
    </x-ui.infolist.section>

    {{-- Fila 2: saldo inicial (solo al crear) --}}
    @unless($editing)
        <x-ui.infolist.section title="Saldo inicial" icon="heroicon-o-currency-dollar" :cols="0"
            description="El negocio casi nunca empieza con el sistema: este es el punto de partida.">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                <x-ui.forms.input label="Saldo de hoy" name="opening_balance" type="number" step="0.01" min="0" required
                    :addonLeft="$currency" :value="old('opening_balance', '0')"
                    hint="Lo contado en la caja o lo que dice el banco hoy. Equivale a la primera conciliación."
                    :error="$errors->first('opening_balance')" />
                <x-ui.forms.input label="Fecha" name="opening_date" type="date" required max="{{ now()->toDateString() }}"
                    :value="old('opening_date', now()->toDateString())"
                    hint="El día de ese saldo."
                    :error="$errors->first('opening_date')" />
            </div>
            <x-ui.alert variant="neutral" class="mt-5">
                Después de crearla, el saldo solo cambia con movimientos o conciliando.
            </x-ui.alert>
        </x-ui.infolist.section>
    @endunless

    {{-- Fila 3: nota --}}
    <x-ui.infolist.section title="Nota" icon="heroicon-o-document-text" :cols="0">
        <x-ui.forms.textarea label="Nota" name="notes" :rows="3" maxlength="500"
            placeholder="Ej: Cuenta corriente para depósitos del negocio"
            :error="$errors->first('notes')">{{ old('notes', $account->notes ?? '') }}</x-ui.forms.textarea>
    </x-ui.infolist.section>
</div>
