<x-app-layout>
    <div class=""
        x-cloak
        x-data="{
            isLoading: false,

            submitForm() {
                this.isLoading = true;
                this.$refs.configForm.submit();
            }
        }">

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <x-ui.toasts />

            <form x-ref="configForm" method="POST" action="{{ route('sales.pos.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- SECCIÓN 1: CLIENTE Y FLUJO --}}
                <section class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center gap-3 sm:gap-4">
                        <span class="flex-none w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">1</span>
                        <h2 class="font-bold text-slate-800 text-base sm:text-lg">Cliente y Operación</h2>
                    </div>
                    <div class="p-4 sm:p-8 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 gap-4 sm:gap-6">
                            <div>
                                <label class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase mb-2 block tracking-wider">Cliente por Defecto (Walk-in)</label>
                                <select name="default_walkin_customer_id" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl sm:rounded-2xl px-4 py-3 sm:px-5 sm:py-4 text-sm sm:text-base font-semibold text-slate-700 focus:border-indigo-400 transition-all">
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ $settings->default_walkin_customer_id == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl border border-slate-100">
                                <div class="flex-1 min-w-0 pr-3">
                                    <p class="text-xs sm:text-sm font-bold text-slate-700">Creación Rápida</p>
                                    <p class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">Permitir crear clientes desde el POS.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                    <input type="checkbox" name="allow_quick_customer_creation" value="1" class="sr-only peer" {{ $settings->allow_quick_customer_creation ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 sm:p-6 bg-amber-50/50 rounded-2xl sm:rounded-3xl border border-amber-100">
                            <div class="flex gap-3 sm:gap-4 flex-1 min-w-0">
                                <x-heroicon-s-document-duplicate class="w-6 h-6 sm:w-8 sm:h-8 text-amber-500 flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-amber-900">Cotizaciones sin Guardar</h4>
                                    <p class="text-[10px] sm:text-xs text-amber-700/70 mt-1">Permite imprimir o enviar cotizaciones sin que se descuente stock ni se genere una deuda en el sistema.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 self-end sm:self-auto">
                                <input type="checkbox" name="allow_quote_without_save" value="1" class="sr-only peer" {{ $settings->allow_quote_without_save ? 'checked' : '' }}>
                                <div class="w-14 h-7 bg-amber-200/50 peer-focus:ring-4 peer-focus:ring-amber-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-amber-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all"></div>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- SECCIÓN 2: IMPRESIÓN --}}
                <section class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center gap-3 sm:gap-4">
                        <span class="flex-none w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">2</span>
                        <h2 class="font-bold text-slate-800 text-base sm:text-lg">Configuración de Ticket</h2>
                    </div>
                    <div class="p-4 sm:p-8 space-y-4 sm:space-y-6">
                        <div>
                            <label class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase mb-2 sm:mb-3 block tracking-wider">Tamaño de Papel (Térmico)</label>
                            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                <label class="cursor-pointer group">
                                    <input type="radio" name="receipt_size" value="58mm" class="sr-only peer" {{ $settings->receipt_size == '58mm' ? 'checked' : '' }}>
                                    <div class="p-3 sm:p-4 border-2 border-slate-100 rounded-xl sm:rounded-2xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-center">
                                        <p class="text-sm sm:text-base font-bold text-slate-700 group-hover:text-indigo-600">58mm</p>
                                        <p class="text-[9px] sm:text-[10px] text-slate-400 italic mt-0.5">Formato pequeño</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="receipt_size" value="80mm" class="sr-only peer" {{ $settings->receipt_size == '80mm' ? 'checked' : '' }}>
                                    <div class="p-3 sm:p-4 border-2 border-slate-100 rounded-xl sm:rounded-2xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all text-center">
                                        <p class="text-sm sm:text-base font-bold text-slate-700 group-hover:text-indigo-600">80mm</p>
                                        <p class="text-[9px] sm:text-[10px] text-slate-400 italic mt-0.5">Estándar POS</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl border border-slate-100">
                            <div class="flex-1 min-w-0 pr-3">
                                <p class="text-xs sm:text-sm font-bold text-slate-700">Auto-imprimir Recibo</p>
                                <p class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">Lanza la impresión al cobrar.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="checkbox" name="auto_print_receipt" value="1" class="sr-only peer" {{ $settings->auto_print_receipt ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- BOTÓN FLOTANTE --}}
                <div class="sticky bottom-4 sm:bottom-6 bg-white/95 backdrop-blur-md border border-slate-200 p-3 sm:p-4 rounded-2xl sm:rounded-3xl shadow-2xl flex flex-col sm:flex-row items-stretch sm:items-center gap-3 z-[40]">
                    <a href="{{ route('sales.pos.terminals.index') }}" 
                       class="px-4 py-2 sm:px-6 sm:py-3 text-[10px] sm:text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest text-center sm:text-left">
                        ← Volver a Terminales
                    </a>

                    <button 
                        type="button"
                        @click="submitForm()"
                        x-bind:disabled="isLoading"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 sm:px-10 py-3 sm:py-4 bg-indigo-600 border border-transparent rounded-xl sm:rounded-2xl text-sm font-bold sm:font-semibold text-white uppercase tracking-widest shadow-lg shadow-indigo-200 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150">
                        
                        <span x-show="!isLoading" class="flex items-center gap-2">
                            <x-heroicon-s-check-circle class="w-5 h-5" />
                            <span class="hidden sm:inline">Aplicar Configuración</span>
                            <span class="sm:hidden">Guardar</span>
                        </span>
                        
                        <span x-show="isLoading" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Guardando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-config-layout>