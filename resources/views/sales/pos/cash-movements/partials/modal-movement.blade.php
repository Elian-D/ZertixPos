@props(['sessionId' => null, 'sessions' => []])

<x-modal name="register-cash-movement" maxWidth="md">
    <x-form-header 
        title="Movimiento de Efectivo" 
        subtitle="Registre entradas o salidas de dinero de la caja actual." />

    {{-- Pasamos las variables del controlador a AlpineJS --}}
    <form method="POST" action="{{ route('sales.pos.cash-movements.store') }}" 
          class="p-6"
          x-data="{ 
            type: '{{ \App\Models\Sales\Pos\PosCashMovement::TYPE_OUT }}',
            amount: '',
            incomeAccounts: {{ Js::from($income_accounts) }},
            expenseAccounts: {{ Js::from($expense_accounts) }},
            get isOut() { return this.type === 'out' },
            get currentAccounts() {
                return this.isOut ? this.expenseAccounts : this.incomeAccounts;
            }
          }">
        @csrf
        
        @if($sessionId)
            <input type="hidden" name="pos_session_id" value="{{ $sessionId }}">
        @endif

        <div class="space-y-5">
            {{-- 1. Selección de Sesión --}}
            @if(!$sessionId)
                <div>
                    <x-input-label for="pos_session_id" value="Sesión de Caja Activa" />
                    <select name="pos_session_id" id="pos_session_id" 
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        <option value="">Seleccione una sesión...</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->terminal?->name ?? 'Sin Terminal' }} - {{ $s->user?->name }} (#{{ $s->id }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- 2. Selector Visual de Tipo (Toggle) --}}
            <div>
                <x-input-label value="Tipo de Operación" class="mb-2" />
                <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-xl border border-gray-200">
                    <button type="button" 
                        @click="type = 'out'"
                        :class="type === 'out' ? 'bg-white text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex items-center justify-center gap-2 py-2 text-xs font-bold rounded-lg transition-all">
                        <x-heroicon-s-arrow-trending-down class="w-4 h-4" />
                        SALIDA
                    </button>
                    <button type="button" 
                        @click="type = 'in'"
                        :class="type === 'in' ? 'bg-white text-green-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex items-center justify-center gap-2 py-2 text-xs font-bold rounded-lg transition-all">
                        <x-heroicon-s-arrow-trending-up class="w-4 h-4" />
                        ENTRADA
                    </button>
                </div>
                <input type="hidden" name="type" :value="type">
            </div>

            {{-- NUEVO: 3. Selección de Cuenta Contable (Dinámica) --}}
            <div>
                <x-input-label for="accounting_account_id" value="Cuenta Contable (Contrapartida)" />
                <select name="accounting_account_id" id="accounting_account_id" 
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                    <option value="">Seleccione cuenta...</option>
                    <template x-for="account in currentAccounts" :key="account.id">
                        <option :value="account.id" x-text="account.code + ' - ' + account.name"></option>
                    </template>
                </select>
                <p class="mt-1 text-[10px] text-gray-500 italic">
                    Seleccione el destino o procedencia contable del dinero.
                </p>
            </div>

            {{-- 4. Monto --}}
            <div>
                <x-input-label for="amount" value="Monto a Registrar" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-400 sm:text-sm font-bold">$</span>
                    </div>
                    <x-text-input 
                        id="amount" name="amount" type="number" step="0.01" x-model="amount"
                        class="block w-full pl-7 text-lg font-semibold" placeholder="0.00" required 
                    />
                </div>
                <p class="mt-1 text-[10px] font-medium italic" :class="isOut ? 'text-amber-600' : 'text-green-600'">
                    <span x-text="isOut ? 'Se restará del arqueo final.' : 'Se sumará al arqueo final.'"></span>
                </p>
            </div>

            {{-- 5. Motivo --}}
            <div>
                <x-input-label for="reason" value="Motivo / Razón" />
                <textarea 
                    id="reason" name="reason" rows="2"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                    placeholder="Ej: Pago a proveedor de limpieza, ingreso por cambio..." 
                    required></textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button 
                ::class="isOut ? 'bg-amber-600 hover:bg-amber-700' : 'bg-green-600 hover:bg-green-700'"
                class="transition-colors duration-200">
                <span x-text="isOut ? 'Registrar Salida' : 'Registrar Entrada'"></span>
            </x-primary-button>
        </div>
    </form>
</x-modal>