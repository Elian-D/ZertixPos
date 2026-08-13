<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4" 
         x-data="{ 
            clients: {{ $clients->toJson() }},
            selectedClientId: '{{ old('client_id', '') }}',
            emissionDate: '{{ date('Y-m-d') }}',
            paymentTerms: 0,
            
            get dueDate() {
                if (!this.emissionDate) return '';
                let date = new Date(this.emissionDate + 'T12:00:00');
                date.setDate(date.getDate() + parseInt(this.paymentTerms));
                return date.toISOString().split('T')[0];
            },

            updateClientData() {
                const client = this.clients.find(c => c.id == this.selectedClientId);
                this.paymentTerms = client ? client.payment_terms : 0;
            }
         }" 
         x-init="updateClientData()">
        
        <form action="{{ route('accounting.receivables.store') }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf

            <x-ui.toasts />
            
            <x-form-header
                title="Nueva Cuenta por Cobrar"
                subtitle="Registro administrativo de deudas. Las fechas se calculan según los términos del cliente."
                :back-route="route('accounting.receivables.index')" />

            <div class="p-8 space-y-8">
                
                {{-- Un solo contenedor x-data para todo el formulario --}}
                <div x-data="{ 
                    selectedClientId: '{{ old('client_id') }}',
                    clients: {{ $clients->toJson() }},
                    allAccounts: {{ $accounts->toJson() }}, {{-- Todas las cuentas del nodo 1.1.02 --}}
                    defaultAccountId: '{{ $defaultAccount->id }}',
                    currentAccountId: '{{ old('accounting_account_id', $defaultAccount->id) }}',
                    emissionDate: '{{ old('emission_date', date('Y-m-d')) }}',
                    dueDate: '{{ old('due_date') }}',

                    {{-- Propiedad computada para obtener solo las cuentas válidas para el contexto --}}
                    get filteredAccounts() {
                        const client = this.clients.find(c => c.id == this.selectedClientId);
                        
                        // Si no hay cliente, solo permitimos la general
                        if (!client) {
                            return this.allAccounts.filter(acc => acc.id == this.defaultAccountId);
                        }

                        // Si hay cliente, permitimos la general Y la cuenta específica del cliente (si tiene)
                        return this.allAccounts.filter(acc => {
                            return acc.id == this.defaultAccountId || acc.id == client.accounting_account_id;
                        });
                    },
                    
                    updateClientData() {
                        const client = this.clients.find(c => c.id == this.selectedClientId);
                        if (client) {
                            // Asignación automática de cuenta
                            this.currentAccountId = client.accounting_account_id 
                                ? client.accounting_account_id 
                                : this.defaultAccountId;

                            // Cálculo de fechas por términos de pago
                            if (this.emissionDate) {
                                let date = new Date(this.emissionDate + 'T12:00:00'); // Evitar desfase de zona horaria
                                date.setDate(date.getDate() + parseInt(client.payment_terms || 0));
                                this.dueDate = date.toISOString().split('T')[0];
                            }
                        }
                    }
                }" x-init="if(selectedClientId) updateClientData()">

                    <section>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider flex items-center gap-2 mb-4">
                            <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px]">1</span>
                            Información del Cliente y Factura
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                            
                            {{-- SELECT DE CLIENTE --}}
                            <div class="md:col-span-2">
                                <x-input-label value="Seleccionar Cliente" />
                                <select name="client_id" 
                                        x-model="selectedClientId" 
                                        @change="updateClientData()"
                                        class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                                    <option value="">Seleccione un cliente...</option>
                                    <template x-for="client in clients" :key="client.id">
                                        <option :value="client.id" x-text="`${client.name} (Saldo: ${client.balance} / Limite: ${client.credit_limit})`"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                            </div>

                            {{-- SELECT DE CUENTA CONTABLE FILTRADO --}}
                            <div class="md:col-span-2">
                                <x-input-label value="Cuenta Contable de la Deuda" />
                                <select name="accounting_account_id" 
                                        x-model="currentAccountId"
                                        class="w-full mt-1 border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    <template x-for="acc in filteredAccounts" :key="acc.id">
                                        <option :value="acc.id" x-text="`${acc.code} - ${acc.name}`" :selected="acc.id == currentAccountId"></option>
                                    </template>
                                </select>
                                <p class="text-[10px] text-indigo-600 mt-1 italic">
                                    <span x-show="filteredAccounts.length > 1">Este cliente tiene una cuenta contable específica asignada.</span>
                                    <span x-show="filteredAccounts.length <= 1">Usando cuenta de control general.</span>
                                </p>
                            </div>

                            {{-- DOCUMENTO Y DESCRIPCIÓN --}}
                            <div>
                                <x-input-label value="Número de Factura / Documento" />
                                <x-text-input name="document_number" class="w-full mt-1 font-mono uppercase" :value="old('document_number')" placeholder="Ej: FAC-0001" required />
                            </div>

                            <div>
                                <x-input-label value="Concepto o Descripción" />
                                <x-text-input name="description" class="w-full mt-1" :value="old('description')" placeholder="Ej: Saldo Inicial" required />
                            </div>
                        </div>
                    </section>
                </div>

                {{-- SECCIÓN 2: FECHAS Y MONTOS --}}
                <section>
                    <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px]">2</span>
                        Detalles Financieros (Calculados)
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Fecha Emisión: Solo lectura visual + input hidden para el POST --}}
                        <div>
                            <x-input-label value="Fecha Emisión" />
                            <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-500 text-sm flex items-center gap-2">
                                <x-heroicon-o-calendar class="w-4 h-4" />
                                <span x-text="emissionDate"></span>
                            </div>
                            <input type="hidden" name="emission_date" :value="emissionDate">
                        </div>

                        {{-- Fecha Vencimiento: Calculada dinámicamente --}}
                        <div>
                            <x-input-label value="Fecha Vencimiento" />
                            <div class="mt-1 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-md text-indigo-700 font-bold text-sm flex items-center gap-2">
                                <x-heroicon-s-clock class="w-4 h-4" />
                                <span x-text="dueDate"></span>
                            </div>
                            <input type="hidden" name="due_date" :value="dueDate">
                            <p class="text-[10px] text-gray-400 mt-1 uppercase">Calculado según términos del cliente</p>
                        </div>

                        <div>
                            <x-input-label value="Monto Total" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">{{ config('regional.currency_symbol') }}</span>
                                <x-text-input type="number" step="0.01" name="total_amount" class="w-full pl-14"
                                    :value="old('total_amount')" placeholder="0.00" required />
                            </div>
                            <x-input-error :messages="$errors->get('total_amount')" class="mt-2" />
                        </div>
                    </div>
                </section>
            </div>

            <div class="p-6 bg-gray-100 flex justify-end items-center gap-3 border-t">
                <a href="{{ route('accounting.receivables.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Cancelar</a>
                <x-primary-button class="bg-indigo-600 shadow-lg px-8">
                    Registrar Cuenta por Cobrar
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>