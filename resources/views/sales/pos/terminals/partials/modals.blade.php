@foreach($items as $item)
    {{-- MODAL: DETALLES COMPLETOS DE LA TERMINAL --}}
    <x-modal name="view-terminal-{{ $item->id }}" maxWidth="2xl">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con Identificación --}}
            <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b relative">
                <div class="flex justify-between items-start">
                    <div class="flex gap-4 items-center">
                        <div class="relative">
                            <div class="w-16 h-16 {{ $item->is_active ? 'bg-zertix-primary-100 text-zertix-primary-700' : 'bg-gray-100 text-gray-500' }} rounded-xl flex items-center justify-center shadow-sm border-2 border-white">
                                @if($item->is_mobile)
                                    <x-heroicon-s-device-phone-mobile class="w-8 h-8"/>
                                @else
                                    <x-heroicon-s-computer-desktop class="w-8 h-8"/>
                                @endif
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $item->name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-mono font-semibold px-2 py-0.5 bg-zertix-primary-50 text-zertix-primary-700 rounded border border-zertix-primary-100 uppercase tracking-wider">
                                    ID: {{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="text-gray-400 text-xs">•</span>
                                <span class="text-sm text-gray-500 font-medium italic">
                                    {{ $item->is_mobile ? 'Terminal Móvil' : 'Punto de Venta Fijo' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Badge de Estado --}}
                    <div class="flex flex-col gap-2 items-end">
                        <x-ui.badge :variant="$item->is_active ? 'success' : 'error'">
                            {{ $item->is_active ? 'Operativa' : 'Fuera de Servicio' }}
                        </x-ui.badge>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Columna Izquierda: Configuración Contable y Ventas --}}
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-xs font-bold text-zertix-primary-600 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-zertix-primary-50 pb-2">
                                <x-heroicon-s-building-library class="w-4 h-4"/> Integración Contable
                            </h4>
                            <div class="space-y-3">
                                {{-- Cuenta de Caja solo aplica con accounting.advanced activo
                                     (REQ-02.16) — el checkout POS ya no depende de esta cuenta
                                     desde REQ-02.9, así que no tiene sentido mostrarla apagado. --}}
                                @if (module_enabled('accounting.advanced'))
                                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Cuenta de Caja</span>
                                        <p class="text-sm font-bold text-gray-800">
                                            {{ $item->cashAccount->code ?? 'N/A' }} - {{ $item->cashAccount->name ?? 'No vinculada' }}
                                        </p>
                                    </div>
                                @endif
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-[10px] text-gray-400 uppercase font-bold block">Almacén de Despacho</span>
                                    <p class="text-sm font-semibold text-gray-700">
                                        {{ $item->warehouse->name ?? 'No asignado' }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        {{-- Sección: Preferencias de Facturación --}}
                        <section>
                            <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-blue-50 pb-2">
                                <x-heroicon-s-document-duplicate class="w-4 h-4"/> Preferencias de Facturación
                            </h4>
                            <div class="space-y-3">
                                {{-- NCF: Solo mostrar si aplica --}}
                                @if(general_config()?->esModoFiscal())
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                            <x-heroicon-s-receipt-percent class="w-4 h-4"/>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-gray-400 uppercase block">NCF por Defecto</span>
                                            <p class="text-sm font-medium text-gray-700">{{ $item->defaultNcfType->name ?? 'Por defecto del sistema' }}</p>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-50 rounded-lg text-gray-400">
                                        <x-heroicon-s-user class="w-4 h-4"/>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase block">Cliente Genérico</span>
                                        <p class="text-sm text-gray-600 leading-relaxed">
                                            {{ $item->defaultClient->name ?? pos_config('default_walkin_customer_name') ?? 'Consumidor Final' }}
                                            @if(is_null($item->default_client_id))
                                                <span class="text-[9px] text-blue-500 font-bold ml-1">(HEREDADO)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- Columna Derecha: Hardware y Auditoría --}}
                    <div class="space-y-6">
                        {{-- Sección: Configuración de Impresión --}}
                        <section>
                            <h4 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-amber-50 pb-2">
                                <x-heroicon-s-printer class="w-4 h-4"/> Configuración de Impresión
                            </h4>
                            @php 
                                $currentFormat = $item->printer_format ?? pos_config('receipt_size');
                            @endphp
                            <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-amber-800 font-medium">Ancho de Papel:</span>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] font-bold uppercase py-1 px-2 bg-amber-100 text-amber-700 rounded-md">
                                            {{ $currentFormat }}
                                        </span>
                                        @if(is_null($item->printer_format))
                                            <span class="text-[8px] text-amber-500 mt-1 uppercase font-bold italic">Ajuste Global</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-[10px] text-amber-600/70 mt-2 italic leading-tight">
                                    * Formato optimizado para tiqueteras térmicas de {{ $currentFormat == '80mm' ? '8cm' : '5.8cm' }}.
                                </p>
                            </div>
                        </section>

                        <section>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2 border-b border-gray-50 pb-2">
                                <x-heroicon-s-clock class="w-4 h-4"/> Trazabilidad
                            </h4>
                            <div class="space-y-3 px-1">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-400">Creada en:</span>
                                    <span class="font-medium text-gray-600">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-400">Última actividad:</span>
                                    <span class="font-medium text-gray-600">{{ $item->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-10 pt-6 border-t flex flex-col sm:flex-row justify-end items-center gap-4">
                    <div class="flex gap-3 w-full sm:w-auto">
                        <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')" class="flex-1 sm:flex-none justify-center">
                            Cerrar
                        </x-ui.button>
                        @can('pos_terminals.edit')
                            <x-ui.button href="{{ route('sales.pos.terminals.edit', $item) }}"
                                variant="primary" iconLeft="heroicon-s-pencil" class="flex-1 sm:flex-none justify-center">
                                Configurar
                            </x-ui.button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

    {{-- Modal de Confirmación --}}
    @if($item->trashed())
        {{-- Papelera (docs/analisis/politica-soft-deletes.md §6) — borrado
             definitivo vía wireConfirm, dispara PosTerminalTable::forceDelete(). --}}
        <x-ui.confirm-deletion-modal
            :id="$item->id"
            :title="'¿Eliminar Permanentemente?'"
            :itemName="$item->name"
            :type="'la terminal'"
            :wireConfirm="'forceDelete(' . $item->id . ')'"
            :description="'Estás a punto de borrar definitivamente la terminal <strong>' . e($item->name) . '</strong>.'"
        >
            <strong>Aviso Crítico:</strong> Esta operación borrará todos los datos asociados y no se puede deshacer.
        </x-ui.confirm-deletion-modal>
    @else
        <x-ui.confirm-deletion-modal
            :id="$item->id"
            :title="'¿Desactivar Terminal?'"
            :itemName="$item->name"
            :type="'la terminal'"
            :route="route('sales.pos.terminals.destroy', $item)"
        >
            <strong>Advertencia:</strong> Si esta terminal tiene sesiones abiertas, no podrá ser eliminada. Al desactivarla, los cajeros ya no podrán iniciar sesión en este punto de venta.
        </x-ui.confirm-deletion-modal>
    @endif
@endforeach