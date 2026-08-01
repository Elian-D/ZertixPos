<div class="h-screen bg-gray-50 flex flex-col font-sans selection:bg-[#58c03f] selection:text-white"
     x-data="posWorkspace({
        products: @js($products),
        clients: @js($clients),
        categories: @js($categories),
        tipoPagos: @js($tipoPagos),
        ncfTypes: @js($ncfTypes),
        usaNcf: @js($usaNcf),
        taxRate: @js($taxRate),
        maxItemDiscountPct: @js($maxItemDiscountPercentage),
        maxGlobalDiscountPct: @js($maxGlobalDiscountPercentage),
        allowItemDiscount: @js((bool) $allowItemDiscount),
        allowGlobalDiscount: @js((bool) $allowGlobalDiscount),
        walkinClientId: @js($walkinClientId),
        defaultNcfTypeId: @js($terminal->default_ncf_type_id),
        checkoutUrl: @js(route('sales.pos.checkout.store', $terminal)),
        heartbeatUrl: @js(route('sales.pos.heartbeat')),
        lockUrl: @js(route('sales.pos.lock', $terminal)),
        requiresPin: @js($terminal->requiresPinVerification()),
        printUrl: @js($lastInvoiceId ? route('sales.invoices.print', ['invoice' => $lastInvoiceId, 'format' => 'ticket']) : null),
        autoPrint: @js((bool) ($posConfig?->auto_print_receipt ?? false)),
        lastSale: @js($lastSale),
        terminalId: @js($terminal->id),
     })"
     x-init="init()">

    <x-ui.toasts />

    {{-- HEADER (misma barra en todos los tamaños; solo se colapsa el texto secundario en móvil) --}}
    <header class="shrink-0 bg-white border-b border-gray-100 px-3 sm:px-6 py-2.5 sm:py-3 flex items-center justify-between shadow-sm z-20">
        <div class="flex items-center gap-2 sm:gap-4 min-w-0">
            {{-- Volver al backoffice: la sesión de caja queda abierta (es un registro en DB,
                 no depende de esta pantalla), así que salir aquí es seguro. No se manda al
                 Lobby porque, con la sesión todavía abierta, el Lobby solo te regresaría
                 directo a este mismo Workspace — "Bloquear" ya cubre el flujo real de salir
                 del terminal de forma segura. --}}
            <a href="{{ route('dashboard') }}"
               title="Volver al backoffice"
               class="w-8 h-8 sm:w-9 sm:h-9 shrink-0 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                <x-heroicon-s-arrow-left class="w-5 h-5" />
            </a>

            <div class="hidden sm:flex w-10 h-10 rounded-xl items-center justify-center bg-emerald-50 text-[#58c03f] shrink-0">
                <x-heroicon-s-building-storefront class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-gray-900 leading-tight truncate">{{ $terminal->name }}</h1>
                <p class="hidden sm:block text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                    Cajero: {{ auth()->user()->name }} · Turno #{{ $session->id }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
            <div class="hidden md:block text-right text-xs text-gray-500 font-mono" x-text="clock"></div>

            <a href="{{ route('sales.pos.sessions.show', $session) }}"
               title="Ver Caja"
               class="flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-gray-800 px-2.5 sm:px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                <x-heroicon-s-banknotes class="w-4 h-4 sm:hidden" />
                <span class="hidden sm:inline">Ver Caja</span>
            </a>

            @if($terminal->requiresPinVerification())
                <a href="{{ route('sales.pos.lock', $terminal) }}"
                   title="Bloquear"
                   class="flex items-center gap-1.5 text-xs font-bold text-white bg-gray-800 hover:bg-gray-900 px-2.5 sm:px-3 py-2 rounded-lg transition-colors">
                    <x-heroicon-s-lock-closed class="w-4 h-4" />
                    <span class="hidden sm:inline">Bloquear</span>
                </a>
            @endif
        </div>
    </header>

    {{-- Fase 7.10: el Workspace se dividió en subvistas para eliminar la duplicación real
         de HTML entre desktop y móvil (selector NCF, resumen de totales, fila de carrito,
         buscador de cliente estaban pegados dos veces casi idénticos). Todas comparten el
         mismo x-data="posWorkspace(...)" de este elemento raíz — @include es una inclusión
         de servidor (concatenación de string antes de llegar al navegador), así que no
         rompe nada del estado de Alpine con tal de que queden anidadas aquí dentro. --}}
    @include('livewire.sales.pos.pages.pos-workspace.desktop')
    @include('livewire.sales.pos.pages.pos-workspace.mobile')

    @include('livewire.sales.pos.pages.pos-workspace.client-modal')
    @include('livewire.sales.pos.pages.pos-workspace.checkout-modal')

    {{-- Modal Cliente Rápido (Fase 7.5) --}}
    @include('sales.pos.partials.modal-quick-client')

    @include('livewire.sales.pos.pages.pos-workspace.success-modal')

    <script>
        function posWorkspace(config) {
            return {
                ...config,
                search: '',
                activeCategory: null,
                items: [],
                globalDiscountPercentage: 0,
                clock: '',
                formData: {
                    client_id: config.walkinClientId,
                    payment_type: 'cash',
                    tipo_pago_id: (config.tipoPagos.find(t => t.slug === 'efectivo') ?? config.tipoPagos[0])?.id ?? null,
                    ncf_type_id: config.defaultNcfTypeId ?? '',
                    cash_received: 0,
                    cash_change: 0,
                },
                cashBuffer: '',
                cashReceivedInput: '',
                paymentReference: '',
                splitPayment: false,
                payments: [],
                clientSearch: '',
                submitting: false,
                ncfChoice: 'none',
                clientRnc: '',
                rncLookup: { loading: false, error: '', data: null },
                totals: { gross: 0, discount: 0, subtotal: 0, tax: 0, total: 0 },

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                    // Sin PIN configurado no hay nada que proteger con auto-bloqueo por inactividad.
                    if (this.requiresPin) {
                        this.startInactivityWatch();
                    }
                    this.startHeartbeat();

                    // Resuelve el radio de NCF inicial a partir del default de la terminal (si aplica).
                    if (this.formData.ncf_type_id) {
                        const nt = this.ncfTypes.find(n => n.id === this.formData.ncf_type_id);
                        if (nt) this.ncfChoice = ['01', '31'].includes(nt.code) ? 'credito' : 'consumo';
                    }

                    // window.__posClientCreatedBound: este mismo `init()` puede correr más de una
                    // vez (doble montaje de Alpine/Livewire, igual que el caso documentado abajo
                    // para la auto-impresión). Sin esta guardia, cada evento real dispara TANTOS
                    // handlers como veces se registró addEventListener, duplicando al cliente
                    // recién creado en `this.clients` — confirmado en pruebas: el arreglo crecía
                    // de a 2 (o más) por cada cliente nuevo, no de a 1.
                    if (!window.__posClientCreatedBound) {
                        window.__posClientCreatedBound = true;
                        window.addEventListener('pos-client-created', (e) => {
                            // Idempotente por id: si por lo que sea el evento llega repetido
                            // (doble submit, reintento de red), no duplica la entrada en la lista.
                            if (this.clients.some(c => c.id === e.detail.id)) return;

                            this.clients.unshift(e.detail);
                            // $nextTick: si fijamos el id en el mismo tick que el unshift, x-model
                            // intenta seleccionar el <option> antes de que Alpine termine de insertarlo
                            // en el DOM (x-for todavía no corrió) y el <select> se queda en el primero.
                            this.$nextTick(() => { this.formData.client_id = e.detail.id; });
                        });
                    }

                    // Guarda por sessionStorage (no solo por sesión de servidor): si por lo que sea
                    // este componente/x-init se procesa más de una vez para la misma factura
                    // (doble montaje de Alpine, doble render, etc.), solo la primera abre el ticket.
                    if (this.autoPrint && this.printUrl) {
                        const dedupeKey = 'pos-auto-printed:' + this.printUrl;
                        if (sessionStorage.getItem(dedupeKey)) {
                            return;
                        }
                        sessionStorage.setItem(dedupeKey, '1');
                        window.open(this.printUrl, '_blank');
                    }

                    // Feedback de venta recién completada (el Workspace se recarga limpio tras el checkout).
                    if (this.lastSale) {
                        this.$nextTick(() => this.$dispatch('open-modal', 'pos-success-modal'));
                    }
                },

                updateClock() {
                    this.clock = new Date().toTimeString().split(' ')[0];
                },

                // --- 7.2 Product Engine ---
                get filteredProducts() {
                    const term = this.search.trim().toLowerCase();
                    return this.products.filter(p => {
                        if (this.activeCategory && p.category_id !== this.activeCategory) return false;
                        if (!term) return true;
                        return p.name.toLowerCase().includes(term) || (p.sku || '').toLowerCase().includes(term);
                    }).slice(0, 60);
                },

                onScan() {
                    const term = this.search.trim().toLowerCase();
                    if (!term) return;
                    const exact = this.products.find(p => (p.sku || '').toLowerCase() === term);
                    if (exact) {
                        // Lectura por lector de código de barras: se mantiene el foco en el buscador
                        // para poder seguir escaneando sin tocar el mouse.
                        this.addItem(exact, 'search');
                        this.search = '';
                    }
                },

                // --- 7.3 Cart Engine ---
                // focusTarget: 'search' (flujo de escáner, mantiene el foco para seguir leyendo códigos)
                // o 'received' (clic con mouse, el foco pasa directo al campo de efectivo recibido).
                addItem(product, focusTarget = 'received') {
                    if (product.is_stockable && product.stock <= 0) return;

                    const existing = this.items.find(i => i.product_id === product.id);
                    if (existing) {
                        existing.quantity++;
                    } else {
                        this.items.push({
                            product_id: product.id,
                            name: product.name,
                            price: product.price,
                            stock: product.stock,
                            is_stockable: product.is_stockable,
                            quantity: 1,
                            discount_percentage: 0,
                            discount_amount: 0,
                        });
                    }
                    this.recalculateTotals();

                    this.$nextTick(() => {
                        if (focusTarget === 'search') {
                            this.$refs.searchInput?.focus();
                        } else if (this.formData.payment_type === 'cash' && this.isCashMethod) {
                            this.$refs.cashReceivedInput?.focus();
                        }
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.recalculateTotals();
                },

                incrementQty(index) {
                    this.items[index].quantity++;
                    this.recalculateTotals();
                },

                decrementQty(index) {
                    this.items[index].quantity = Math.max(1, this.items[index].quantity - 1);
                    this.recalculateTotals();
                },

                recalculateTotals() {
                    let bruto = 0;
                    let itemDiscounts = 0;

                    this.items.forEach(item => {
                        item.quantity = Math.max(1, parseFloat(item.quantity) || 1);
                        let pct = this.allowItemDiscount ? Math.min(this.maxItemDiscountPct, Math.max(0, parseFloat(item.discount_percentage) || 0)) : 0;
                        item.discount_percentage = pct;

                        const lineBruto = item.price * item.quantity;
                        item.discount_amount = (lineBruto * pct) / 100;

                        bruto += lineBruto;
                        itemDiscounts += item.discount_amount;
                    });

                    // Descuento global — Regla de Exclusión (única política operativa por ahora,
                    // ver 11.2.5): se reparte ÚNICAMENTE entre los ítems SIN descuento propio
                    // (discount_percentage == 0). Un ítem con descuento individual queda excluido
                    // del reparto global, así el backend puede distinguir con certeza "descuento
                    // por ítem" de "descuento global" mirando solo discount_percentage (nunca
                    // tocado por este reparto) — ver SaleService::validateDiscounts().
                    //
                    // (Política alternativa 'cascade' — reparto sobre el subtotal restante tras
                    // descuentos por ítem, incluyendo ítems ya descontados — queda documentada
                    // pero sin implementar ni selector en la UI hasta que haya demanda real de
                    // una terminal con discount_policy = 'cascade'.)
                    let globalAmount = 0;
                    if (this.allowGlobalDiscount && this.globalDiscountPercentage > 0) {
                        const gPct = Math.min(this.maxGlobalDiscountPct, Math.max(0, parseFloat(this.globalDiscountPercentage) || 0));
                        this.globalDiscountPercentage = gPct;

                        const eligibleItems = this.items.filter(item => (item.discount_percentage || 0) === 0);
                        const eligibleBase = eligibleItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);

                        if (eligibleBase > 0) {
                            globalAmount = (eligibleBase * gPct) / 100;

                            eligibleItems.forEach(item => {
                                const lineBruto = item.price * item.quantity;
                                const share = (lineBruto / eligibleBase) * globalAmount;
                                item.discount_amount += share;
                            });
                        }
                    }

                    const discountTotal = itemDiscounts + globalAmount;
                    const netSubtotal = bruto - discountTotal;
                    const tax = this.usaNcf ? netSubtotal * (this.taxRate / 100) : 0;

                    this.totals = {
                        gross: bruto,
                        discount: discountTotal,
                        subtotal: netSubtotal,
                        tax: tax,
                        total: netSubtotal + tax,
                    };

                    this.calculateChange();
                },

                // --- 7.4 Checkout Engine ---
                get isCashMethod() {
                    const tp = this.tipoPagos.find(t => t.id === this.formData.tipo_pago_id);
                    return tp ? tp.slug === 'efectivo' : true;
                },

                onPaymentTypeChange() {
                    if (this.formData.payment_type === 'credit') {
                        this.formData.cash_received = 0;
                        this.formData.cash_change = 0;
                        this.cashBuffer = '';
                        this.cashReceivedInput = '';
                        this.paymentReference = '';
                        // Crédito (CxC) es un flujo de "no cobrar ahora" — dividir pago no aplica.
                        this.disableSplitPayment();
                    }
                },

                // --- Pago dividido (multi-método) ---
                // Cada línea es exactamente lo que se aplica a la venta (sin "vuelto" por línea,
                // a diferencia del pago único): así se evita mezclar dos conceptos de cambio
                // distintos y la vista se mantiene simple. Deben sumar el total exacto.
                enableSplitPayment() {
                    this.splitPayment = true;
                    this.payments = [{
                        tipo_pago_id: this.formData.tipo_pago_id,
                        amount: this.totals.total,
                        reference: this.paymentReference,
                    }];
                },

                disableSplitPayment() {
                    this.splitPayment = false;
                    this.payments = [];
                },

                get paymentsTotal() {
                    return this.payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
                },

                get paymentsRemaining() {
                    return +(this.totals.total - this.paymentsTotal).toFixed(2);
                },

                addPaymentLine() {
                    this.payments.push({
                        tipo_pago_id: this.formData.tipo_pago_id,
                        amount: Math.max(0, this.paymentsRemaining),
                        reference: '',
                    });
                },

                removePaymentLine(index) {
                    this.payments.splice(index, 1);
                },

                paymentLineIsCash(payment) {
                    const tp = this.tipoPagos.find(t => t.id === payment.tipo_pago_id);
                    return tp ? tp.slug === 'efectivo' : true;
                },

                onTipoPagoChange() {
                    // Cambiar de método de pago invalida cualquier referencia ya escrita
                    // (no tiene sentido arrastrar el # de autorización de una tarjeta a un cheque).
                    this.paymentReference = '';

                    if (!this.isCashMethod) {
                        this.formData.cash_received = this.totals.total;
                        this.formData.cash_change = 0;
                    } else {
                        this.syncCashReceived();
                    }
                },

                // Numpad táctil: construye el monto dígito a dígito en centavos.
                cashDigit(n) {
                    this.cashBuffer = (this.cashBuffer + n).slice(0, 9);
                    this.syncCashReceived();
                },
                cashClear() {
                    this.cashBuffer = '';
                    this.syncCashReceived();
                },
                cashBackspace() {
                    this.cashBuffer = this.cashBuffer.slice(0, -1);
                    this.syncCashReceived();
                },
                syncCashReceived() {
                    const value = (parseInt(this.cashBuffer || '0', 10)) / 100;
                    this.formData.cash_received = value;
                    this.cashReceivedInput = this.cashBuffer ? value.toFixed(2) : '';
                    this.calculateChange();
                },

                // Tecleo real desde teclado físico: el vendedor escribe el monto directo.
                onCashInput(event) {
                    let raw = event.target.value.replace(/[^0-9.]/g, '');
                    const firstDot = raw.indexOf('.');
                    if (firstDot !== -1) {
                        raw = raw.slice(0, firstDot + 1) + raw.slice(firstDot + 1).replace(/\./g, '');
                    }

                    this.cashReceivedInput = raw;
                    const numeric = parseFloat(raw) || 0;
                    this.formData.cash_received = numeric;
                    this.cashBuffer = String(Math.round(numeric * 100));
                    this.calculateChange();
                },

                // Botones de denominación del modal de cobro: van sumando al monto ya recibido
                // (ej. dos billetes de 500 = 1000), para no depender de escribir todo con el teclado.
                addCashDenomination(amount) {
                    const next = (parseFloat(this.formData.cash_received) || 0) + amount;
                    this.formData.cash_received = next;
                    this.cashReceivedInput = next.toFixed(2);
                    this.cashBuffer = String(Math.round(next * 100));
                    this.calculateChange();
                },

                clearCashReceived() {
                    this.formData.cash_received = 0;
                    this.cashReceivedInput = '';
                    this.cashBuffer = '';
                    this.calculateChange();
                },

                calculateChange() {
                    const received = parseFloat(this.formData.cash_received) || 0;
                    this.formData.cash_change = received > this.totals.total
                        ? +(received - this.totals.total).toFixed(2)
                        : 0;
                },

                // --- 7.5 Customer Engine ---
                get selectedClient() {
                    return this.clients.find(c => c.id == this.formData.client_id) || null;
                },

                // Buscador de clientes (modal desktop + bottom sheet móvil comparten este getter).
                get filteredClients() {
                    const term = this.clientSearch.trim().toLowerCase();
                    if (!term) return this.clients;
                    return this.clients.filter(c =>
                        c.name.toLowerCase().includes(term) || (c.tax_id || '').toLowerCase().includes(term)
                    );
                },

                onClientChange() {
                    if (this.formData.payment_type === 'credit' && this.selectedClient?.id == this.walkinClientId) {
                        this.formData.payment_type = 'cash';
                        this.onPaymentTypeChange();
                    }
                    // Consumidor Final no puede llevar Crédito Fiscal (no tiene RNC propio).
                    if (this.ncfChoice === 'credito' && this.selectedClient?.id == this.walkinClientId) {
                        this.selectNcf('none');
                    }
                },

                get exceedsCreditLimit() {
                    if (this.formData.payment_type !== 'credit' || !this.selectedClient) return false;
                    return this.totals.total > parseFloat(this.selectedClient.available || 0);
                },

                // --- NCF: Sin Comprobante / Consumo (B02) / Crédito Fiscal (B01) ---
                get consumoNcfType() {
                    return this.ncfTypes.find(n => ['02', '32'].includes(n.code)) || null;
                },

                get creditoFiscalNcfType() {
                    return this.ncfTypes.find(n => ['01', '31'].includes(n.code)) || null;
                },

                selectNcf(choice) {
                    this.ncfChoice = choice;
                    this.clientRnc = '';
                    this.rncLookup = { loading: false, error: '', data: null };

                    if (choice === 'consumo') {
                        this.formData.ncf_type_id = this.consumoNcfType?.id ?? '';
                    } else if (choice === 'credito') {
                        this.formData.ncf_type_id = this.creditoFiscalNcfType?.id ?? '';
                    } else {
                        this.formData.ncf_type_id = '';
                    }
                },

                async lookupRnc() {
                    const rnc = this.clientRnc.replace(/\D/g, '');
                    if (rnc.length < 9) {
                        this.rncLookup = { loading: false, error: 'RNC/Cédula inválido (mínimo 9 dígitos).', data: null };
                        return;
                    }

                    this.rncLookup = { loading: true, error: '', data: null };
                    try {
                        const response = await fetch(`{{ route('sales.pos.rnc-lookup') }}?rnc=${rnc}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await response.json();

                        if (!response.ok || data.error) {
                            this.rncLookup = { loading: false, error: data.mensaje || 'No se pudo verificar el RNC.', data: null };
                            return;
                        }

                        this.rncLookup = { loading: false, error: '', data };
                        this.clientRnc = data.rnc_consultado || this.clientRnc;
                    } catch (e) {
                        this.rncLookup = { loading: false, error: 'Error de conexión al verificar el RNC.', data: null };
                    }
                },

                get isSubmitDisabled() {
                    if (this.items.length === 0 || this.totals.total <= 0) return true;
                    if (this.items.some(i => i.is_stockable && i.quantity > i.stock)) return true;

                    // Crédito Fiscal exige un RNC ya sea en archivo o verificado en esta misma venta.
                    if (this.ncfChoice === 'credito' && !this.selectedClient?.tax_id && !this.rncLookup.data) {
                        return true;
                    }

                    if (this.formData.payment_type === 'credit') {
                        return !this.selectedClient || this.selectedClient.id == this.walkinClientId
                            || this.selectedClient.is_moroso || this.exceedsCreditLimit;
                    }

                    if (this.splitPayment) {
                        if (this.payments.length === 0) return true;
                        if (Math.abs(this.paymentsRemaining) > 0.01) return true;
                        return this.payments.some(p => !this.paymentLineIsCash(p) && !p.reference?.trim());
                    }

                    if (!this.isCashMethod && !this.paymentReference.trim()) return true;

                    return this.isCashMethod && this.formData.cash_received < this.totals.total;
                },

                onSubmit(event) {
                    if (this.isSubmitDisabled || this.submitting) {
                        event.preventDefault();
                        return;
                    }
                    // Evita doble venta si el cajero toca "Cobrar" dos veces mientras el POST está en curso.
                    this.submitting = true;
                },

                // --- 7.7 Session Security ---
                startInactivityWatch() {
                    let timer;
                    const reset = () => {
                        clearTimeout(timer);
                        timer = setTimeout(() => { window.location.href = this.lockUrl; }, 10 * 60 * 1000);
                    };
                    ['mousemove', 'keydown', 'touchstart', 'click'].forEach(evt => window.addEventListener(evt, reset));
                    reset();
                },

                startHeartbeat() {
                    setInterval(() => {
                        fetch(this.heartbeatUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ terminal_id: this.terminalId }),
                        }).catch(() => {});
                    }, 2 * 60 * 1000);
                },

                formatMoney(amount) {
                    return '{{ general_config()->currency_symbol ?? "$" }}' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(amount || 0);
                },
            };
        }
    </script>
</div>
