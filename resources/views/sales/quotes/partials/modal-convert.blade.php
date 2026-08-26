<x-modal name="confirm-convert-quote-{{ $quote->id }}" maxWidth="md">
    <form action="{{ route('clients.quotes.convert', $quote) }}" method="POST" class="p-6 text-left"
          x-data="{
              paymentType: 'cash',
              selectedTipoPagoId: {{ optional($tipo_pagos->first())->id ?? 'null' }},
              tipoPagos: @js($tipo_pagos),
              // Fase 6, REQ-6.9: Efectivo/Tarjeta no necesitan referencia.
              get isCashOrCardMethod() {
                  const tp = this.tipoPagos.find(t => t.id == this.selectedTipoPagoId);
                  return tp ? ['efectivo', 'tarjeta'].includes(tp.slug) : false;
              },
          }">
        @csrf
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-zertix-primary-100 text-zertix-primary-600 rounded-full flex items-center justify-center">
                <x-heroicon-s-arrow-path class="w-6 h-6"/>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Finalizar Venta</h3>
                <p class="text-xs text-gray-500">Convertir Cotizacion #{{ $quote->id }}</p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-3 mb-6 flex justify-between items-center border border-gray-100">
            <span class="text-xs font-medium text-gray-600">Total a Facturar:</span>
            {{-- grand_total (neto + impuesto) — no total (sin impuesto), para que lo que
                 se confirma acá coincida con lo que termina facturado (Fase 5, REQ-5.12). --}}
            <span class="text-lg font-black text-zertix-primary-700">{{ config('regional.currency_symbol') }}{{ number_format($quote->grand_total, 2) }}</span>
        </div>

        <div class="space-y-4">
            {{-- Tipo de Venta --}}
            <div>
                <x-ui.forms.select label="Condicion de Venta" name="payment_type" x-model="paymentType" placeholder="">
                    <option value="cash">Contado</option>
                    {{-- Consumidor Final nunca es creditable (REQ-2.3) — no tiene
                         identidad real a quien cobrarle después. --}}
                    @if($quote->customer_id != 1)
                        <option value="credit">Credito (CxC)</option>
                    @endif
                </x-ui.forms.select>
            </div>

            {{-- Metodo de Pago --}}
            <div x-show="paymentType === 'cash'">
                <x-ui.forms.select label="Metodo de Pago" name="tipo_pago_id" x-model="selectedTipoPagoId" placeholder="">
                    @forelse($tipo_pagos as $pago)
                        <option value="{{ $pago['id'] }}">{{ $pago['nombre'] }}</option>
                    @empty
                        <option disabled>No hay metodos de pago disponibles</option>
                    @endforelse
                </x-ui.forms.select>
            </div>

            {{-- Referencia: oculta para Efectivo/Tarjeta, opcional en el resto — nunca
                 bloquea el envío (Fase 6, REQ-6.9). Único de los 4 puntos donde el campo
                 no existía en absoluto antes de esta fase. --}}
            <div x-show="paymentType === 'cash' && !isCashOrCardMethod" x-cloak>
                <x-ui.forms.input label="Referencia (Opcional)" type="text" name="reference"
                       placeholder="Últimos 4 dígitos, # de autorización, # de cheque…" />
            </div>

            {{-- Comprobante Fiscal --}}
            @if(module_enabled('sales.ncf'))
            <div>
                <x-ui.forms.select label="Tipo de Comprobante (NCF)" name="ncf_type_id" placeholder=""
                    hint="Deje 'Sin Comprobante' para una venta interna sin impacto fiscal">
                    <option value="">Sin Comprobante (Consumidor Final)</option>
                    @forelse($ncf_types as $ncf)
                        <option value="{{ $ncf['id'] }}">{{ $ncf['name'] }} ({{ $ncf['code'] }})</option>
                    @empty
                        <option disabled>No hay tipos NCF disponibles</option>
                    @endforelse
                </x-ui.forms.select>
            </div>
            @endif

            {{-- Almacen Oculto --}}
            <input type="hidden" name="warehouse_id" value="{{ $warehouses[0]['id'] ?? null }}">
        </div>

        <div class="mt-8 flex gap-3">
            <x-ui.button appearance="ghost" variant="secondary" class="flex-1 justify-center" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
            <x-ui.button type="submit" variant="primary" class="flex-1 justify-center">
                Confirmar Venta
            </x-ui.button>
        </div>
    </form>
</x-modal>