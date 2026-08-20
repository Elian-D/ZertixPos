<x-app-layout>
    <div class="max-w-4xl mx-auto py-4 md:py-8 px-4" 
         x-data="{ 
            clients: {{ $clients->toJson() }},
            allReceivables: {{ $pendingReceivables->toJson() }},
            tipoPagos: {{ $paymentMethods->toJson() }},
            selectedClientId: '{{ old('client_id', '') }}',
            selectedReceivableId: '{{ old('receivable_id', '') }}',
            selectedTipoPagoId: '{{ old('tipo_pago_id', '') }}',
            paymentAmount: {{ old('amount', 0) }},

            get filteredReceivables() {
                if (!this.selectedClientId) return [];
                return this.allReceivables.filter(r => r.client_id == this.selectedClientId);
            },

            get selectedReceivable() {
                return this.allReceivables.find(r => r.id == this.selectedReceivableId);
            },

            // Fase 6, REQ-6.9: Efectivo no deja rastro que pedir y Tarjeta ya viene
            // verificada por el datáfono — la Referencia solo aporta algo para
            // transferencia/depósito/cheque, y ahí es opcional, nunca bloqueante.
            get isCashOrCardMethod() {
                const tp = this.tipoPagos.find(t => t.id == this.selectedTipoPagoId);
                return tp ? ['efectivo', 'tarjeta'].includes(tp.slug) : false;
            },

            {{-- Lógica corregida: Solo error si supera el saldo o es cero/negativo --}}
            get exceedsBalance() {
                if (!this.selectedReceivable || !this.paymentAmount) return false;
                return parseFloat(this.paymentAmount) > parseFloat(this.selectedReceivable.current_balance);
            },

            get isValidAmount() {
                return this.paymentAmount > 0 && !this.exceedsBalance;
            }
         }">
        
        <form action="{{ route('finance.collections.store') }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf

            <x-ui.toasts />
            
            <x-form-header
                title="Nuevo Recibo de Cobro"
                subtitle="Registro de abonos o cancelaciones."
                :back-route="route('finance.collections.index')" />

            <div class="p-4 md:p-8 space-y-6 md:space-y-8">
                
                {{-- PASO 1: SELECCIÓN --}}
                <section>
                    <h3 class="font-bold text-gray-800 uppercase text-[10px] md:text-xs tracking-wider flex items-center gap-2 mb-4">
                        <span class="w-5 h-5 md:w-6 md:h-6 bg-emerald-600 text-white rounded-full flex items-center justify-center text-[10px]">1</span>
                        Cuenta por Cobrar
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="w-full">
                            <x-input-label value="Cliente" class="text-xs" />
                            <select name="client_id" 
                                    x-model="selectedClientId" 
                                    @change="selectedReceivableId = ''; paymentAmount = 0"
                                    class="w-full mt-1 border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm text-sm">
                                <option value="">Seleccione un cliente...</option>
                                <template x-for="client in clients" :key="client.id">
                                    <option :value="client.id" x-text="`${client.name} (Saldo: {{ config('regional.currency_symbol') }}${client.balance})`"></option>
                                </template>
                            </select>
                        </div>

                        <div class="w-full">
                            <x-input-label value="Documento / Factura" class="text-xs" />
                            <select name="receivable_id" 
                                    x-model="selectedReceivableId"
                                    :disabled="!selectedClientId"
                                    class="w-full mt-1 border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm text-sm disabled:bg-gray-50">
                                <option value="">Seleccione factura...</option>
                                <template x-for="receivable in filteredReceivables" :key="receivable.id">
                                    <option :value="receivable.id" x-text="`${receivable.document_number} — Saldo: {{ config('regional.currency_symbol') }}${receivable.current_balance}`"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </section>

                {{-- PASO 2: DETALLES --}}
                <section x-show="selectedReceivableId" x-transition>
                    <h3 class="font-bold text-gray-800 uppercase text-[10px] md:text-xs tracking-wider flex items-center gap-2 mb-4">
                        <span class="w-5 h-5 md:w-6 md:h-6 bg-emerald-600 text-white rounded-full flex items-center justify-center text-[10px]">2</span>
                        Detalles del Cobro
                    </h3>

                    <div class="bg-gray-50/50 border border-gray-100 rounded-2xl p-4 md:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                            {{-- Monto --}}
                            <div class="md:col-span-1">
                                <x-input-label value="Monto del Abono" class="font-bold text-emerald-700" />
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold">{{ config('regional.currency_symbol') }}</span>
                                    <input type="number" step="0.01" name="amount" 
                                           x-model="paymentAmount"
                                           class="w-full pl-14 py-3 border-2 rounded-xl text-lg font-bold transition-all focus:ring-emerald-500"
                                           :class="exceedsBalance ? 'border-red-300 bg-red-50 text-red-600' : 'border-gray-200'"
                                           placeholder="0.00" required />
                                </div>
                                <p x-show="exceedsBalance" class="text-[10px] text-red-500 mt-1 font-bold italic">
                                    El monto no puede ser mayor a la deuda.
                                </p>
                            </div>

                            {{-- Otros campos --}}
                            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label value="Método" />
                                    <select name="tipo_pago_id" x-model="selectedTipoPagoId" class="w-full mt-1 border-gray-200 rounded-xl text-sm shadow-sm" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label value="Fecha" />
                                    <input type="date" name="payment_date" class="w-full mt-1 border-gray-200 rounded-xl text-sm shadow-sm" value="{{ date('Y-m-d') }}" required />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                            {{-- Oculta para Efectivo/Tarjeta — no hay nada que referenciar
                                 (Fase 6, REQ-6.9). --}}
                            <div class="md:col-span-1" x-show="!isCashOrCardMethod" x-cloak>
                                <x-input-label for="reference" value="Referencia Bancaria / Cheque" />
                                <x-text-input id="reference" name="reference" type="text"
                                    class="w-full mt-1 rounded-xl bg-gray-50"
                                    placeholder="Opcional (Ej: Trans-9928)" />
                                <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="note" value="Observaciones del Cobro" />
                                <textarea id="note" name="note" rows="2"
                                    class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm"
                                    placeholder="Detalles adicionales sobre este cobro..."></textarea>
                                <x-input-error :messages="$errors->get('note')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- RESUMEN PROYECTADO (Abono Parcial vs Total) --}}
                <div x-show="selectedReceivable && paymentAmount > 0" 
                     class="bg-emerald-900 rounded-2xl p-4 md:p-6 text-white shadow-xl relative overflow-hidden">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <div class="bg-white/10 p-3 rounded-lg flex-1 md:flex-none text-center">
                                <p class="text-[9px] uppercase opacity-70">Deuda Actual</p>
                                <p class="font-mono font-bold" x-text="'{{ config('regional.currency_symbol') }}' + selectedReceivable?.current_balance"></p>
                            </div>
                            <div class="text-emerald-400 hidden md:block">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            </div>
                            <div class="bg-white/10 p-3 rounded-lg flex-1 md:flex-none text-center">
                                <p class="text-[9px] uppercase opacity-70">Su Abono</p>
                                <p class="font-mono font-bold" x-text="'- {{ config('regional.currency_symbol') }}' + paymentAmount"></p>
                            </div>
                        </div>

                        <div class="w-full md:w-auto text-center md:text-right border-t md:border-t-0 border-white/10 pt-4 md:pt-0">
                            <span class="text-[10px] uppercase block text-emerald-400 font-bold">Nuevo Saldo Pendiente</span>
                            <span class="text-2xl md:text-3xl font-black font-mono" 
                                  :class="exceedsBalance ? 'text-red-400' : 'text-white'"
                                  x-text="'{{ config('regional.currency_symbol') }}' + (selectedReceivable?.current_balance - paymentAmount).toFixed(2)">
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 md:p-6 bg-gray-50 flex flex-col-reverse md:flex-row justify-end items-center gap-3 border-t">
                <a href="{{ route('finance.collections.index') }}" class="w-full md:w-auto text-center px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Cancelar</a>
                <x-primary-button 
                    class="w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 justify-center shadow-lg px-8 py-3"
                    x-bind:disabled="!isValidAmount || !selectedReceivableId">
                    <span x-text="paymentAmount >= (selectedReceivable?.current_balance || 0) ? 'Liquidar Factura' : 'Registrar Abono'"></span>
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>