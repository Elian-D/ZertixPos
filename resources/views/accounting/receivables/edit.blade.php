<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4" 
         x-data="{ 
            clients: {{ $catalogs['clients']->toJson() }},
            allAccounts: {{ $catalogs['accounts']->toJson() }},
            defaultAccountId: '{{ $catalogs['defaultAccount']->id }}',
            selectedClientId: '{{ old('client_id', $item->client_id) }}',
            currentAccountId: '{{ old('accounting_account_id', $item->accounting_account_id) }}',
            emissionDate: '{{ $item->emission_date->format('Y-m-d') }}',
            paymentTerms: {{ $item->client->payment_terms ?? 0 }},
            isPartiallyPaid: {{ ($item->current_balance < $item->total_amount) ? 'true' : 'false' }},
            
            {{-- Propiedad computada para filtrar las cuentas --}}
            get filteredAccounts() {
                const client = this.clients.find(c => c.id == this.selectedClientId);
                if (!client) {
                    return this.allAccounts.filter(acc => acc.id == this.defaultAccountId);
                }
                // Permitimos la general y la específica del cliente
                return this.allAccounts.filter(acc => {
                    return acc.id == this.defaultAccountId || acc.id == client.accounting_account_id;
                });
            },

            get dueDate() {
                if (!this.emissionDate) return '';
                let date = new Date(this.emissionDate + 'T12:00:00');
                date.setDate(date.getDate() + parseInt(this.paymentTerms));
                return date.toISOString().split('T')[0];
            },

            updateClientData() {
                const client = this.clients.find(c => c.id == this.selectedClientId);
                this.paymentTerms = client ? client.payment_terms : 0;
                
                // Actualizar cuenta automáticamente al cambiar cliente
                if (client) {
                    this.currentAccountId = client.accounting_account_id 
                        ? client.accounting_account_id 
                        : this.defaultAccountId;
                }
            }
         }">
        
        <form action="{{ route('finance.receivables.update', $item) }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf
            @method('PUT')

            <x-ui.toasts />
            
            <x-form-header
                title="Editar CxC: {{ $item->document_number }}"
                subtitle="Gestione los detalles de la deuda. El monto y fechas están protegidos por integridad contable."
                :back-route="route('finance.receivables.index')" />

            <div class="p-8 space-y-8">
                {{-- ALERTA DE BLOQUEO POR PAGOS --}}
                <template x-if="isPartiallyPaid">
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6">
                        <div class="flex">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-400" />
                            <div class="ml-3">
                                <p class="text-sm text-amber-700">
                                    <strong>Aviso:</strong> Esta factura ya tiene abonos registrados. El monto total está bloqueado.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
                {{-- SECCIÓN 1: CLIENTE Y CUENTA --}}
                <section>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                        {{-- Cliente --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Cliente" />
                            <select name="client_id" 
                                    x-model="selectedClientId" 
                                    @change="updateClientData()"
                                    class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm" required>
                                <option value="">Seleccione un cliente...</option>
                                @foreach($catalogs['clients'] as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->name }} (Términos: {{ $client->payment_terms }} días)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Cuenta Contable (Dinamismo Alpine) --}}
                        <div class="md:col-span-2">
                            <x-input-label value="Cuenta Contable Destino" />
                            <select name="accounting_account_id" 
                                    x-model="currentAccountId"
                                    class="w-full mt-1 border-gray-300 bg-white rounded-md shadow-sm text-sm">
                                <template x-for="acc in filteredAccounts" :key="acc.id">
                                    <option :value="acc.id" x-text="`${acc.code} - ${acc.name}`" :selected="acc.id == currentAccountId"></option>
                                </template>
                            </select>
                            <p class="text-[10px] text-indigo-600 mt-1 italic">
                                <span x-show="filteredAccounts.length > 1">Este cliente posee una cuenta contable específica.</span>
                                <span x-show="filteredAccounts.length <= 1">Usando cuenta de control general 1.1.02.</span>
                            </p>
                        </div>

                        <div>
                            <x-input-label value="Número de Documento" />
                            <x-text-input name="document_number" class="w-full mt-1 font-mono uppercase" 
                                :value="old('document_number', $item->document_number)" required />
                        </div>

                        <div>
                            <x-input-label value="Estado del Registro" />
                            <select name="status" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                                @foreach($catalogs['statuses'] as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', $item->status) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                {{-- SECCIÓN 2: FECHAS Y MONTOS --}}
                <section>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Fecha Emisión: Protegida --}}
                        <div>
                            <x-input-label value="Fecha Emisión" />
                            <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-400 text-sm flex items-center gap-2">
                                <x-heroicon-o-lock-closed class="w-4 h-4" />
                                <span x-text="emissionDate"></span>
                            </div>
                            <input type="hidden" name="emission_date" :value="emissionDate">
                        </div>

                        {{-- Fecha Vencimiento: Recalculada --}}
                        <div>
                            <x-input-label value="Fecha Vencimiento" />
                            <div class="mt-1 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-md text-indigo-700 font-bold text-sm flex items-center gap-2">
                                <x-heroicon-s-clock class="w-4 h-4" />
                                <span x-text="dueDate"></span>
                            </div>
                            <input type="hidden" name="due_date" :value="dueDate">
                        </div>

                        {{-- Monto Total: Bloqueado si hay abonos --}}
                        <div>
                            <x-input-label value="Monto Total" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">{{ config('regional.currency_symbol') }}</span>
                                <input type="number" step="0.01" name="total_amount" 
                                    value="{{ old('total_amount', $item->total_amount) }}"
                                    :readonly="isPartiallyPaid"
                                    :class="isPartiallyPaid ? 'bg-gray-100 cursor-not-allowed text-gray-500' : ''"
                                    class="w-full pl-14 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500"
                                    required />
                            </div>
                        </div>
                    </div>
                </section>

                <div>
                    <x-input-label value="Concepto / Descripción" />
                    <x-text-input name="description" class="w-full mt-1" :value="old('description', $item->description)" required />
                </div>
            </div>
            
            {{-- Botones de acción --}}
            <div class="p-6 bg-gray-50 flex justify-end gap-3 border-t">
                <a href="{{ route('finance.receivables.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500">Volver</a>
                <x-primary-button class="bg-indigo-600 px-8">
                    Actualizar Cambios
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>