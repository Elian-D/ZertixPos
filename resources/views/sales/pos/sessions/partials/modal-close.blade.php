{{-- resources/views/sales/pos/sessions/partials/modal-close.blade.php --}}

@foreach($sessions->where('status', \App\Models\Sales\Pos\PosSession::STATUS_OPEN) as $session)
<x-modal name="close-session-{{ $session->id }}" maxWidth="lg">
    <x-form-header 
        title="Arqueo y Cierre de Caja" 
        subtitle="Sesión #{{ $session->id }} - {{ $session->terminal->name ?? 'Terminal eliminada' }}" />

    <form action="{{ route('sales.pos.sessions.close', $session) }}" 
        method="POST" 
        class="p-6"
        x-data="{ 
            terminalId: {{ $session->terminal_id }},
            requiresPin: {{ $session->terminal?->requires_pin ? 'true' : 'false' }},
            expected: {{ $session->calculateExpected() }}, 
            real: '',
            pin: '',
            
            isVerified: false,
            loading: false,
            errorMessage: '',
            
            get difference() { 
                return this.real === '' ? 0 : (parseFloat(this.real) - this.expected).toFixed(2);
            },
            
            get isReady() {
                const securityCheck = this.requiresPin ? this.isVerified : true;
                return securityCheck && this.real !== '';
            },

            async verifyPin() {
                if (this.pin.length !== 4) return;
                this.loading = true;
                this.errorMessage = '';

                try {
                    const response = await fetch('{{ route('sales.pos.verify-pin') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            terminal_id: this.terminalId,
                            pin: this.pin
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.isVerified = true;
                        this.errorMessage = '';
                    } else {
                        this.isVerified = false;
                        this.errorMessage = data.message || 'PIN Incorrecto';
                        this.pin = '';
                    }
                } catch (error) {
                    this.errorMessage = 'Error de conexión';
                } finally {
                    this.loading = false;
                }
            }
        }"
        x-init="if(!requiresPin) isVerified = true;">
        @csrf
        @method('PATCH')
        
        {{-- FASE 1: BLOQUEO DE SEGURIDAD (REDISEÑADO) --}}
        <div x-show="requiresPin && !isVerified" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl border-2 border-slate-200 overflow-hidden">
            
            {{-- Header --}}
            <div class="bg-slate-800 px-4 py-3 flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white">Autorización Requerida</h4>
                    <p class="text-xs text-slate-300">Ingrese el PIN para realizar el arqueo</p>
                </div>
            </div>

            {{-- Contenido --}}
            <div class="p-6 space-y-4">
                <div class="flex justify-center">
                    <input 
                        type="password" 
                        x-model="pin" 
                        @input="if(pin.length === 4) verifyPin()"
                        maxlength="4"
                        inputmode="numeric"
                        placeholder="••••"
                        class="w-48 text-center text-4xl tracking-[0.8em] font-mono font-bold bg-white border-2 border-slate-300 rounded-xl shadow-inner focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all py-3"
                        :class="errorMessage ? 'border-red-300 shake' : ''"
                        :disabled="loading"
                        autocomplete="off"
                        autofocus
                    >
                </div>

                <div x-show="loading" 
                     x-transition
                     class="flex items-center justify-center gap-2 text-indigo-600">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium">Verificando credenciales...</span>
                </div>

                <div x-show="errorMessage" 
                     x-transition
                     class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-2">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-800" x-text="errorMessage"></p>
                        <p class="text-xs text-red-600 mt-1">Por favor, intente nuevamente</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- FASE 2: ARQUEO CIEGO --}}
        <div x-show="isVerified" 
             x-transition:enter="transition ease-out duration-300 transform" 
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            
            {{-- RESUMEN DEL SISTEMA (CORREGIDO - Sin blur cuando se agrega monto) --}}
            <div class="relative mb-6 transition-all duration-500"
                 :class="real === '' ? 'opacity-40' : 'opacity-100'">
                
                {{-- Overlay solo cuando NO hay monto --}}
                <div x-show="real === ''" 
                     class="absolute inset-0 z-10 flex items-center justify-center backdrop-blur-[2px] bg-white/30 rounded-2xl">
                    <span class="bg-white px-4 py-2 rounded-full text-xs font-bold text-gray-600 shadow-lg border border-gray-200">
                        Ingrese el efectivo para ver la comparativa
                    </span>
                </div>

                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5 border-2 border-gray-200 space-y-3 shadow-sm">
                    <div class="flex justify-between text-sm font-medium text-gray-600">
                        <span>(+) Fondo Inicial:</span>
                        <span class="font-mono font-bold text-gray-800">${{ number_format($session->opening_balance, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-medium text-blue-600">
                        <span>(+) Entradas Manuales:</span>
                        <span class="font-mono font-bold">${{ number_format($session->cashMovements()->in()->sum('amount'), 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-medium text-red-500">
                        <span>(-) Salidas/Gastos:</span>
                        <span class="font-mono font-bold">(${{ number_format($session->cashMovements()->out()->sum('amount'), 2) }})</span>
                    </div>
                    
                    <div class="pt-3 border-t-2 border-dashed border-gray-300 flex justify-between items-center">
                        <span class="text-sm font-black text-indigo-900 uppercase tracking-wide">Esperado en Caja:</span>
                        <span class="font-mono text-2xl font-black text-indigo-600" x-text="'$' + expected.toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>
            </div>

            {{-- INPUT DEL CAJERO --}}
            <div class="space-y-4">
                <div>
                    <x-input-label for="closing_balance_{{ $session->id }}" value="Monto Real en Caja (Arqueo Físico)" class="font-bold" />
                    <div class="relative mt-2">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-400 font-bold text-lg">$</span>
                        </div>
                        <x-text-input 
                            id="closing_balance_{{ $session->id }}" 
                            name="closing_balance" 
                            type="number" 
                            step="0.01" 
                            x-model="real"
                            class="pl-10 block w-full text-2xl font-black text-gray-800 bg-white focus:ring-indigo-500 rounded-xl border-2" 
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
                            <span class="text-2xl font-black font-mono mt-1" x-text="(difference > 0 ? '+' : '') + '$' + Math.abs(difference).toFixed(2)"></span>
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

                <div>
                    <x-input-label for="notes_{{ $session->id }}" value="Observaciones del Cierre" />
                    <textarea name="notes" id="notes_{{ $session->id }}" rows="2" 
                        class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                        placeholder="Escriba aquí si hubo alguna novedad con el efectivo..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <x-primary-button 
                    class="w-full justify-center py-3 bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all disabled:opacity-50"
                    x-bind:disabled="!isReady">
                    Finalizar Sesión y Registrar Arqueo
                </x-primary-button>
                <x-secondary-button x-on:click="$dispatch('close')" class="w-full justify-center">
                    Seguir Operando
                </x-secondary-button>
            </div>
        </div>
    </form>
</x-modal>
@endforeach

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}
.shake {
    animation: shake 0.3s ease-in-out;
}
</style>