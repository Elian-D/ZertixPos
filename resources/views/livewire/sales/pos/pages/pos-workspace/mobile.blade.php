{{-- BODY MÓVIL (<lg): construido para POS táctiles portátiles (Sunmi V2 y similares).
     Comparte 100% el estado de Alpine con la vista desktop de pos-workspace/desktop.blade.php;
     solo cambia la presentación: categorías como bottom sheet, carrito como FAB + slide-over
     con pestañas Productos/Cobrar, y selector de cliente como bottom sheet con buscador. --}}
<div class="lg:hidden flex-1 flex flex-col min-h-0 relative" x-data="{ mobileCartOpen: false, mobileTab: 'products', categorySheetOpen: false, clientSheetOpen: false }">

    {{-- Buscador --}}
    <div class="shrink-0 px-3 pt-3 pb-2 space-y-2 bg-gray-50">
        <div class="relative">
            <x-heroicon-s-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input type="text"
                   x-model="search"
                   @keydown.enter.prevent="onScan()"
                   placeholder="Buscar por nombre o SKU…"
                   class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-medium shadow-sm focus:ring-2 focus:ring-[#58c03f] focus:border-[#58c03f] transition-all">
        </div>

        {{-- Categorías: se abren como bottom sheet en vez de una fila de chips, para no
             robarle ancho a la grilla de productos en pantallas angostas. --}}
        <button type="button" @click="categorySheetOpen = true"
                class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-600 shadow-sm">
            <span class="flex items-center gap-2">
                <x-heroicon-s-funnel class="w-4 h-4 text-gray-400" />
                <span x-text="activeCategory ? (categories.find(c => c.id === activeCategory)?.name ?? 'Categorías') : 'Todas las categorías'"></span>
            </span>
            <x-heroicon-s-chevron-down class="w-4 h-4 text-gray-400" />
        </button>
    </div>

    {{-- Grid de productos (2 columnas, igual estado/filtro que desktop) --}}
    <div class="flex-1 overflow-y-auto px-3 pb-28">
        <div class="grid grid-cols-2 gap-2.5">
            <template x-for="product in filteredProducts" :key="'m-'+product.id">
                <button type="button" @click="addItem(product)" :disabled="inventoryTrackingEnabled && product.is_stockable && product.stock <= 0"
                        class="text-left bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm active:scale-95 transition-transform"
                        :class="inventoryTrackingEnabled && product.is_stockable && product.stock <= 0 ? 'opacity-50 grayscale cursor-not-allowed' : ''">
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
                    <div class="p-2.5">
                        <div class="text-[9px] font-mono text-gray-400 mb-0.5 truncate" x-text="product.sku || '—'"></div>
                        <div class="text-xs font-bold text-gray-800 leading-snug line-clamp-2 mb-1.5 min-h-[2rem]" x-text="product.name"></div>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-sm font-black text-gray-900" x-text="formatMoney(grossPrice(product))"></span>
                            <template x-if="product.is_stockable && inventoryTrackingEnabled">
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                      :class="product.stock <= 0
                                          ? 'bg-red-50 text-red-500'
                                          : (product.stock <= product.min_stock ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600')"
                                      x-text="product.stock <= 0 ? 'Agotado' : product.stock"></span>
                            </template>
                            <template x-if="product.is_stockable && !inventoryTrackingEnabled">
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0 bg-gray-100 text-gray-500">Sin inventario</span>
                            </template>
                            <template x-if="!product.is_stockable">
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shrink-0 bg-gray-100 text-gray-500">Servicio</span>
                            </template>
                        </div>
                    </div>
                </button>
            </template>

            <template x-if="filteredProducts.length === 0">
                <div class="col-span-full py-16 text-center text-gray-400 text-sm italic">
                    No se encontraron productos para "<span x-text="search"></span>".
                </div>
            </template>
        </div>
    </div>

    {{-- FAB del carrito --}}
    <button type="button" @click="mobileCartOpen = true; mobileTab = 'products'"
            class="fixed bottom-5 right-5 z-40 w-16 h-16 bg-[#58c03f] text-white rounded-full shadow-2xl flex items-center justify-center active:scale-90 transition-transform">
        <x-heroicon-s-shopping-cart class="w-7 h-7" />
        <span x-show="items.length > 0" x-cloak
              class="absolute -top-1 -right-1 bg-gray-900 text-white text-[11px] font-bold w-6 h-6 rounded-full flex items-center justify-center"
              x-text="items.reduce((sum, i) => sum + i.quantity, 0)"></span>
    </button>

    {{-- Overlay + Slide-over del carrito --}}
    <div class="fixed inset-0 bg-black/40 z-40 transition-opacity"
         x-show="mobileCartOpen" x-cloak x-transition.opacity
         @click="mobileCartOpen = false"></div>

    <div class="fixed top-0 right-0 h-full w-full max-w-sm bg-gray-50 z-50 shadow-2xl flex flex-col transition-transform duration-300"
         x-cloak
         :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full'">

        <div class="shrink-0 flex items-center justify-between px-4 h-14 border-b border-gray-100 bg-white">
            <div class="flex items-center gap-2">
                <x-heroicon-s-shopping-cart class="w-5 h-5 text-[#58c03f]" />
                <h2 class="font-bold text-gray-900">Carrito</h2>
                <span class="text-xs text-gray-400" x-text="'(' + items.reduce((sum, i) => sum + i.quantity, 0) + ')'"></span>
            </div>
            <button type="button" @click="mobileCartOpen = false" class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500">
                <x-heroicon-s-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Tabs: Productos (todo el espacio) / Cobrar (cliente, NCF, desglose) —
             separarlos evita que en una pantalla de ~6" quepan a la fuerza el listado
             de productos, el selector de cliente, NCF y el desglose todos a la vez. --}}
        <div class="shrink-0 flex gap-1.5 px-3 py-2 bg-gray-100 border-b border-gray-200">
            <button type="button" @click="mobileTab = 'products'"
                    :class="mobileTab === 'products' ? 'bg-[#58c03f] text-white' : 'text-gray-500'"
                    class="flex-1 py-2 rounded-lg text-xs font-bold transition-all">
                Productos
            </button>
            <button type="button" @click="mobileTab = 'checkout'"
                    :class="mobileTab === 'checkout' ? 'bg-[#58c03f] text-white' : 'text-gray-500'"
                    class="flex-1 py-2 rounded-lg text-xs font-bold transition-all">
                Cobrar
            </button>
        </div>

        {{-- TAB: Productos --}}
        <div x-show="mobileTab === 'products'" x-cloak class="flex-1 min-h-0 flex flex-col">
            <div class="flex-1 min-h-0 overflow-y-auto px-4 py-3 divide-y divide-gray-100">
                <template x-if="items.length === 0">
                    <div class="py-12 text-center text-gray-400 text-xs italic">Carrito vacío. Toca un producto para agregarlo.</div>
                </template>

                <template x-for="(item, index) in items" :key="'mc-'+item.product_id">
                    @include('livewire.sales.pos.pages.pos-workspace.partials.cart-item', ['touch' => true])
                </template>
            </div>

            {{-- Mini total fijo abajo, siempre visible en esta pestaña --}}
            <div class="shrink-0 px-4 py-3 bg-white border-t border-gray-200 flex justify-between items-center">
                <span class="font-bold text-gray-700">Total</span>
                <span class="text-xl font-black font-mono text-[#58c03f]" x-text="formatMoney(totals.total)"></span>
            </div>
        </div>

        {{-- TAB: Cobrar (cliente + NCF + descuento + desglose; abre el mismo modal de pago) --}}
        <div x-show="mobileTab === 'checkout'" x-cloak class="flex-1 min-h-0 overflow-y-auto px-4 py-3 space-y-3">

            {{-- Cliente: botón que abre bottom sheet con buscador --}}
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Cliente</label>
                    @if($posConfig->allow_quick_customer_creation)
                        <button type="button" @click="$dispatch('open-modal', 'quick-create-client')"
                                class="text-[#58c03f] text-xs font-bold flex items-center gap-1">
                            <x-heroicon-s-user-plus class="w-3.5 h-3.5" /> Nuevo
                        </button>
                    @endif
                </div>
                <button type="button" @click="clientSheetOpen = true"
                        class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center justify-between text-sm">
                    <span class="truncate text-gray-800" x-text="selectedClient?.name ?? 'Selecciona un cliente'"></span>
                    <x-heroicon-s-chevron-down class="w-4 h-4 text-gray-400 shrink-0" />
                </button>
                <template x-if="selectedClient && selectedClient.is_moroso">
                    <p class="text-[11px] text-red-600 font-bold">⚠ Cliente con estado restringido — crédito bloqueado.</p>
                </template>
            </div>

            @include('livewire.sales.pos.pages.pos-workspace.partials.ncf-selector', ['showLabel' => true, 'rncInputBg' => 'bg-white'])

            {{-- Descuento global --}}
            <div x-show="allowGlobalDiscount" class="flex items-center justify-between gap-3">
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

            @include('livewire.sales.pos.pages.pos-workspace.partials.totals-summary', ['detailed' => true])

            <button type="button" @click="$dispatch('open-modal', 'pos-checkout-modal')"
                    :disabled="items.length === 0 || totals.total <= 0"
                    class="w-full py-3.5 bg-[#58c03f] active:bg-[#4bad35] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg transition-all">
                Confirmar Cobro
            </button>
        </div>
    </div>

    {{-- Bottom sheet: Categorías --}}
    <div class="fixed inset-0 z-[70]" x-show="categorySheetOpen" x-cloak
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/40" @click="categorySheetOpen = false"></div>
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-2xl flex flex-col max-h-[75vh] transition-transform duration-300"
             :class="categorySheetOpen ? 'translate-y-0' : 'translate-y-full'">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mt-3 mb-2"></div>
            <div class="px-4 pb-3 flex items-center justify-between border-b border-gray-100">
                <h3 class="font-bold text-gray-900">Categorías</h3>
                <button type="button" @click="categorySheetOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <x-heroicon-s-x-mark class="w-4 h-4 text-gray-500" />
                </button>
            </div>
            <div class="overflow-y-auto p-3 space-y-1 pb-6">
                <button type="button" @click="activeCategory = null; categorySheetOpen = false"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-bold"
                        :class="activeCategory === null ? 'bg-emerald-50 text-[#58c03f]' : 'text-gray-700 hover:bg-gray-50'">
                    <span>Todas</span>
                    <x-heroicon-s-check-circle class="w-5 h-5" x-show="activeCategory === null" />
                </button>
                <template x-for="cat in categories" :key="'cs-'+cat.id">
                    <button type="button" @click="activeCategory = cat.id; categorySheetOpen = false"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm"
                            :class="activeCategory === cat.id ? 'bg-emerald-50 text-[#58c03f] font-bold' : 'text-gray-700 hover:bg-gray-50'">
                        <span x-text="cat.name"></span>
                        <x-heroicon-s-check-circle class="w-5 h-5" x-show="activeCategory === cat.id" />
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Bottom sheet: Cliente (con buscador) --}}
    <div class="fixed inset-0 z-[70]" x-show="clientSheetOpen" x-cloak
         x-init="$watch('clientSheetOpen', (open) => { if (!open) clientSearch = ''; })"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/40" @click="clientSheetOpen = false"></div>
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-2xl flex flex-col max-h-[80vh] transition-transform duration-300"
             :class="clientSheetOpen ? 'translate-y-0' : 'translate-y-full'">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mt-3 mb-2"></div>
            <div class="px-4 pb-3 flex items-center justify-between border-b border-gray-100">
                <h3 class="font-bold text-gray-900">Cliente</h3>
                <button type="button" @click="clientSheetOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <x-heroicon-s-x-mark class="w-4 h-4 text-gray-500" />
                </button>
            </div>
            <div class="px-4 py-2.5 border-b border-gray-100">
                @include('livewire.sales.pos.pages.pos-workspace.partials.client-search-input', ['autofocus' => false])
            </div>
            <div class="overflow-y-auto p-3 space-y-1 pb-6">
                @include('livewire.sales.pos.pages.pos-workspace.partials.client-results-list', ['closeAction' => 'clientSheetOpen = false'])
            </div>
        </div>
    </div>

    {{-- Bottom sheet: Menú (kebab del header) — Cobrar Deudas/Ver Caja/Bloquear hoy;
         punto de entrada preparado para lo que se sume después (cargar/crear
         cotizaciones, lector QR, etc.) sin volver a apretar botones sueltos en el
         header. menuSheetOpen vive en el estado raíz (pos-workspace.blade.php),
         porque el botón que lo abre está en el header compartido. --}}
    <div class="fixed inset-0 z-[70]" x-show="menuSheetOpen" x-cloak
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/40" @click="menuSheetOpen = false"></div>
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-2xl flex flex-col transition-transform duration-300"
             :class="menuSheetOpen ? 'translate-y-0' : 'translate-y-full'">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mt-3 mb-2"></div>
            <div class="px-4 pb-3 flex items-center justify-between border-b border-gray-100">
                <h3 class="font-bold text-gray-900">Más opciones</h3>
                <button type="button" @click="menuSheetOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                    <x-heroicon-s-x-mark class="w-4 h-4 text-gray-500" />
                </button>
            </div>
            <div class="p-3 space-y-1 pb-6">
                @if($canCollect)
                    <button type="button"
                            @click="menuSheetOpen = false; resetCollectPanel(); $dispatch('open-modal', 'pos-collect-modal')"
                            class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold text-amber-700 bg-amber-50 hover:bg-amber-100">
                        <x-heroicon-s-currency-dollar class="w-5 h-5" />
                        <span>Cobrar Deudas</span>
                    </button>
                @endif

                <a href="{{ route('sales.pos.sessions.show', $session) }}"
                   class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50">
                    <x-heroicon-s-banknotes class="w-5 h-5 text-gray-400" />
                    <span>Ver Caja</span>
                </a>

                @if($terminal->requiresPinVerification())
                    <a href="{{ route('sales.pos.lock', $terminal) }}"
                       class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50">
                        <x-heroicon-s-lock-closed class="w-5 h-5 text-gray-400" />
                        <span>Bloquear</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
