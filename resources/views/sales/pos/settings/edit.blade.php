<x-app-layout>
    <div class="" x-cloak>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <form method="POST" action="{{ route('sales.pos.settings.update') }}" class="space-y-6">
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
                                <x-ui.forms.select label="Cliente por Defecto (Walk-in)" name="default_walkin_customer_id" placeholder="">
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ $settings->default_walkin_customer_id == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>

                            <div class="p-3 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl border border-slate-100">
                                <x-ui.forms.toggle label="Creación Rápida" name="allow_quick_customer_creation"
                                    :checked="$settings->allow_quick_customer_creation"
                                    description="Permitir crear clientes desde el POS." />
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
                            <div class="flex-shrink-0 self-end sm:self-auto">
                                <x-ui.forms.toggle name="allow_quote_without_save"
                                    :checked="$settings->allow_quote_without_save" />
                            </div>
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
                        
                        <div class="p-3 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl border border-slate-100">
                            <x-ui.forms.toggle label="Auto-imprimir Recibo" name="auto_print_receipt"
                                :checked="$settings->auto_print_receipt"
                                description="Lanza la impresión al cobrar." />
                        </div>
                    </div>
                </section>

                {{-- BOTÓN FLOTANTE --}}
                <div class="sticky bottom-4 sm:bottom-6 bg-white/95 backdrop-blur-md border border-slate-200 p-3 sm:p-4 rounded-2xl sm:rounded-3xl shadow-2xl flex flex-col sm:flex-row items-stretch sm:items-center gap-3 z-[40]">
                    <x-ui.button href="{{ route('sales.pos.terminals.index') }}" appearance="ghost" variant="secondary"
                        class="flex-1 sm:flex-none">
                        ← Volver a Terminales
                    </x-ui.button>

                    <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-check-circle"
                        class="flex-1 sm:flex-none">
                        <span class="hidden sm:inline">Aplicar Configuración</span>
                        <span class="sm:hidden">Guardar</span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-config-layout>