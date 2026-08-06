<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4" x-data="saleForm()" x-init="init()">
        
        <form action="{{ route('sales.store') }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100 transition-all">
            @csrf

            <x-ui.toasts />
            
            {{-- HEADER --}}
            <div class="bg-white border-b border-gray-100 p-6 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight">Nueva Venta de Mercancía</h2>
                    <p class="text-sm text-gray-500">Gestión de facturación y salida de inventario.</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Fecha de Emisión</span>
                    <input type="date" name="sale_date" x-model="formData.sale_date"
                        class="border-none p-0 text-gray-600 font-mono text-sm bg-transparent focus:ring-0 text-right cursor-default" 
                        readonly>
                </div>
            </div>

            <div class="p-6 md:p-8 space-y-8">
                
                {{-- SECCIÓN 1: CONFIGURACIÓN REDISEÑADA --}}
                <section class="space-y-6">
                    {{-- FILA 1: TIPO DE VENTA Y CLIENTE --}}
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                        
                        {{-- Selector de Tipo de Venta (Visual) --}}
                        <div class="md:col-span-4">
                            <x-input-label value="Tipo de Venta" class="mb-3 text-xs text-gray-500 uppercase tracking-wider" />
                            <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-xl">
                                <button type="button" 
                                    @click="formData.payment_type = 'cash'; handlePaymentTypeChange()"
                                    :class="formData.payment_type === 'cash' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all">
                                    <x-heroicon-s-currency-dollar class="w-4 h-4"/>
                                    Contado
                                </button>
                                <button type="button" 
                                    @click="formData.payment_type = 'credit'; handlePaymentTypeChange()"
                                    :class="formData.payment_type === 'credit' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-bold transition-all">
                                    <x-heroicon-s-credit-card class="w-4 h-4"/>
                                    Crédito
                                </button>
                            </div>
                            <input type="hidden" name="payment_type" x-model="formData.payment_type">
                        </div>

                        {{-- Selector de Cliente --}}
                        <div class="md:col-span-5">
                            <x-input-label value="Cliente" class="mb-1 text-xs text-gray-500 uppercase tracking-wider" />
                            <div class="relative">
                                <select name="client_id" x-model="formData.client_id" @change="validateNcfAndClient()"
                                    class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 shadow-sm pl-10">
                                    <template x-for="client in filteredClients" :key="client.id">
                                        <option :value="client.id" x-text="client.name"></option>
                                    </template>
                                </select>
                                <x-heroicon-o-user class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"/>
                            </div>
                        </div>

                        {{-- Almacén (Compacto) --}}
                        <div class="md:col-span-3">
                            <x-input-label value="Almacén" class="mb-1 text-xs text-gray-500 uppercase tracking-wider" />
                            <select name="warehouse_id" x-model="formData.warehouse_id" @change="clearItems()"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 shadow-sm transition-all" required>
                                <option value="">Seleccione origen...</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- FILA 2: COMPROBANTE Y MÉTODO --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50 flex items-center gap-4">
                            
                            <template x-if="config.ncfEnabled">
                                <div class="flex-1">
                                    <x-input-label value="Comprobante Fiscal" class="mb-1 text-[10px] text-indigo-400 uppercase font-bold" />
                                    <select name="ncf_type_id" x-model="formData.ncf_type_id"
                                        class="w-full border-none bg-transparent p-0 text-sm font-black text-indigo-900 focus:ring-0">
                                        <option value="">Seleccione NCF...</option>
                                        <template x-for="type in filteredNcfTypes" :key="type.id">
                                            <option :value="type.id" x-text="type.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>

                            <template x-if="!config.ncfEnabled">
                                <div class="flex-1">
                                    <x-input-label value="Tipo de Documento" class="mb-1 text-[10px] text-gray-400 uppercase font-bold" />
                                    <div class="text-sm font-black text-gray-600 uppercase tracking-tight">
                                        Documento Interno
                                    </div>
                                    <input type="hidden" name="ncf_type_id" value="">
                                </div>
                            </template>

                            <div class="h-10 w-px bg-indigo-200"></div>

                            {{-- Método de Pago --}}
                            <div class="flex-1" x-show="formData.payment_type === 'cash'" x-transition>
                                <x-input-label value="Método de Pago" class="mb-1 text-[10px] text-indigo-400 uppercase font-bold" />
                                <select name="tipo_pago_id" x-model="formData.tipo_pago_id" @change="handleTipoPagoChange()"
                                    class="w-full border-none bg-transparent p-0 text-sm font-black text-indigo-900 focus:ring-0">
                                    <option value="">Seleccione...</option>
                                    @foreach($tipo_pagos as $tp)
                                        <option value="{{ $tp->id }}">{{ $tp->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex-1" x-show="formData.payment_type === 'credit'" x-transition>
                                <p class="text-[10px] text-amber-500 uppercase font-bold">Condición</p>
                                <p class="text-sm font-black text-amber-700">Cuentas por Cobrar</p>
                            </div>
                        </div>

                        {{-- INFO BOX DEL CLIENTE --}}
                        <div class="flex items-center">
                            <template x-if="selectedClient">
                                <div class="w-full bg-white border border-gray-200 rounded-xl p-3 flex justify-between items-center shadow-sm">
                                    <div>
                                        <p class="text-[10px] uppercase font-bold text-gray-400">RNC/Cédula</p>
                                        <p class="text-sm font-mono text-gray-700" x-text="selectedClient.tax_id || 'N/A'"></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] uppercase font-bold text-gray-400">Crédito Disp.</p>
                                        <p :class="exceedsCreditLimit ? 'text-red-600' : 'text-emerald-600'" 
                                        class="text-sm font-black" x-text="formatMoney(selectedClient.available)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                {{-- ALERTAS DE ESTADO DE CLIENTE --}}
                <template x-if="selectedClient && (selectedClient.is_moroso || exceedsCreditLimit)">
                    <div class="mb-6 space-y-3">
                        <template x-if="selectedClient.is_moroso">
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm animate-pulse">
                                <div class="flex items-center">
                                    <x-heroicon-s-exclamation-triangle class="w-6 h-6 text-red-600 mr-3"/>
                                    <div>
                                        <h3 class="text-sm font-bold text-red-800">CLIENTE MOROSO DETECTADO</h3>
                                        <p class="text-xs text-red-700">Este cliente tiene facturas vencidas. Las ventas a crédito están bloqueadas automáticamente.</p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="exceedsCreditLimit">
                            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl shadow-sm">
                                <div class="flex items-center">
                                    <x-heroicon-s-no-symbol class="w-6 h-6 text-amber-600 mr-3"/>
                                    <div>
                                        <h3 class="text-sm font-bold text-amber-800">LÍMITE DE CRÉDITO EXCEDIDO</h3>
                                        <p class="text-xs text-amber-700">El monto total (<span x-text="formatMoney(totals.total)"></span>) supera el balance disponible del cliente (<span x-text="formatMoney(selectedClient.available)"></span>).</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- SECCIÓN 2: DETALLE DE PRODUCTOS --}}
                <section x-show="formData.warehouse_id" 
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100">
                    
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-400 uppercase text-[10px] tracking-widest flex items-center gap-2">
                            <span class="w-5 h-5 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center text-[10px] font-bold">2</span>
                            Detalle de Productos
                        </h3>
                        <button type="button" @click="addItem()"
                            class="text-xs bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all font-bold active:scale-95">
                            + Añadir Producto
                        </button>
                    </div>

                    <div class="border border-gray-100 rounded-xl overflow-hidden shadow-sm bg-white">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50/80 text-gray-500 text-[10px] uppercase text-left border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 w-1/3 text-center">Producto</th>
                                    <th class="px-6 py-4 text-center">Stock</th>
                                    <th class="px-6 py-4 w-28 text-center">Cant.</th>
                                    <th class="px-6 py-4 text-center">Precio</th>
                                    <th class="px-6 py-4 w-28 text-center">Desc. (%)</th>
                                    <th class="px-6 py-4 text-right">Subtotal</th>
                                    <th class="px-6 py-4 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-gray-50/50 transition-colors group"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 -translate-x-2"
                                        x-transition:enter-end="opacity-100 translate-x-0">
                                        
                                        <td class="px-4 py-3">
                                            <select :name="`items[${index}][product_id]`" 
                                                    x-model="item.product_id"
                                                    @change="updateProductData(index)"
                                                    class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 transition-all">
                                                <option value="">Seleccione...</option>
                                                <template x-for="p in filteredProducts" :key="p.id">
                                                    <option :value="p.id" x-text="p.name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 bg-gray-100 rounded text-gray-500 font-mono text-[11px]" x-text="item.stock || 0"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" :name="`items[${index}][quantity]`" 
                                                x-model.number="item.quantity"
                                                @input="calculateTotals()"
                                                :max="item.stock" min="1"
                                                class="w-full border-gray-200 rounded-lg text-sm text-center focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="hidden" :name="`items[${index}][price]`" :value="item.price">
                                            <input type="number" 
                                                x-model.number="item.price"
                                                class="w-full border-transparent bg-gray-50/50 rounded-lg text-sm text-right font-mono cursor-not-allowed" 
                                                readonly>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="relative rounded-md shadow-sm">
                                                <input type="number" 
                                                    :name="`items[${index}][discount_percentage]`"
                                                    x-model.number="item.discount_percentage" 
                                                    @input="calculateTotals()"
                                                    min="0" 
                                                    max="100"
                                                    step="0.01"
                                                    {{-- 11.2: venta manual de backoffice, sin terminal asociada — sin límite de
                                                         descuento a propósito (no hay config global a la que heredar). --}}
                                                    class="w-full border-gray-200 rounded-lg text-sm text-right p-2 pr-7 focus:ring-indigo-500 disabled:bg-gray-100">
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                    <span class="text-gray-400 text-xs">%</span>
                                                </div>
                                                <input type="hidden" :name="`items[${index}][discount_amount]`" :value="item.discount_amount">
                                            </div>
                                        </td>

                                        {{-- Muestra el bruto de la línea (qty * price), el descuento se ve en el resumen --}}
                                        <td class="px-4 py-3 text-right font-mono font-bold text-gray-700" 
                                            x-text="formatMoney(item.subtotal)">
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button" @click="removeItem(index)" 
                                                    class="p-1.5 text-red-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                                <x-heroicon-s-trash class="w-4 h-4"/>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                {{-- EMPTY STATE --}}
                                <template x-if="items.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic text-sm">
                                            No hay productos agregados. Haga clic en "Añadir Producto" para comenzar.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- SECCIÓN 3: TOTALES --}}
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="md:col-span-2">
                        <x-input-label value="Notas de la Venta" class="text-xs text-gray-500 uppercase tracking-wider" />
                        <textarea name="notes" rows="3" 
                                class="w-full mt-2 border-gray-300 rounded-xl text-sm focus:ring-indigo-500 transition-all" 
                                placeholder="Detalles adicionales de la factura..."></textarea>
                    </div>

                    <div class="bg-gray-900 text-white rounded-2xl p-6 shadow-2xl space-y-4 relative overflow-hidden transition-all duration-500">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/10 rounded-full -mr-12 -mt-12"></div>
                        
                        {{-- Subtotal Bruto --}}
                        <div class="flex justify-between text-[10px] opacity-50 uppercase tracking-[0.2em]">
                            <span>Subtotal</span>
                            <span x-text="formatMoney(totals.gross)"></span>
                        </div>

                        {{-- Descuento (solo si hay) --}}
                        <template x-if="formData.discount_total > 0">
                            <div class="flex justify-between text-[10px] text-red-400 uppercase tracking-[0.2em]" x-transition>
                                <span>Descuento</span>
                                <span x-text="'- ' + formatMoney(formData.discount_total)"></span>
                            </div>
                        </template>

                        {{-- NUEVO: Bloque de Pago (Solo si es Contado) --}}
                        <template x-if="formData.payment_type === 'cash'">
                            <div class="space-y-3 pt-4 mt-2 border-t border-white/10" x-transition>
                                
                                {{-- Solo mostrar input de recibido si es efectivo --}}
                                <template x-if="tipo_pagos.find(t => t.id == formData.tipo_pago_id)?.nombre.toLowerCase().includes('efectivo')">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Efectivo Recibido</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-mono">$</span>
                                            <input type="number" 
                                                name="cash_received" 
                                                x-model.number="formData.cash_received" 
                                                @input="calculateChange()" 
                                                step="0.01"
                                                class="w-full bg-white/5 border-white/10 rounded-lg pl-7 py-2 text-lg font-mono focus:ring-indigo-500 focus:bg-white/10 transition-all">
                                        </div>
                                    </div>
                                </template>

                                {{-- Visualización de Cambio y Campos Ocultos para el POST --}}
                                <div class="flex justify-between items-center bg-indigo-500/20 p-3 rounded-xl border border-indigo-500/30">
                                    <span class="text-[10px] font-bold text-indigo-300 uppercase">Cambio a Devolver</span>
                                    <span class="text-xl font-black font-mono text-indigo-400" x-text="formatMoney(formData.cash_change)"></span>
                                    
                                    {{-- ESTOS INPUTS SON VITALES PARA EL BACKEND --}}
                                    <input type="hidden" name="cash_change" :value="formData.cash_change">
                                    {{-- Si el método NO es efectivo, enviamos el neto como recibido --}}
                                    <template x-if="!tipo_pagos.find(t => t.id == formData.tipo_pago_id)?.nombre.toLowerCase().includes('efectivo')">
                                        <input type="hidden" name="cash_received" :value="totals.total">
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="pt-2">
                            <div class="flex justify-between items-end">
                                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">Total a Pagar</span>
                                {{-- totals.total es el NETO (lo que el cliente paga de verdad) --}}
                                <span class="text-3xl font-black font-mono tracking-tight" x-text="formatMoney(totals.total)"></span>
                            </div>

                            {{--
                                CAMPOS OCULTOS PARA EL BACKEND:
                                - total_amount  → BRUTO (gross): lo que vale sin descuentos  → se guarda en sales.total_amount
                                - discount_total → descuento en dinero                        → se guarda en sales.discount_total
                                El backend calcula: total_amount - discount_total = neto a cobrar
                            --}}
                            <input type="hidden" name="subtotal"      :value="totals.subtotal">
                            <input type="hidden" name="tax_amount"    :value="totals.tax">
                            <input type="hidden" name="total_amount"  :value="totals.gross">
                            <input type="hidden" name="discount_total" :value="formData.discount_total">
                        </div>
                    </div>
                </section>
            </div>

            {{-- FOOTER --}}
            <div class="p-6 bg-gray-50/50 flex justify-end items-center border-t border-gray-100 gap-6">
                <a href="{{ route('sales.index') }}" class="text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">Cancelar</a>
                <div class="flex flex-col items-end gap-1">    
                    <button type="submit"
                        class="bg-indigo-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg shadow-indigo-100 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:grayscale"
                        :class="isSubmitDisabled ? '' : 'hover:bg-indigo-700 hover:-translate-y-0.5 active:scale-95'"
                        :disabled="isSubmitDisabled">
                        <x-heroicon-s-check-circle class="w-5 h-5"/>
                        Confirmar y Facturar
                    </button>
                    <template x-if="isSubmitDisabled && selectedClient">
                        <span class="text-[10px] text-red-500 font-bold uppercase">Verifique el estado o límite del cliente</span>
                    </template>
                </div>
            </div>
        </form>
    </div>

    <script>
        function saleForm() {
            return {
                products: @json($products),
                clients: @json($clients),
                ncf_types: @json($ncf_types),
                tipo_pagos: @json($tipo_pagos),
                items: [],
                config: {
                    tax_rate: {{ general_config()->impuesto->valor ?? 0 }},
                    apply_tax: false,
                    ncfEnabled: {{ module_enabled('sales.ncf') ? 'true' : 'false' }}
                },
                formData: {
                    payment_type: 'cash',
                    tipo_pago_id: '1',
                    client_id: '1',
                    warehouse_id: '',
                    ncf_type_id: '2',
                    sale_date: '{{ date("Y-m-d") }}',
                    cash_received: 0,
                    cash_change: 0,
                    discount_total: 0,
                },
                // CAMBIO: se añade gross (bruto) y net (neto) para separar lo que se muestra de lo que se guarda
                totals: { gross: 0, net: 0, subtotal: 0, tax: 0, total: 0 },

                init() {
                    if (!this.config.ncfEnabled) {
                        this.formData.ncf_type_id = null;
                    }
                    this.$watch('formData.ncf_type_id', () => this.validateNcfAndClient());
                },

                get filteredClients() {
                    let list = this.clients;
                    if (this.formData.payment_type === 'credit') {
                        list = list.filter(c => c.id != 1);
                    }
                    const selectedNcf = this.ncf_types.find(n => n.id == this.formData.ncf_type_id);
                    if (selectedNcf && ['01', '31'].includes(selectedNcf.code)) {
                        list = list.filter(c => c.id != 1 && c.tax_id !== '00000000000');
                    }
                    return list;
                },

                get filteredNcfTypes() {
                    if (this.formData.client_id == 1 || this.selectedClient?.tax_id === '00000000000') {
                        return this.ncf_types.filter(n => !['01', '31'].includes(n.code));
                    }
                    return this.ncf_types;
                },

                get selectedClient() {
                    return this.clients.find(c => c.id == this.formData.client_id) || null;
                },

                get filteredProducts() {
                    if (!this.formData.warehouse_id) return [];
                    return this.products.filter(p => p.warehouse_id == this.formData.warehouse_id);
                },

                validateNcfAndClient() {
                    const selectedNcf = this.ncf_types.find(n => n.id == this.formData.ncf_type_id);
                    if (selectedNcf && ['01', '31'].includes(selectedNcf.code)) {
                        if (this.formData.client_id == 1 || (this.selectedClient && !this.selectedClient.tax_id)) {
                            this.formData.client_id = '';
                        }
                    }
                },

                get ncfRequiresRnc() {
                    const selectedNcf = this.ncf_types.find(n => n.id == this.formData.ncf_type_id);
                    if (!selectedNcf) return false;
                    return ['01', '31'].includes(selectedNcf.code) && (!this.selectedClient?.tax_id || this.selectedClient.tax_id === '00000000000');
                },

                get exceedsCreditLimit() {
                    if (this.formData.payment_type !== 'credit' || !this.selectedClient || this.selectedClient.id == 1) {
                        return false;
                    }
                    // Comparamos el NETO a pagar contra el disponible del cliente
                    return this.totals.total > parseFloat(this.selectedClient.available || 0);
                },

                get isSubmitDisabled() {
                    const hasItems = this.items.length > 0;
                    const hasTotal = this.totals.total > 0;

                    if (this.config.ncfEnabled && this.ncfRequiresRnc) return true;

                    if (this.formData.payment_type === 'credit') {
                        return !hasItems || !this.selectedClient || this.selectedClient.is_moroso || this.exceedsCreditLimit;
                    }

                    return !hasItems || !hasTotal;
                },

                addItem() {
                    this.items.push({
                        product_id: '',
                        quantity: 1,
                        price: 0,
                        stock: 0,
                        discount_percentage: 0,
                        discount_amount: 0,
                        subtotal: 0
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculateTotals();
                },

                clearItems() {
                    this.items = [];
                    this.calculateTotals();
                },

                updateProductData(index) {
                    const item = this.items[index];
                    const product = this.products.find(p => p.id == item.product_id && p.warehouse_id == this.formData.warehouse_id);
                    if (product) {
                        item.price = product.price;
                        item.stock = product.stock;
                    }
                    this.calculateTotals();
                },

                handlePaymentTypeChange() {
                    if (this.formData.payment_type === 'credit') {
                        const tipoCredito = this.tipo_pagos.find(tp => tp.slug === 'credito' || tp.nombre.toLowerCase().includes('crédito'));
                        this.formData.tipo_pago_id = tipoCredito ? tipoCredito.id : null;
                        this.formData.cash_received = 0;
                        this.formData.cash_change = 0;
                    } else {
                        this.formData.tipo_pago_id = '1';
                    }
                    this.validateNcfAndClient();
                },

                handleTipoPagoChange() {
                    const metodo = this.tipo_pagos.find(t => t.id == this.formData.tipo_pago_id);
                    const esEfectivo = metodo && metodo.nombre.toLowerCase().includes('efectivo');
                    if (!esEfectivo) {
                        // Para no-efectivo, el recibido es el neto a pagar (no hay cambio)
                        this.formData.cash_received = this.totals.total;
                        this.formData.cash_change = 0;
                    }
                },

                calculateTotals() {
                    let bruto = 0;
                    let totalDescuentos = 0;

                    // 11.2: sin terminal asociada, sin límite de descuento a propósito
                    // (backoffice de uso exclusivo de admins de confianza).
                    const maxDiscountPct  = 100;
                    const allowItemDiscount = true;

                    this.items.forEach(item => {
                        let qty   = parseFloat(item.quantity) || 1;
                        let price = parseFloat(item.price)    || 0;
                        let pct   = parseFloat(item.discount_percentage) || 0;

                        if (!allowItemDiscount) {
                            pct = 0;
                        } else {
                            if (pct < 0) pct = 0;
                            if (pct > maxDiscountPct) pct = maxDiscountPct;
                        }

                        item.discount_percentage = pct;

                        // Bruto de la línea: precio * cantidad (ej. 20 * $15 = $300)
                        const itemBruto = price * qty;

                        // CAMBIO: item.subtotal muestra el BRUTO (lo que vale sin descuento)
                        // El descuento se refleja en el resumen global, no en la columna de línea
                        item.subtotal       = itemBruto;
                        item.discount_amount = (itemBruto * pct) / 100;

                        bruto            += itemBruto;
                        totalDescuentos  += item.discount_amount;
                    });

                    // El descuento global en dinero (ej. $30)
                    this.formData.discount_total = totalDescuentos;

                    // El neto real que el cliente va a pagar (ej. $270)
                    const netoPagar = bruto - totalDescuentos;

                    if (this.config.apply_tax && this.config.tax_rate > 0) {
                        const divisor = 1 + (this.config.tax_rate / 100);

                        // CAMBIO: gross = bruto antes de descuentos → va a total_amount en BD
                        this.totals.gross    = bruto;
                        // net = neto después de descuentos → lo que realmente paga
                        this.totals.net      = netoPagar;
                        this.totals.subtotal = netoPagar / divisor;
                        this.totals.tax      = netoPagar - this.totals.subtotal;
                        // total = neto → se muestra en pantalla y se usa para cobro/cambio/crédito
                        this.totals.total    = netoPagar;
                    } else {
                        // CAMBIO: gross = bruto → se envía como total_amount al servidor
                        this.totals.gross    = bruto;
                        this.totals.net      = netoPagar;
                        this.totals.subtotal = netoPagar;
                        this.totals.tax      = 0;
                        // total = neto → lo que se muestra y se cobra
                        this.totals.total    = netoPagar;
                    }

                    this.calculateChange();
                    this.handleTipoPagoChange();
                },

                calculateChange() {
                    const total    = parseFloat(this.totals.total) || 0;
                    const received = parseFloat(this.formData.cash_received) || 0;
                    if (received > total) {
                        this.formData.cash_change = (received - total).toFixed(2);
                    } else {
                        this.formData.cash_change = 0;
                    }
                },

                formatMoney(amount) {
                    return '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(amount);
                }
            }
        }
    </script>
</x-app-layout>