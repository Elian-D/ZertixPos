@foreach($clients as $client)
{{-- MODAL: DETALLES COMPLETOS --}}
    <x-modal name="view-client-{{ $client->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con degradado suave y Estado --}}
            <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b relative">
                <div class="flex justify-between items-start">
                    <div class="flex gap-4 items-center">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 shadow-sm">
                            @if($client->type === 'company')
                                <x-heroicon-s-building-office class="w-7 h-7"/>
                            @else
                                <x-heroicon-s-user class="w-7 h-7"/>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $client->name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-semibold px-2 py-0.5 bg-gray-200 text-gray-700 rounded uppercase tracking-wider">
                                    {{ $client->type === 'individual' ? 'Físico' : 'Jurídico' }}
                                </span>
                                @if($client->commercial_name)
                                    <span class="text-gray-400 text-xs">•</span>
                                    <span class="text-sm text-gray-500 font-medium italic">{{ $client->commercial_name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ring-1 ring-inset shadow-sm {{ $client->estadoCliente->clase_fondo }} {{ $client->estadoCliente->clase_texto }} ring-black/5">
                        <span class="w-1.5 h-1.5 rounded-full mr-2 bg-current animate-pulse"></span>
                        {{ $client->estadoCliente->nombre }}
                    </span>
                </div>
            </div>

            <div class="p-8 bg-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Columna Izquierda: Identidad y Finanzas --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-identification class="w-4 h-4"/> Identificación y Fiscal
                            </h4>
                            <div class="space-y-3">
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-[10px] text-gray-400 uppercase font-bold block">Tipo y Documento</span>
                                    <p class="text-sm font-semibold text-gray-700">
                                        {{ $client->tax_label }}:
                                        <span class="text-indigo-600 ml-1 font-mono">{{ $client->tax_id ?? 'N/A' }}</span>
                                    </p>
                                </div>
                                @if (module_enabled('accounting.advanced'))
                                    <div class="bg-indigo-50/30 p-3 rounded-lg border border-indigo-100/50">
                                        <span class="text-[10px] text-indigo-400 uppercase font-bold block">Cuenta Contable</span>
                                        <p class="text-sm font-bold text-indigo-900">
                                            {{ $client->accountingAccount->name ?? 'Cuentas por Cobrar Clientes' }}
                                            <span class="block text-[10px] font-normal text-indigo-500">{{ $client->accountingAccount->code ?? '1102-01' }}</span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-banknotes class="w-4 h-4"/> Información de Crédito
                            </h4>

                            @if($client->credit_limit > 0)
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Saldo --}}
                                    <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm relative overflow-hidden">
                                        @if($client->balance > $client->credit_limit)
                                            <div class="absolute top-0 right-0 bg-red-600 text-white text-[8px] px-2 py-0.5 font-black uppercase">Riesgo</div>
                                        @endif
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Saldo Actual</span>
                                        <p class="text-sm font-black {{ $client->balance > $client->credit_limit ? 'text-red-600' : ($client->balance > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                            {{ config('regional.currency_symbol') }}{{ number_format($client->balance, 2) }}
                                        </p>
                                    </div>

                                    {{-- Límite --}}
                                    <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Límite Autorizado</span>
                                        <p class="text-sm font-bold text-gray-700">{{ config('regional.currency_symbol') }}{{ number_format($client->credit_limit, 2) }}</p>
                                    </div>

                                    {{-- Alerta de Exceso (Solo si aplica) --}}
                                    @if($client->balance > $client->credit_limit)
                                        <div class="col-span-2 bg-red-50 border border-red-100 p-2 rounded flex items-center gap-2">
                                            <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-red-600"/>
                                            <span class="text-[10px] font-bold text-red-700 uppercase">El cliente ha superado su límite por {{ config('regional.currency_symbol') }}{{ number_format($client->balance - $client->credit_limit, 2) }}</span>
                                        </div>
                                    @endif

                                    {{-- Términos --}}
                                    <div class="col-span-2 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Términos de Pago</span>
                                        <p class="text-sm font-semibold text-gray-700">{{ $client->payment_terms }} Días netos</p>
                                    </div>
                                </div>
                            @else
                                {{-- ESTADO: NO ADMITE CRÉDITO --}}
                                <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl p-6 text-center">
                                    <x-heroicon-s-no-symbol class="w-8 h-8 text-gray-300 mx-auto mb-2"/>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tight">No admite crédito</p>
                                    <p class="text-[10px] text-gray-400 mt-1">Este cliente está configurado para transacciones de contado exclusivamente.</p>
                                </div>
                            @endif
                        </section>
                    </div>

                    {{-- Columna Derecha: Ubicación y Contacto --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-map-pin class="w-4 h-4"/> Ubicación
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-2">
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Provincia y Ciudad</span>
                                    <p class="text-sm font-medium">{{ $client->provincia->name ?? 'N/A' }}, {{ $client->municipio->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Dirección</span>
                                    <p class="text-sm text-gray-600 leading-snug">{{ $client->address }}</p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <x-heroicon-s-phone class="w-4 h-4"/> Contacto y Registro
                            </h4>
                            <div class="space-y-3 px-1">
                                <div class="flex items-center gap-3">
                                    <x-heroicon-s-envelope class="w-4 h-4 text-gray-300"/>
                                    <a href="mailto:{{ $client->email }}" class="text-sm text-indigo-600 hover:underline">{{ $client->email ?? 'N/A' }}</a>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-heroicon-s-device-phone-mobile class="w-4 h-4 text-gray-300"/>
                                    <span class="text-sm text-gray-700">{{ $client->phone ?? 'N/A' }}</span>
                                </div>
                                <hr class="border-gray-100">
                                <div class="flex justify-between text-[11px] text-gray-400">
                                    <span>Creado: {{ $client->created_at->format('d/m/Y') }}</span>
                                    <span>Actualizado: {{ $client->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-10 pt-6 border-t flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-[10px] text-gray-300 uppercase tracking-tighter font-mono">ID: {{ $client->id }}</div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <x-secondary-button x-on:click="$dispatch('close')" class="flex-1 sm:flex-none justify-center">
                            Cerrar
                        </x-secondary-button>
                        <a href="{{ route('clients.edit', $client) }}" class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition duration-150">
                            <x-heroicon-s-pencil class="w-4 h-4 mr-2" /> Editar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

    <x-ui.confirm-deletion-modal 
    :id="$client->id"
    :title="'¿Eliminar Cliente?'"
    :itemName="$client->name"
    :type="'el cliente'"
    :route="route('clients.destroy', $client)"
    >
    <strong>Aviso:</strong> Esta operación se puede deshacer desde la papelera.
    </x-ui.confirm-deletion-modal>
    @endforeach

    