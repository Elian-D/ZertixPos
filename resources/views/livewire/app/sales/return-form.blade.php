@php
    $currency = config('regional.currency_symbol');
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp

<div>
    <x-modal :name="\App\Livewire\App\Sales\ReturnForm::MODAL" maxWidth="2xl">
        @if($this->sale)
            @php
                $sale = $this->sale;
                $values = $this->lineValues;
                $total = $this->total;
                $summary = $this->exchangeSummary;
                $isExchange = $method === \App\Models\Sales\Returns\SaleReturn::METHOD_EXCHANGE;
                $selectedIds = $this->selectedIds;
            @endphp

            <form wire:submit="save">
                {{-- Encabezado --}}
                <div class="px-6 py-5 border-b bg-slate-50 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Devolver productos</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Venta <span class="font-mono font-bold text-zertix-primary-700">{{ $sale->number }}</span>
                            · {{ $sale->client->name ?? 'Consumidor Final' }}
                        </p>
                    </div>
                    <x-ui.badge :variant="$this->isCredit ? 'warning' : 'info'" size="sm" :dot="false">
                        {{ $this->isCredit ? 'Crédito' : 'Contado' }}
                    </x-ui.badge>
                </div>

                <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    <x-ui.forms.select label="Motivo" name="reason" wire:model="reason" :error="$errors->first('reason')" required>
                        @foreach($reasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    {{-- Método --}}
                    <div>
                        <span class="block text-sm font-medium text-slate-700 mb-2">Método</span>
                        <div class="flex flex-col sm:flex-row gap-3 sm:gap-6">
                            <x-ui.forms.radio name="method" value="cash" wire:model.live="method"
                                :label="$this->isCredit ? 'Reducir deuda' : 'Efectivo'"
                                :description="$this->isCredit ? 'Baja la cuenta por cobrar del cliente' : 'Se entrega dinero al cliente'" />
                            <x-ui.forms.radio name="method" value="exchange" wire:model.live="method"
                                label="Cambio de producto" :disabled="! $this->canExchange"
                                :description="$this->canExchange ? 'El cliente se lleva otro producto' : 'Solo con un producto marcado: el cambio es de uno en uno'" />
                        </div>
                        @error('method')
                            <p class="mt-1 text-xs text-state-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Líneas a devolver --}}
                    <div>
                        <span class="block text-sm font-medium text-slate-700 mb-2">Productos a devolver <span class="text-state-error">*</span></span>
                        <div class="rounded-lg border border-slate-200 divide-y divide-slate-100">
                            @foreach($this->returnableItems as $line)
                                @php
                                    $id = $line->id;
                                    $selected = in_array($id, $selectedIds, true);
                                    $v = $values[$id];
                                @endphp
                                <div class="p-3 {{ $selected ? 'bg-zertix-primary-50/40' : '' }}" wire:key="ret-line-{{ $id }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <x-ui.forms.checkbox id="ret-line-{{ $id }}" name="lines[{{ $id }}][selected]"
                                            wire:model.live="lines.{{ $id }}.selected"
                                            :label="$line->product->name ?? 'Producto eliminado'"
                                            :description="'Disponible: '.$fmtQty($line->returnable).' · Neto '.$currency.number_format($v['unit_net'], 2).' + ITBIS '.$currency.number_format($v['unit_tax'], 2).' c/u'" />
                                        @if($selected)
                                            <span class="font-mono font-bold text-slate-900 whitespace-nowrap">{{ $currency }}{{ number_format($v['total'], 2) }}</span>
                                        @endif
                                    </div>

                                    @if($selected)
                                        <div class="mt-3 pl-7 grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">
                                            <x-ui.forms.input type="number" step="0.01" min="0.01" :max="$line->returnable"
                                                label="Cantidad" name="lines[{{ $id }}][quantity]" id="ret-qty-{{ $id }}"
                                                wire:model.live.debounce.400ms="lines.{{ $id }}.quantity"
                                                :disabled="$line->returnable <= 1"
                                                :hint="$line->returnable <= 1 ? 'Única unidad disponible.' : 'Máximo '.$fmtQty($line->returnable).'.'"
                                                :error="$errors->first('lines.'.$id.'.quantity')" />
                                            <div class="pt-6">
                                                <x-ui.forms.toggle id="ret-restock-{{ $id }}" name="lines[{{ $id }}][restock]"
                                                    wire:model="lines.{{ $id }}.restock" label="Regresa a inventario"
                                                    description="Apágalo si está dañado." />
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('lines')
                            <p class="mt-1 text-xs text-state-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Efectivo / Reducir deuda: siempre el valor exacto devuelto --}}
                    @if(! $isExchange && $total > 0)
                        @if($this->isCredit)
                            <p class="text-sm text-slate-700">
                                Se reducirá la deuda del cliente en
                                <span class="font-mono font-bold">{{ $currency }}{{ number_format($total, 2) }}</span>.
                            </p>
                        @else
                            <x-ui.forms.input label="Efectivo a entregar" name="cash_preview" id="ret-cash"
                                :addonLeft="$currency" value="{{ number_format($total, 2) }}" disabled
                                hint="Se entrega exactamente el valor de lo devuelto." />
                        @endif
                    @endif

                    {{-- Cambio --}}
                    @if($isExchange)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <x-ui.forms.select label="Producto de reemplazo" name="replacementProductId"
                                    wire:model.live="replacementProductId" placeholder="El mismo producto"
                                    hint="Vacío: se entrega otra unidad del mismo producto."
                                    :error="$errors->first('replacementProductId')">
                                    @foreach($this->products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} — {{ $currency }}{{ number_format($product->price, 2) }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>
                            @if($this->isDifferentProduct)
                                <x-ui.forms.input type="number" step="0.01" min="0.01" label="Cantidad" name="replacementQuantity"
                                    wire:model.live.debounce.400ms="replacementQuantity" :error="$errors->first('replacementQuantity')" required />
                            @endif
                        </div>

                        @if($summary)
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-zertix-primary-100 bg-zertix-primary-50 px-4 py-3 text-sm">
                                <span>A favor <span class="font-mono font-bold">{{ $currency }}{{ number_format($summary['credit'], 2) }}</span></span>
                                <span class="text-slate-300">·</span>
                                <span>Nuevo <span class="font-mono font-bold">{{ $currency }}{{ number_format($summary['new'], 2) }}</span></span>
                                <span class="text-slate-300">·</span>
                                @if($summary['give'] > 0)
                                    <span class="font-bold text-state-warning">Entregar {{ $currency }}{{ number_format($summary['give'], 2) }}</span>
                                @else
                                    <span class="font-bold text-zertix-primary-700">Cobrar {{ $currency }}{{ number_format($summary['collect'], 2) }}</span>
                                @endif
                            </div>
                        @elseif(! $this->isDifferentProduct && ! empty($selectedIds))
                            <p class="text-xs text-slate-500">Se entrega otra unidad del mismo producto, sin cobro ni devolución de dinero.</p>
                        @endif
                    @endif

                    <x-ui.forms.textarea label="Observaciones" name="notes" wire:model="notes" :rows="2"
                        placeholder="Notas del cajero (opcional)" :error="$errors->first('notes')" />
                </div>

                <div class="px-6 py-4 border-t bg-slate-50 flex items-center justify-between gap-3">
                    <span class="text-sm text-slate-600">
                        Total devuelto: <span class="font-mono font-black text-slate-900">{{ $currency }}{{ number_format($total, 2) }}</span>
                    </span>
                    <div class="flex gap-3">
                        <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Cancelar</x-ui.button>
                        <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-arrow-uturn-left">
                            Confirmar devolución
                        </x-ui.button>
                    </div>
                </div>
            </form>
        @endif
    </x-modal>
</div>
