@foreach($items as $item)
    <x-modal name="view-receivable-{{ $item->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl bg-white shadow-2xl">
            {{-- Header --}}
            <div class="bg-gray-50 px-8 py-6 border-b flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-black text-gray-900 tracking-tight">Detalle de Cuenta por Cobrar</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-mono text-zertix-primary-600 bg-zertix-primary-50 px-2 py-0.5 rounded border border-zertix-primary-100">
                            {{ $item->document_number }}
                        </span>
                        <span class="text-gray-300 text-xs">•</span>
                        <span class="text-xs text-gray-500 italic">ID: {{ $item->id }}</span>
                    </div>
                </div>

                {{-- Estado Dinámico desde el Modelo --}}
                <x-ui.badge :variant="match($item->status) {
                    \App\Models\Accounting\Receivable::STATUS_UNPAID => 'error',
                    \App\Models\Accounting\Receivable::STATUS_PARTIAL => 'warning',
                    \App\Models\Accounting\Receivable::STATUS_PAID => 'success',
                    \App\Models\Accounting\Receivable::STATUS_CANCELLED => 'slate',
                    default => 'info',
                }">
                    {{ strtoupper($item->status_label) }}
                </x-ui.badge>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Columna Izquierda --}}
                    <div class="space-y-6">
                        <div class="flex gap-3">
                            <div class="w-10 h-10 bg-zertix-primary-50 rounded-lg flex items-center justify-center text-zertix-primary-600 shrink-0">
                                <x-heroicon-s-user class="w-5 h-5"/>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Cliente</span>
                                <p class="text-sm font-bold text-gray-800">{{ $item->client->name }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 shrink-0">
                                <x-heroicon-s-calendar class="w-5 h-5"/>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Fecha Emisión</span>
                                <p class="text-sm font-semibold text-gray-700">{{ $item->emission_date->format('d/m/Y') }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <div class="w-10 h-10 {{ $item->is_overdue ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }} rounded-lg flex items-center justify-center shrink-0">
                                <x-heroicon-s-clock class="w-5 h-5"/>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Vencimiento</span>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-700">{{ $item->due_date->format('d/m/Y') }}</p>
                                    @if($item->is_overdue)
                                        <x-ui.badge variant="error" size="sm" :dot="false">Vencido</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Columna Derecha: Finanzas y Contabilidad --}}
                    <div class="space-y-6">
                        {{-- Card de Estado de Cuenta --}}
                        <div class="p-5 {{ $item->status === 'paid' ? 'bg-emerald-900' : 'bg-zertix-primary-900' }} rounded-2xl shadow-lg relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                            
                            <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest block mb-4">Resumen de Cobro</span>
                            
                            <div class="flex justify-between items-center mb-2 text-white/80">
                                <span class="text-xs">Monto Original:</span>
                                <span class="text-sm font-bold">{{ config('regional.currency_symbol') }}{{ number_format($item->total_amount, 2) }}</span>
                            </div>

                            @if($item->current_balance < $item->total_amount && $item->current_balance > 0)
                                <div class="flex justify-between items-center mb-2 text-emerald-400">
                                    <span class="text-xs">Total Abonado:</span>
                                    <span class="text-sm font-bold">-{{ config('regional.currency_symbol') }}{{ number_format($item->total_amount - $item->current_balance, 2) }}</span>
                                </div>
                            @endif
                            
                            <hr class="border-white/10 my-3">
                            
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-white font-bold uppercase">Saldo Actual:</span>
                                <span class="text-xl font-black text-white tracking-tight">{{ config('regional.currency_symbol') }}{{ number_format($item->current_balance, 2) }}</span>
                            </div>
                        </div>

                        {{-- Validación de Cuenta Contable — solo con accounting.advanced activo
                             (REQ-02.13), CxC en sí no depende de Contabilidad para nada más. --}}
                        @if (module_enabled('accounting.advanced'))
                            <div class="flex gap-3 px-1">
                                <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center text-gray-400 shrink-0">
                                    <x-heroicon-s-book-open class="w-5 h-5"/>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Cuenta Contable Destino</span>
                                    @if($item->client->accounting_account_id)
                                        {{-- Cuenta propia del cliente --}}
                                        <p class="text-xs font-mono text-zertix-primary-600">
                                            {{ $item->client->accountingAccount->code }} <br>
                                            <span class="font-sans font-bold text-gray-800">{{ $item->client->accountingAccount->name }}</span>
                                            <span class="block text-[9px] text-zertix-primary-400 font-sans italic underline decoration-zertix-primary-200">Cuenta específica del cliente</span>
                                        </p>
                                    @else
                                        {{-- Cuenta general de la CxC --}}
                                        <p class="text-xs font-mono text-gray-600">
                                            {{ $item->accountingAccount->code ?? '1.1.02' }} <br>
                                            <span class="font-sans font-bold text-gray-800">{{ $item->accountingAccount->name ?? 'Cuenta Por Cobrar General' }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Concepto --}}
                    <div class="col-span-1 md:col-span-2">
                        <div class="bg-gray-50 p-4 rounded-xl border border-dashed border-gray-200">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Concepto o Descripción</span>
                            <p class="text-sm text-gray-700 leading-relaxed italic">"{{ $item->description }}"</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between text-[11px] text-gray-400 uppercase font-bold tracking-tighter">
                    <span>Creado: {{ $item->created_at->format('d/m/Y H:i') }}</span>
                    <span>Último cambio: {{ $item->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t flex justify-end gap-3">
                <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cerrar</x-ui.button>
            </div>
        </div>
    </x-modal>

    {{-- Categoría C (docs/analisis/politica-soft-deletes.md) — CxC es bitácora de
         deuda del cliente, nunca se borra ni se archiva: sin modal de eliminación
         aquí a propósito. --}}
@endforeach