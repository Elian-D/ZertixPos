@foreach($items as $movement)
    <x-modal name="view-movement-{{ $movement->id }}" maxWidth="lg">
        <div class="overflow-hidden rounded-xl">
            {{-- Header con Color según Tipo (Usando los helpers del modelo) --}}
            @php
                $styleClasses = \App\Models\Sales\Pos\PosCashMovement::getTypeStyles()[$movement->type] ?? 'bg-gray-50 text-gray-700';
                $icon = \App\Models\Sales\Pos\PosCashMovement::getTypeIcons()[$movement->type] ?? 'heroicon-s-currency-dollar';
                $typeLabel = \App\Models\Sales\Pos\PosCashMovement::getTypes()[$movement->type] ?? $movement->type;
                
                // Definir gradientes basados en el tipo para el header
                $gradient = $movement->type === 'in' 
                    ? 'from-green-50 to-white border-green-100 text-green-700' 
                    : 'from-amber-50 to-white border-amber-100 text-amber-700';
            @endphp
            
            <div class="bg-gradient-to-r {{ $gradient }} px-6 py-4 border-b relative">
                <div class="flex justify-between items-center">
                    <div class="flex gap-3 items-center">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-white shadow-sm border border-current opacity-80">
                            <x-dynamic-component :component="$icon" class="w-6 h-6"/>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold leading-tight">Movimiento #{{ $movement->id }}</h3>
                            <p class="text-xs font-medium opacity-70 italic uppercase tracking-wider">
                                {{ $typeLabel }}
                            </p>
                        </div>
                    </div>
                    <span class="text-[10px] font-mono bg-white/50 px-2 py-1 rounded border border-current/20">
                        {{ $movement->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="p-6 bg-white">
                <div class="space-y-6">
                    {{-- Información Principal: Monto y Sesión --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <span class="text-[10px] text-gray-400 uppercase font-bold block">Sesión de Caja</span>
                            <p class="text-sm font-semibold text-gray-800">SES-{{ $movement->pos_session_id }}</p>
                            <p class="text-[10px] text-gray-500">{{ $movement->session->terminal->name ?? 'Terminal N/A' }}</p>
                        </div>

                        <div class="p-3 rounded-lg border {{ $movement->type === 'in' ? 'bg-green-50 border-green-100' : 'bg-amber-50 border-amber-100' }}">
                            <span class="text-[10px] {{ $movement->type === 'in' ? 'text-green-500' : 'text-amber-500' }} uppercase font-bold block">Monto Registrado</span>
                            <p class="text-xl font-black {{ $movement->type === 'in' ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }} ${{ number_format($movement->amount, 2) }}
                            </p>
                        </div>
                    </div>

                    {{-- Motivo / Razón --}}
                    <section>
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <x-heroicon-s-chat-bubble-bottom-center-text class="w-4 h-4 text-gray-300"/> Motivo / Razón
                        </h4>
                        <p class="text-sm text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 italic">
                            "{{ $movement->reason ?? 'Sin descripción registrada.' }}"
                        </p>
                    </section>

                    {{-- Auditoría y Contabilidad --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-4">
                        <div>
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                                <x-heroicon-s-user class="w-3 h-3"/> Responsable
                            </h4>
                            <p class="text-xs font-medium text-gray-700">{{ $movement->user->name ?? 'Sistema' }}</p>
                            <p class="text-[10px] text-gray-400">{{ $movement->user->email ?? '' }}</p>
                        </div>

                        <div class="md:text-right">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 flex items-center md:justify-end gap-1">
                                <x-heroicon-s-document-check class="w-3 h-3"/> Estado Contable
                            </h4>
                            @if($movement->accounting_entry_id)
                                <p class="text-xs font-bold text-indigo-600">Asiento #{{ $movement->accounting_entry_id }}</p>
                                <p class="text-[10px] text-gray-400">Integrado automáticamente</p>
                            @else
                                <p class="text-xs font-medium text-gray-400 italic">Sin asiento contable</p>
                            @endif
                        </div>
                    </div>

                    {{-- Metadatos / Referencia --}}
                    @if($movement->reference || !empty($movement->metadata))
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 mt-2">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase mb-2">Información Técnica / Referencia</h4>
                            <div class="flex flex-wrap gap-2 text-[10px]">
                                @if($movement->reference)
                                    <span class="px-2 py-1 bg-white border rounded">REF: {{ $movement->reference }}</span>
                                @endif
                                @if(!empty($movement->metadata))
                                    @foreach($movement->metadata as $key => $value)
                                        @if(is_scalar($value))
                                            <span class="px-2 py-1 bg-white border rounded uppercase">{{ $key }}: {{ $value }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Añadir esto dentro del grid de Auditoría del Modal --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                            <x-heroicon-s-building-library class="w-3 h-3"/> Cuenta Contable
                        </h4>
                        @if($movement->account)
                            <p class="text-xs font-bold text-gray-700">{{ $movement->account->name }}</p>
                            <p class="text-[10px] font-mono text-indigo-500">{{ $movement->account->code }}</p>
                        @else
                            <p class="text-xs text-gray-400 italic">Sin cuenta asignada</p>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')" class="w-full sm:w-auto justify-center">
                            Cerrar Detalle
                        </x-secondary-button>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>
@endforeach