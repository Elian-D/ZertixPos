<x-app-layout>
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 text-[10px] uppercase tracking-wider font-bold">
                    <li class="inline-flex items-center text-gray-400">
                        <a href="{{ route('sales.pos.sessions.index') }}" class="hover:text-zertix-primary-600 transition">Turnos POS</a>
                    </li>
                    <x-heroicon-s-chevron-right class="w-3 h-3 text-gray-300" />
                    <li class="text-gray-500">Cierre de Turno #{{ $session->id }}</li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 bg-white shadow-sm rounded-3xl border border-gray-100 overflow-hidden">
                <x-form-header
                    title="Arqueo y Cierre de Caja"
                    subtitle="Turno #{{ $session->id }} - {{ $session->terminal->name ?? 'Terminal eliminada' }}" />

                <form action="{{ route('sales.pos.sessions.close', $session) }}"
                    method="POST"
                    class="p-6 sm:p-8"
                    x-data="{
                        expected: {{ $expected }},
                        real: '',
                        reason: '',
                        differenceNotes: '',

                        get difference() {
                            return this.real === '' ? 0 : (parseFloat(this.real) - this.expected).toFixed(2);
                        },

                        get hasDifference() {
                            return Math.abs(this.difference) >= 0.01;
                        },

                        get isReady() {
                            if (this.real === '') return false;
                            if (!this.hasDifference) return true;
                            if (this.reason === '') return false;
                            if (this.reason === 'otro' && this.differenceNotes.trim() === '') return false;
                            return true;
                        }
                    }">
                    @csrf
                    @method('PATCH')

                    {{-- RESUMEN DEL SISTEMA --}}
                    <div class="mb-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5 border-2 border-gray-200 space-y-3 shadow-sm">
                            <div class="flex justify-between text-sm font-medium text-gray-600">
                                <span>(+) Fondo Inicial:</span>
                                <span class="font-mono font-bold text-gray-800">{{ config('regional.currency_symbol') }}{{ number_format($session->opening_balance, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium text-green-600">
                                <span>(+) Ventas en Efectivo:</span>
                                <span class="font-mono font-bold">{{ config('regional.currency_symbol') }}{{ number_format($session->cash_sales, 2) }}</span>
                            </div>

                            {{-- Fase 6, REQ-6.7: sin esta línea, "Esperado en Caja" incluía los
                                 Cobros CxC en efectivo pero no lo explicaba — el cajero veía
                                 Fondo + Ventas sin que sumara el total, como si hubiera un error. --}}
                            @if($session->cash_collections > 0)
                                <div class="flex justify-between text-sm font-medium text-green-600">
                                    <span>(+) Cobros CxC en Efectivo:</span>
                                    <span class="font-mono font-bold">{{ config('regional.currency_symbol') }}{{ number_format($session->cash_collections, 2) }}</span>
                                </div>
                            @endif

                            <div class="pt-3 border-t-2 border-dashed border-gray-300 flex justify-between items-center">
                                <span class="text-sm font-black text-zertix-primary-900 uppercase tracking-wide">Esperado en Caja:</span>
                                <span class="font-mono text-2xl font-black text-zertix-primary-600" x-text="'{{ config('regional.currency_symbol') }}' + expected.toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>
                    </div>

                    {{-- INPUT DEL CAJERO --}}
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="closing_balance" value="Monto Real en Caja (Arqueo Físico)" class="font-bold" />
                            <div class="relative mt-2">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-400 font-bold text-lg">{{ config('regional.currency_symbol') }}</span>
                                </div>
                                <x-text-input
                                    id="closing_balance"
                                    name="closing_balance"
                                    type="number"
                                    step="0.01"
                                    x-model="real"
                                    class="pl-16 block w-full text-2xl font-black text-gray-800 bg-white focus:ring-zertix-primary-500 rounded-xl border-2"
                                    placeholder="0.00"
                                    required
                                    autofocus
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                Cuente los billetes y monedas antes de ingresar el monto
                            </p>
                        </div>

                        {{-- FEEDBACK DE DIFERENCIA --}}
                        <template x-if="real !== ''">
                            <div :class="difference == 0 ? 'bg-green-50 border-green-300 text-green-800' : (difference < 0 ? 'bg-red-50 border-red-300 text-red-800' : 'bg-amber-50 border-amber-300 text-amber-800')"
                                 class="p-4 rounded-xl border-2 flex justify-between items-center shadow-sm"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black uppercase tracking-widest opacity-70"
                                          x-text="difference == 0 ? 'Balance Perfecto' : (difference > 0 ? 'Sobrante (Overage)' : 'Faltante (Shortage)')"></span>
                                    <span class="text-2xl font-black font-mono mt-1" x-text="(difference > 0 ? '+' : '') + '{{ config('regional.currency_symbol') }}' + Math.abs(difference).toFixed(2)"></span>
                                </div>
                                <template x-if="difference == 0">
                                    <svg class="w-10 h-10 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </template>
                                <template x-if="difference != 0">
                                    <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </template>
                            </div>
                        </template>

                        {{-- JUSTIFICACIÓN DEL DESCUADRE: solo aparece si hay diferencia --}}
                        <div x-show="hasDifference"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             class="bg-white border-2 border-amber-200 rounded-xl p-4 space-y-3">
                            <p class="text-xs font-black text-amber-700 uppercase tracking-wide flex items-center gap-1">
                                <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                                Motivo del Descuadre (obligatorio)
                            </p>

                            <div>
                                <x-ui.forms.select name="difference_reason" x-model="reason"
                                    placeholder="Selecciona un motivo..." :error="$errors->first('difference_reason')">
                                    @foreach($reasons as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>

                            <div x-show="reason === 'otro'" x-transition>
                                <x-ui.forms.textarea name="difference_notes" x-model="differenceNotes" :rows="2"
                                    placeholder="Explica qué pasó con el efectivo..."
                                    :error="$errors->first('difference_notes')"></x-ui.forms.textarea>
                            </div>
                        </div>

                        <div>
                            <x-ui.forms.textarea label="Observaciones Generales (Opcional)" name="notes" id="notes" :rows="2"
                                placeholder="Cualquier otra novedad del turno, aparte del descuadre..."></x-ui.forms.textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3">
                        <x-ui.button
                            type="submit"
                            variant="primary"
                            class="w-full justify-center py-3 bg-zertix-primary-600 hover:bg-zertix-primary-700 shadow-lg shadow-zertix-primary-100 transition-all disabled:opacity-50"
                            x-bind:disabled="!isReady">
                            Finalizar Turno y Registrar Arqueo
                        </x-ui.button>
                        <a href="{{ route('sales.pos.sessions.index') }}"
                           class="w-full text-center py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition">
                            Volver sin cerrar
                        </a>
                    </div>
                </form>
            </div>

            {{-- Listado de ventas del turno, para verificar contra el efectivo contado sin
                 salir de la pantalla de cierre. Nota: esto NO es un desglose de billetes/
                 monedas (denominaciones) — esa es una idea a futuro, no está construida. --}}
            <div class="lg:col-span-1 bg-white shadow-sm rounded-3xl border border-gray-100 p-5 lg:sticky lg:top-6">
                <h3 class="text-xs font-black text-gray-800 mb-4 flex items-center gap-2 uppercase tracking-wide">
                    <x-heroicon-s-shopping-cart class="w-4 h-4 text-zertix-primary-500" />
                    Ventas del Turno
                </h3>

                @if(count($salesDetail))
                    <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                        @foreach($salesDetail as $row)
                            <div class="flex items-center justify-between text-xs border-b border-gray-50 pb-2">
                                <div>
                                    <span class="block font-bold text-gray-700">{{ $row['hora'] }}</span>
                                    <span class="block text-gray-400">{{ $row['metodo'] }}</span>
                                </div>
                                <span class="font-mono font-bold text-gray-800">{{ config('regional.currency_symbol') }}{{ number_format($row['total'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 italic">Sin ventas registradas en este turno todavía.</p>
                @endif
            </div>
            </div>
        </div>
    </div>
</x-app-layout>
