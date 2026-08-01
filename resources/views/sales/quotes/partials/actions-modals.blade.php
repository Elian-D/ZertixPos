@foreach($items as $quote)
    {{-- MODAL: CANCELAR --}}
    <x-modal name="confirm-cancel-quote-{{ $quote->id }}" maxWidth="sm">
        <form action="{{ route('sales.quotes.cancel', $quote) }}" method="POST" class="p-6 text-center">
            @csrf @method('PATCH')
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-s-x-mark class="w-8 h-8"/>
            </div>
            <h3 class="text-lg font-bold text-gray-900">¿Cancelar Cotización?</h3>
            <p class="text-xs text-gray-500 mb-4">Esta acción es irreversible.</p>
            
            <div class="text-left mb-4">
                <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Motivo / Observación</label>
                <textarea name="reason" rows="2" required 
                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500"
                placeholder="Indique por qué se cancela..."></textarea>
            </div>
            
            <div class="flex justify-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Volver</x-secondary-button>
                <x-danger-button type="submit">Confirmar Cancelación</x-danger-button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL: APROBAR --}}
    <x-modal name="confirm-approve-quote-{{ $quote->id }}" maxWidth="sm">
        <form action="{{ route('sales.quotes.approve', $quote) }}" method="POST" class="p-6 text-center">
            @csrf @method('PATCH')
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <x-heroicon-s-check class="w-8 h-8"/>
            </div>
            <h3 class="text-lg font-bold text-gray-900">¿Aprobar Cotización?</h3>
            <p class="text-xs text-gray-500 mb-6">El cliente podrá proceder con el pago tras la aprobación.</p>
            
            <div class="flex justify-center gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition shadow-lg shadow-emerald-100">
                    Aprobar Ahora
                </button>
            </div>
        </form>
    </x-modal>

    {{-- MODAL: CONVERTIR A VENTA --}}
    <x-modal name="confirm-convert-quote-{{ $quote->id }}" maxWidth="md">
        <form action="{{ route('sales.quotes.convert', $quote) }}" method="POST" class="p-6 text-left" x-data="{ paymentType: 'cash' }">
            @csrf
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                    <x-heroicon-s-arrow-path class="w-6 h-6"/>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Finalizar Venta</h3>
                    <p class="text-xs text-gray-500">Convertir Cotización #{{ $quote->id }}</p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-3 mb-6 flex justify-between items-center border border-gray-100">
                <span class="text-xs font-medium text-gray-600">Total a Facturar:</span>
                <span class="text-lg font-black text-indigo-700">{{ number_format($quote->total, 2) }}</span>
            </div>

            <div class="space-y-4">
                {{-- Tipo de Venta --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Condición de Venta</label>
                    <select name="payment_type" x-model="paymentType" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500">
                        <option value="cash">Contado</option>
                        <option value="credit">Crédito (CxC)</option>
                    </select>
                </div>

                {{-- Método de Pago --}}
                <div x-show="paymentType === 'cash'">
                    <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Método de Pago</label>
                    <select name="tipo_pago_id" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                        @foreach($tipo_pagos as $pago)
                            <option value="{{ $pago['id'] }}">{{ $pago['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Comprobante Fiscal --}}
                @if(general_config()->usa_ncf)
                <div>
                    <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Tipo de Comprobante (NCF)</label>
                    <select name="ncf_type_id" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                        <option value="">Sin Comprobante (Consumidor Final)</option>
                        @foreach($ncf_types as $ncf)
                            <option value="{{ $ncf['id'] }}">{{ $ncf['name'] }} ({{ $ncf['code'] }})</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Almacén Oculto --}}
                <input type="hidden" name="warehouse_id" value="{{ $quote->pos_terminal_id ? $quote->terminal->warehouse_id : $warehouses->first()['id'] }}">
            </div>

            <div class="mt-8 flex gap-3">
                <x-secondary-button class="flex-1 justify-center" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="flex-1 justify-center bg-indigo-600 hover:bg-indigo-700">
                    Confirmar Venta
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    
@endforeach