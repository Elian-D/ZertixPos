{{-- resources/views/sales/pos/sessions/partials/modal-open.blade.php --}}
<x-modal name="open-session-modal" maxWidth="md">
    <x-form-header 
        title="Apertura de Caja" 
        subtitle="Inicie una nueva sesión para comenzar a facturar." />

    <form action="{{ route('sales.pos.sessions.store') }}" 
          method="POST" 
          class="p-6"
          x-data="{ 
            terminalId: '',
            balance: 0,
            pin: '',
            isVerified: false,
            loading: false,
            errorMessage: '',
            
            terminalsConfig: @js($available_terminals->pluck('requires_pin', 'id')),

            get needsPin() { 
                return this.terminalsConfig[this.terminalId] === 1 || this.terminalsConfig[this.terminalId] === true;
            },

            get isReady() { 
                const securityCleared = this.needsPin ? this.isVerified : (this.terminalId !== '');
                return securityCleared && this.terminalId !== '' && this.balance >= 0 && !this.loading;
            },

            handleTerminalChange() {
                this.isVerified = false;
                this.pin = '';
                this.errorMessage = '';
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
                        this.errorMessage = data.message || 'PIN incorrecto';
                        this.pin = '';
                    }
                } catch (error) {
                    this.errorMessage = 'Error de comunicación con el servidor';
                } finally {
                    this.loading = false;
                }
            }
          }">
        @csrf
        
        <div class="space-y-5">
            {{-- 1. Selección de Terminal --}}
            <div>
                <x-input-label for="terminal_id" value="Seleccionar Terminal / Punto de Venta" />
                <select name="terminal_id" id="terminal_id" x-model="terminalId"
                    @change="handleTerminalChange()"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm" required>
                    <option value="">Seleccione una terminal disponible...</option>
                    @foreach($available_terminals as $terminal)
                        <option value="{{ $terminal->id }}">
                            {{ $terminal->name }} ({{ $terminal->warehouse->name ?? 'Sin Almacén' }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 2. Validación de PIN (REDISEÑADO) --}}
            <div x-show="needsPin && !isVerified" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl border-2 border-slate-200 overflow-hidden">
                
                {{-- Header --}}
                <div class="bg-slate-800 px-4 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Verificación de Seguridad</h4>
                        <p class="text-xs text-slate-300">Ingrese el PIN de 4 dígitos</p>
                    </div>
                </div>

                {{-- Contenido --}}
                <div class="p-6 space-y-4">
                    {{-- Input PIN --}}
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
                        >
                    </div>

                    {{-- Loading State --}}
                    <div x-show="loading" 
                         x-transition
                         class="flex items-center justify-center gap-2 text-indigo-600">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">Validando credenciales...</span>
                    </div>

                    {{-- Error Message --}}
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

            {{-- Check de Verificado --}}
            <div x-show="isVerified" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 class="flex items-center gap-3 text-emerald-700 bg-emerald-50 p-4 rounded-xl border-2 border-emerald-200 shadow-sm">
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm font-bold">Terminal verificada - Puede continuar</span>
            </div>

            {{-- 3. Fondo de Caja Inicial --}}
            <div x-show="terminalId && (!needsPin || isVerified)" 
                 x-transition:enter="transition ease-out duration-200">
                <x-input-label for="opening_balance" value="Monto Inicial (Fondo de Caja)" />
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-lg font-bold">$</span>
                    </div>
                    <x-text-input 
                        id="opening_balance" 
                        name="opening_balance" 
                        type="number" 
                        step="0.01" 
                        x-model="balance"
                        class="pl-9 block w-full bg-white font-bold text-xl text-emerald-700 rounded-lg" 
                        placeholder="0.00" 
                        required 
                    />
                </div>
            </div>

            {{-- 4. Notas --}}
            <div x-show="terminalId && (!needsPin || isVerified)" x-transition>
                <x-input-label for="notes" value="Observaciones de Apertura" />
                <textarea name="notes" id="notes" rows="2" 
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm" 
                    placeholder="Escriba aquí cualquier observación pertinente..."></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t pt-4">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button 
                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-30 transition-all"
                x-bind:disabled="!isReady">
                Confirmar Apertura
            </x-primary-button>
        </div>
    </form>
</x-modal>

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