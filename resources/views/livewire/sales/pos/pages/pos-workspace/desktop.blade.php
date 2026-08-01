{{-- BODY DESKTOP/TABLET (≥lg): layout de dos columnas fijas. La versión táctil de
     pantallas chicas (móviles POS tipo Sunmi V2) vive en pos-workspace/mobile.blade.php,
     con su propia disposición en capas (bottom sheets, FAB, slide-over con tabs) —
     comparte todo el estado de Alpine, solo cambia la presentación visual. --}}
<div class="hidden lg:flex flex-1 min-h-0">

    {{-- COLUMNA PRODUCTOS --}}
    <main class="flex-1 flex flex-col min-w-0 p-5 gap-4 overflow-hidden">

        {{-- Buscador / Scanner --}}
        <div class="relative">
            <x-heroicon-s-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" />
            <input type="text"
                   x-model="search"
                   x-ref="searchInput"
                   @keydown.enter.prevent="onScan()"
                   placeholder="Buscar por nombre o SKU… (Enter para lectura de código de barras)"
                   autofocus
                   class="w-full pl-12 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl text-sm font-medium shadow-sm focus:ring-2 focus:ring-[#58c03f] focus:border-[#58c03f] transition-all">
        </div>

        {{-- Categorías rápidas --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1" x-show="categories.length">
            <button type="button" @click="activeCategory = null"
                    :class="activeCategory === null ? 'bg-[#58c03f] text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                    class="shrink-0 text-xs font-bold px-3.5 py-2 rounded-full transition-colors">
                Todas
            </button>
            <template x-for="cat in categories" :key="cat.id">
                <button type="button" @click="activeCategory = cat.id"
                        :class="activeCategory === cat.id ? 'bg-[#58c03f] text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50'"
                        class="shrink-0 text-xs font-bold px-3.5 py-2 rounded-full transition-colors">
                    <span x-text="cat.name"></span>
                </button>
            </template>
        </div>

        {{-- Grid de productos --}}
        <div class="flex-1 overflow-y-auto -mx-1 px-1">
            <div class="grid grid-cols-3 sm:grid-cols-4 xl:grid-cols-5 gap-2.5 pb-4">
                <template x-for="product in filteredProducts" :key="product.id">
                    <button type="button" @click="addItem(product)" :disabled="product.is_stockable && product.stock <= 0"
                            class="text-left bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm transition-all group"
                            :class="product.is_stockable && product.stock <= 0
                                ? 'opacity-50 grayscale cursor-not-allowed'
                                : 'hover:shadow-md hover:border-[#58c03f]/40 hover:-translate-y-0.5'">
                        <div class="aspect-square w-full bg-gray-50 flex items-center justify-center overflow-hidden">
                            <template x-if="product.image">
                                <img :src="product.image" :alt="product.name" class="w-full h-full object-cover" loading="lazy">
                            </template>
                            <template x-if="!product.image">
                                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </template>
                        </div>
                        <div class="p-2">
                            <div class="text-[9px] font-mono text-gray-400 mb-0.5 truncate" x-text="product.sku || '—'"></div>
                            <div class="text-xs font-bold text-gray-800 leading-snug line-clamp-2 mb-1.5 min-h-[2rem] group-hover:text-[#58c03f]" x-text="product.name"></div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-sm font-black text-gray-900" x-text="formatMoney(product.price)"></span>
                                <template x-if="product.is_stockable">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                          :class="product.stock <= 0
                                              ? 'bg-red-50 text-red-500'
                                              : (product.stock <= product.min_stock ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600')"
                                          x-text="product.stock <= 0 ? 'Agotado' : product.stock"></span>
                                </template>
                                <template x-if="!product.is_stockable">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0 bg-gray-100 text-gray-500">
                                        Servicio
                                    </span>
                                </template>
                            </div>
                        </div>
                    </button>
                </template>

                <template x-if="filteredProducts.length === 0">
                    <div class="col-span-full py-16 text-center text-gray-400 text-sm italic">
                        No se encontraron productos con existencia para "<span x-text="search"></span>".
                    </div>
                </template>
            </div>
        </div>
    </main>

    {{-- COLUMNA CARRITO / CHECKOUT --}}
    <aside class="w-[420px] shrink-0 bg-white border-l border-gray-100 flex flex-col min-h-0">

        {{-- Cliente: botón que abre un modal con buscador (antes era un <select> plano
             sin forma de buscar por nombre/RNC en una lista larga de clientes). --}}
        <div class="px-4 py-2.5 border-b border-gray-100">
            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cliente</label>
            <div class="flex gap-2">
                <button type="button" @click="clientSearch = ''; $dispatch('open-modal', 'pos-client-modal')"
                        class="flex-1 flex items-center justify-between bg-white border border-gray-200 rounded-lg text-sm py-1.5 px-3 hover:border-gray-300 transition-colors">
                    <span class="truncate text-gray-800" x-text="selectedClient?.name ?? 'Selecciona un cliente'"></span>
                    <x-heroicon-s-chevron-down class="w-4 h-4 text-gray-400 shrink-0" />
                </button>
                @if($posConfig->allow_quick_customer_creation)
                    <button type="button" @click="$dispatch('open-modal', 'quick-create-client')"
                            class="shrink-0 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        <x-heroicon-s-user-plus class="w-4 h-4 text-gray-600" />
                    </button>
                @endif
            </div>
            <template x-if="selectedClient && selectedClient.is_moroso">
                <p class="text-[11px] text-red-600 font-bold mt-1.5">⚠ Cliente con estado restringido — crédito bloqueado.</p>
            </template>
        </div>

        {{-- Carrito --}}
        <div class="flex-1 min-h-0 overflow-y-auto px-4 py-3 divide-y divide-gray-50">
            <template x-if="items.length === 0">
                <div class="py-12 text-center text-gray-400 text-xs italic">Carrito vacío. Toca un producto para agregarlo.</div>
            </template>

            <template x-for="(item, index) in items" :key="item.product_id">
                @include('livewire.sales.pos.pages.pos-workspace.partials.cart-item', ['touch' => false])
            </template>
        </div>

        {{-- Descuento global --}}
        <div class="px-4 py-2 border-t border-gray-100" x-show="allowGlobalDiscount">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Descuento Global</label>
                    @include('livewire.sales.pos.pages.pos-workspace.partials.discount-info-tooltip')
                </div>
                <div class="relative w-24">
                    <input type="number" min="0" :max="maxGlobalDiscountPct" step="0.01"
                           x-model.number="globalDiscountPercentage" @input="recalculateTotals()"
                           class="w-full text-right border-gray-200 rounded-lg text-xs py-1.5 pr-6">
                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400">%</span>
                </div>
            </div>
        </div>

        {{-- Totales y Checkout --}}
        {{-- id="pos-checkout-form": el modal de pago (pos-checkout-modal) vive fuera de este
             <form> —se comparte con la vista móvil, que tiene su propio disparador en la
             pestaña "Cobrar"— y su botón de submit lo referencia via form="pos-checkout-form"
             en vez de depender de anidamiento en el DOM. --}}
        <form id="pos-checkout-form" method="POST" :action="checkoutUrl" @submit="onSubmit" class="border-t border-gray-200 bg-white text-gray-900 p-3 space-y-2 overflow-y-auto">
            @csrf
            <input type="hidden" name="sale_date" value="{{ now()->format('Y-m-d') }}">
            <input type="hidden" name="client_id" :value="formData.client_id">
            <input type="hidden" name="payment_type" :value="formData.payment_type">
            {{-- En pago dividido, tipo_pago_id/cash_received/cash_change quedan como un
                 resumen razonable para reportes que aún leen esos campos sueltos del header
                 de la venta; el detalle real y lo que valida el backend es payments[] abajo. --}}
            <input type="hidden" name="tipo_pago_id" :value="splitPayment ? (payments[0]?.tipo_pago_id ?? '') : formData.tipo_pago_id">
            <input type="hidden" name="total_amount" :value="totals.gross">
            <input type="hidden" name="discount_total" :value="totals.discount">
            <input type="hidden" name="apply_tax" :value="usaNcf ? 1 : 0">
            <input type="hidden" name="cash_received" :value="splitPayment ? totals.total : formData.cash_received">
            <input type="hidden" name="cash_change" :value="splitPayment ? 0 : formData.cash_change">
            <input type="hidden" name="notes" value="">
            <input type="hidden" name="pos_terminal_id" value="{{ $terminal->id }}">
            <input type="hidden" name="pos_session_id" value="{{ $session->id }}">
            <input type="hidden" name="is_walkin_customer" :value="formData.client_id == walkinClientId ? 1 : 0">
            <template x-if="usaNcf">
                <input type="hidden" name="ncf_type_id" :value="formData.ncf_type_id">
            </template>
            <input type="hidden" name="client_rnc" :value="clientRnc">
            <template x-if="!splitPayment">
                <input type="hidden" name="payment_reference" :value="paymentReference">
            </template>
            <template x-if="splitPayment">
                <template x-for="(payment, pIndex) in payments" :key="'pay-'+pIndex">
                    <div>
                        <input type="hidden" :name="`payments[${pIndex}][tipo_pago_id]`" :value="payment.tipo_pago_id">
                        <input type="hidden" :name="`payments[${pIndex}][amount]`" :value="payment.amount">
                        <input type="hidden" :name="`payments[${pIndex}][reference]`" :value="payment.reference">
                    </div>
                </template>
            </template>
            <template x-for="(item, index) in items" :key="'f-'+item.product_id">
                <div>
                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                    <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                    <input type="hidden" :name="`items[${index}][price]`" :value="item.price">
                    <input type="hidden" :name="`items[${index}][discount_amount]`" :value="item.discount_amount">
                    <input type="hidden" :name="`items[${index}][discount_percentage]`" :value="item.discount_percentage">
                </div>
            </template>

            @include('livewire.sales.pos.pages.pos-workspace.partials.ncf-selector', ['showLabel' => false, 'rncInputBg' => 'bg-gray-50'])

            @include('livewire.sales.pos.pages.pos-workspace.partials.totals-summary', ['detailed' => false])

            <button type="button" @click="$dispatch('open-modal', 'pos-checkout-modal')"
                    :disabled="items.length === 0 || totals.total <= 0"
                    class="w-full py-2.5 bg-[#58c03f] hover:bg-[#4bad35] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-lg shadow-lg transition-all">
                Cobrar
            </button>

        </form>
    </aside>
</div>
