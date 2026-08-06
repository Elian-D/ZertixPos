<x-modal name="confirm-convert-quote-{{ $quote->id }}" maxWidth="md">
    <form action="{{ route('sales.quotes.convert', $quote) }}" method="POST" class="p-6 text-left" x-data="{ paymentType: 'cash' }">
        @csrf
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                <x-heroicon-s-arrow-path class="w-6 h-6"/>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Finalizar Venta</h3>
                <p class="text-xs text-gray-500">Convertir Cotizacion #{{ $quote->id }}</p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-3 mb-6 flex justify-between items-center border border-gray-100">
            <span class="text-xs font-medium text-gray-600">Total a Facturar:</span>
            <span class="text-lg font-black text-indigo-700">{{ $config->currency_symbol ?? '$' }}{{ number_format($quote->total, 2) }}</span>
        </div>

        <div class="space-y-4">
            {{-- Tipo de Venta --}}
            <div>
                <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Condicion de Venta</label>
                <select name="payment_type" x-model="paymentType" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500">
                    <option value="cash">Contado</option>
                    <option value="credit">Credito (CxC)</option>
                </select>
            </div>

            {{-- Metodo de Pago --}}
            <div x-show="paymentType === 'cash'">
                <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Metodo de Pago</label>
                <select name="tipo_pago_id" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                    @forelse($tipo_pagos as $pago)
                        <option value="{{ $pago['id'] }}">{{ $pago['nombre'] }}</option>
                    @empty
                        <option disabled>No hay metodos de pago disponibles</option>
                    @endforelse
                </select>
            </div>

            {{-- Comprobante Fiscal --}}
            @if(module_enabled('sales.ncf'))
            <div>
                <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1">Tipo de Comprobante (NCF)</label>
                <select name="ncf_type_id" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                    <option value="">Sin Comprobante (Consumidor Final)</option>
                    @forelse($ncf_types as $ncf)
                        <option value="{{ $ncf['id'] }}">{{ $ncf['name'] }} ({{ $ncf['code'] }})</option>
                    @empty
                        <option disabled>No hay tipos NCF disponibles</option>
                    @endforelse
                </select>
            </div>
            @endif

            {{-- Almacen Oculto --}}
            <input type="hidden" name="warehouse_id" value="{{ $warehouses[0]['id'] ?? null }}">
        </div>

        <div class="mt-8 flex gap-3">
            <x-secondary-button class="flex-1 justify-center" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button type="submit" class="flex-1 justify-center bg-emerald-600 hover:bg-emerald-700">
                Confirmar Venta
            </x-primary-button>
        </div>
    </form>
</x-modal>