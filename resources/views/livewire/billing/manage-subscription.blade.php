@php
    // Mismo criterio de contenido que resources/views/livewire/install/partials/step-plan.blade.php
    // (Wizard, REQ-4): "Todo lo de X, más:" progresivo, línea de usuarios explícita,
    // el plan del medio como recomendado — para que el mensaje no cambie entre
    // el Wizard y esta vista.
    $plans = $this->plans;
    $planCount = $plans->count();
    $highlightIndex = intdiv($planCount, 2);
    $usersLine = function ($plan) {
        return is_null($plan->users_limit)
            ? 'Usuarios ilimitados — sumá a todo tu equipo'
            : ($plan->users_limit === 1
                ? 'Un usuario con acceso al sistema'
                : "Hasta {$plan->users_limit} usuarios con acceso al sistema");
    };

    $status = $this->status;
    [$statusVariant, $statusLabel, $ctaLabel] = match ($status) {
        'active' => ['success', 'Activa • Pago Recurrente', 'Cambiar de Plan'],
        'trialing' => ['warning', 'Período de Prueba', 'Activar Plan Definitivo'],
        default => ['error', 'Vencida • Acceso Restringido', 'Reactivar Suscripción'],
    };
@endphp

<div class="flex flex-col gap-6">
    <x-ui.page-header
        title="Suscripción y Facturación"
        description="Gestioná tu plan, el estado de tu cuenta y tu método de pago."
    >
        @if ($view !== 'summary' && $this->currentSubscription)
            <x-slot:actions>
                <x-ui.button variant="secondary" appearance="ghost" wire:click="backToSummary" iconLeft="heroicon-s-arrow-left">
                    Volver al resumen
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($errorMessage)
        <div class="bg-state-error/5 border border-state-error/20 text-state-error text-sm rounded-lg p-3.5 flex items-center gap-2">
            <x-heroicon-s-exclamation-triangle class="w-4 h-4 flex-shrink-0" />
            {{ $errorMessage }}
        </div>
    @endif

    {{-- ============================================================
         RESUMEN — adapta banner/CTA a active/trialing/past_due sin
         duplicar la regla de bloqueo real (esa sigue solo en
         EnsureSubscriptionActive, REQ-3.5). Solo se muestra si ya hubo
         alguna suscripción real alguna vez (ver ManageSubscription::mount()).
    ============================================================ --}}
    @if ($view === 'summary' && $this->currentSubscription)
        {{-- REQ-4.7.1 — cancelación o downgrade ya confirmados, pero
             todavía no aplicados: el acceso/plan actual sigue intacto hasta
             `current_period_ends_at`, esto solo lo hace visible. Ninguno de
             los dos bloquea nada — son informativos, distinto del banner de
             `past_due` de abajo. --}}
        @if ($this->isCancellationScheduled)
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 sm:p-5 flex items-start gap-3.5">
                <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-s-clock class="w-5 h-5" />
                </span>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Cancelación programada</span>
                    <p class="text-sm text-gray-700 font-medium mt-0.5">
                        Tu suscripción se cancela el {{ $this->periodEndsAt?->translatedFormat('d \d\e F, Y') }} — hasta esa fecha sigues con acceso completo, y no se te va a cobrar de nuevo.
                    </p>
                </div>
            </div>
        @elseif ($this->scheduledPlan)
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 sm:p-5 flex items-start gap-3.5">
                <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-s-arrow-trending-down class="w-5 h-5" />
                </span>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Cambio de plan programado</span>
                    <p class="text-sm text-gray-700 font-medium mt-0.5">
                        El {{ $this->periodEndsAt?->translatedFormat('d \d\e F, Y') }} pasás a <strong>{{ $this->scheduledPlan->name }}</strong> (USD${{ number_format((float) $this->scheduledPlan->price, 0) }}/mes). Hasta esa fecha sigues con todo lo de {{ $this->currentPlan?->name }}.
                    </p>
                </div>
            </div>
        @endif

        @if ($status === 'past_due')
            <div class="rounded-xl bg-state-error/5 border border-state-error/20 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5">
                    <span class="w-10 h-10 rounded-xl bg-state-error/10 text-state-error flex items-center justify-center flex-shrink-0">
                        <x-heroicon-s-shield-exclamation class="w-5 h-5" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold uppercase tracking-wider text-state-error">Acceso Restringido</span>
                            <x-ui.badge variant="error" size="sm" :dot="false">Modo solo lectura</x-ui.badge>
                        </div>
                        <p class="text-sm text-gray-700 font-medium mt-0.5">
                            Tu suscripción a {{ $this->currentPlan?->name ?? 'ZertixPOS' }} venció. El acceso queda pausado hasta regularizar el pago.
                        </p>
                    </div>
                </div>
                <x-ui.button variant="error" wire:click="showPlans" iconRight="heroicon-s-arrow-right" class="flex-shrink-0">
                    Regularizar Pago
                </x-ui.button>
            </div>
        @endif

        {{-- Tarjeta hero del plan actual — círculos difuminados decorativos
             (navy + verde de marca), mismo recurso visual que el mockup
             "Resumen de Suscripción" para que la card no se sienta plana. --}}
        <div class="relative overflow-hidden rounded-2xl bg-white border border-gray-100 shadow-sm p-7 lg:p-10">
            <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-zertix-secondary/5 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 bottom-0 w-80 h-80 rounded-full bg-zertix-primary/10 blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                <div class="flex flex-col max-w-2xl">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Plan Actual Contratado</span>
                        <x-ui.badge :variant="$statusVariant" size="sm" :dot="true">{{ $statusLabel }}</x-ui.badge>
                    </div>

                    <div class="flex flex-wrap items-baseline gap-3 my-1">
                        <h2 class="text-3xl lg:text-4xl font-bold text-zertix-secondary tracking-tight">{{ $this->currentPlan?->name }}</h2>
                        <span class="text-sm text-gray-400 font-medium">/ Facturación Mensual</span>
                    </div>

                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        @if ($status === 'trialing')
                            Estás disfrutando todas las funcionalidades de ZertixPOS sin costo durante tu período de prueba. Activá tu suscripción para continuar sin interrupciones al terminar.
                        @else
                            Todo lo que tu negocio necesita para vender, facturar y controlar tu inventario en un solo lugar.
                        @endif
                    </p>

                    <div class="flex flex-wrap items-center gap-3 mt-6">
                        @if ($this->currentSubscription->gateway === 'paypal' && $this->currentSubscription->gateway_subscription_id)
                            <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-100">
                                <span class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-zertix-secondary flex-shrink-0">
                                    <x-heroicon-s-credit-card class="w-4 h-4" />
                                </span>
                                <div class="flex flex-col">
                                    <span class="text-[10px] uppercase tracking-wider text-gray-400 font-bold leading-none">Método Verificado</span>
                                    <span class="text-xs font-semibold text-gray-700 mt-0.5">PayPal — {{ auth()->user()->email }}</span>
                                </div>
                                <span @class(['w-2 h-2 rounded-full ml-1.5', 'bg-zertix-primary' => $status === 'active', 'bg-state-error' => $status !== 'active'])></span>
                            </div>
                        @else
                            <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-100">
                                <span class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-gray-400 flex-shrink-0">
                                    <x-heroicon-s-credit-card class="w-4 h-4" />
                                </span>
                                <span class="text-xs font-medium text-gray-500">Sin método de pago vinculado — se factura recién al activar</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col lg:items-end gap-5 border-t lg:border-t-0 lg:border-l border-gray-100 pt-6 lg:pt-0 lg:pl-8 min-w-[280px]">
                    <div class="flex flex-col lg:text-right">
                        <span class="text-xs uppercase tracking-widest text-gray-400 font-semibold">
                            {{ $status === 'trialing' ? 'Tarifa al finalizar prueba' : 'Tarifa Base Actual' }}
                        </span>
                        <div class="flex items-baseline lg:justify-end gap-1 mt-1">
                            <span @class(['text-3xl lg:text-4xl font-bold tracking-tight', 'text-state-error' => $status === 'past_due', 'text-zertix-secondary' => $status !== 'past_due'])>
                                USD${{ number_format((float) $this->currentPlan?->price, 0) }}
                            </span>
                            <span class="text-sm font-semibold text-gray-400">/ mes</span>
                        </div>
                    </div>

                    <x-ui.button
                        :variant="$status === 'past_due' ? 'error' : 'primary'"
                        wire:click="showPlans"
                        iconRight="heroicon-s-arrow-right"
                        :hoverEffect="true"
                        :fullWidth="true"
                        class="lg:w-auto"
                    >
                        {{ $ctaLabel }}
                    </x-ui.button>
                    <span class="text-[11px] text-gray-400 text-center lg:text-right">Cambiá o cancelá cuando quieras, sin penalidades</span>

                    {{-- REQ-4.7.1 — solo si hay algo real que cancelar (acuerdo de
                         PayPal activo) y no hay ya una cancelación en curso. --}}
                    @if ($this->isChangeOfPlan && ! $this->isCancellationScheduled)
                        <x-ui.button
                            type="button"
                            variant="error"
                            appearance="ghost"
                            size="sm"
                            wire:click="openCancelModal"
                            iconLeft="heroicon-s-x-circle"
                            class="mt-1"
                        >
                            Cancelar suscripción
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats reales — nada inventado: fecha/monto/días vienen de Subscription, factura de SubscriptionInvoice --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
            <div class="flex flex-col p-5 rounded-xl bg-white border border-gray-100 shadow-sm hover:border-zertix-secondary/30 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">
                        {{ $status === 'trialing' ? 'Fin del Período de Prueba' : 'Próxima Facturación' }}
                    </span>
                    <span class="w-9 h-9 rounded-lg bg-zertix-secondary/5 flex items-center justify-center text-zertix-secondary">
                        <x-heroicon-s-calendar-days class="w-4 h-4" />
                    </span>
                </div>
                <span class="text-xl font-bold text-zertix-secondary tracking-tight">
                    {{ $this->periodEndsAt?->translatedFormat('d \d\e F, Y') ?? '—' }}
                </span>
                <span class="text-[11px] text-gray-400 mt-1.5 font-medium flex items-center gap-1">
                    <x-heroicon-s-arrow-path class="w-3 h-3 text-gray-300" />
                    {{ $status === 'active' ? 'Renovación automática vía PayPal' : ($status === 'trialing' ? 'Sin renovación automática aún' : 'Vencido — sin renovación en curso') }}
                </span>
            </div>

            <div class="flex flex-col p-5 rounded-xl bg-white border border-gray-100 shadow-sm hover:border-zertix-primary/30 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Monto Mensual</span>
                    <span class="w-9 h-9 rounded-lg bg-zertix-primary/10 flex items-center justify-center text-zertix-primary">
                        <x-heroicon-s-banknotes class="w-4 h-4" />
                    </span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold text-zertix-secondary tracking-tight">USD${{ number_format((float) $this->currentPlan?->price, 2) }}</span>
                </div>
                <span class="text-[11px] text-gray-400 mt-1.5 font-medium flex items-center gap-1">
                    <x-heroicon-s-check-circle class="w-3 h-3 text-zertix-primary" />
                    Sin cargos ocultos
                </span>
            </div>

            <div class="flex flex-col p-5 rounded-xl bg-white border border-gray-100 shadow-sm hover:border-zertix-secondary/30 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Días Restantes</span>
                    <span @class(['w-9 h-9 rounded-lg flex items-center justify-center', 'bg-state-error/10 text-state-error' => $status === 'past_due', 'bg-zertix-secondary/5 text-zertix-secondary' => $status !== 'past_due'])>
                        <x-heroicon-s-clock class="w-4 h-4" />
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span @class(['text-xl font-bold tracking-tight', 'text-state-error' => $status === 'past_due', 'text-zertix-secondary' => $status !== 'past_due'])>{{ $this->daysRemaining }}</span>
                    <span class="text-xs text-gray-400 font-medium">de {{ $this->totalPeriodDays }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-3.5 overflow-hidden">
                    <div @class(['h-full rounded-full', 'bg-state-error' => $status === 'past_due', 'bg-zertix-primary' => $status !== 'past_due'])
                         style="width: {{ $this->totalPeriodDays > 0 ? min(100, round($this->daysRemaining / $this->totalPeriodDays * 100)) : 0 }}%"></div>
                </div>
            </div>

            <div class="flex flex-col p-5 rounded-xl bg-white border border-gray-100 shadow-sm hover:border-zertix-secondary/30 hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Última Factura</span>
                    <span class="w-9 h-9 rounded-lg bg-zertix-secondary/5 flex items-center justify-center text-zertix-secondary">
                        <x-heroicon-s-document-text class="w-4 h-4" />
                    </span>
                </div>
                @if ($this->latestInvoice)
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xl font-bold text-zertix-secondary tracking-tight font-mono">USD${{ number_format((float) $this->latestInvoice->amount, 2) }}</span>
                        <x-ui.badge :variant="$this->latestInvoice->status === 'paid' ? 'success' : 'warning'" size="sm" :dot="false">
                            {{ $this->latestInvoice->status === 'paid' ? 'Pagada' : 'Pendiente' }}
                        </x-ui.badge>
                    </div>
                    <span class="text-[11px] text-gray-400 mt-1.5 font-medium">{{ $this->latestInvoice->paid_at?->format('d/m/Y') ?? $this->latestInvoice->created_at->format('d/m/Y') }}</span>
                @else
                    <span class="text-lg font-bold text-gray-300 tracking-tight">—</span>
                    <span class="text-[11px] text-gray-400 mt-1.5">Se genera con el primer cobro real</span>
                @endif
            </div>
        </div>
    @endif

    {{-- ============================================================
         PLANES — mismo grid/copy que el paso Plan del Wizard, para no
         tener dos criterios distintos de qué mostrar por plan.
    ============================================================ --}}
    @if ($view === 'plans')
        <div class="flex flex-col items-center gap-2 text-center">
            <h2 class="text-2xl font-bold text-zertix-secondary">Elegí tu plan</h2>
            <p class="text-sm text-gray-500 max-w-md">
                {{ $this->currentSubscription ? 'Cambiá cuando quieras, sin penalidades.' : 'Seleccioná el plan con el que querés continuar.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-{{ min($planCount, 3) }} gap-6 items-stretch">
            @foreach ($plans as $index => $plan)
                @php
                    $isHighlighted = $index === $highlightIndex;
                    $isCurrent = $this->currentPlan?->id === $plan->id;
                @endphp
                <div @class([
                    'relative bg-white rounded-2xl p-7 lg:p-8 flex flex-col justify-between gap-4 border transition-all',
                    'border-2 border-zertix-primary shadow-lg' => $isHighlighted && ! $isCurrent,
                    'border-gray-200 hover:border-gray-300' => ! $isHighlighted || $isCurrent,
                ])>
                    @if ($isHighlighted && ! $isCurrent)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-zertix-primary text-white text-[10px] font-bold px-3.5 py-1.5 rounded-full uppercase tracking-wide shadow-md">
                            Más popular
                        </span>
                    @endif

                    <div>
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span @class(['text-xs uppercase tracking-wide font-bold', 'text-zertix-primary' => $isHighlighted, 'text-gray-400' => ! $isHighlighted])>
                                    {{ $plan->name }}
                                </span>
                                @if ($isCurrent)
                                    <x-ui.badge variant="success" size="sm" :dot="false">Activa</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-2">
                                <span class="text-[32px] leading-none font-black text-zertix-secondary">USD${{ number_format((float) $plan->price, 0) }}</span>
                                <span class="text-sm text-gray-400">/mes</span>
                            </p>
                        </div>

                        <p class="flex items-center gap-1.5 text-xs font-semibold text-gray-500 mt-3">
                            <x-heroicon-s-user-group class="w-4 h-4 flex-shrink-0" />
                            {{ $usersLine($plan) }}
                        </p>

                        <div class="h-px w-full bg-gray-100 my-5"></div>

                        <ul class="space-y-3">
                            @if ($index > 0)
                                <li class="flex items-start gap-2 text-sm font-bold text-zertix-secondary">
                                    <x-heroicon-s-plus-circle class="w-4 h-4 flex-shrink-0 mt-0.5 text-zertix-primary" />
                                    Todo lo de {{ $plans[$index - 1]->name }}, más:
                                </li>
                            @endif
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="flex items-center gap-2 text-sm text-gray-600">
                                    <x-heroicon-s-check-circle class="w-4 h-4 flex-shrink-0 text-zertix-primary" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @php
                        // Un plan "actual" solo es de verdad no-accionable si la
                        // suscripción sigue viva (active/trialing) — no hay nada
                        // que renovar todavía. Si ya venció (past_due) o se
                        // canceló, es exactamente el mismo botón que renovar: no
                        // hay auto-renovación real de la que depender (PayPal no
                        // avisa de forma confiable), así que pagar de nuevo AHORA
                        // es el único camino, mismo plan o no.
                        $isAlive = in_array($this->currentSubscription?->status, ['active', 'trialing'], true);
                    @endphp
                    @if ($isCurrent && $isAlive)
                        <x-ui.button variant="secondary" appearance="outline" :fullWidth="true" disabled>
                            Plan actual
                        </x-ui.button>
                    @elseif ($isCurrent)
                        <x-ui.button variant="primary" :fullWidth="true" wire:click="selectPlan({{ $plan->id }})">
                            Renovar este plan
                        </x-ui.button>
                    @else
                        <x-ui.button variant="primary" :fullWidth="true" wire:click="selectPlan({{ $plan->id }})">
                            {{ $this->currentSubscription ? 'Cambiar a este plan' : 'Elegir este plan' }}
                        </x-ui.button>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="flex items-center justify-center gap-2 text-sm text-gray-400 py-2">
            <x-heroicon-s-lock-closed class="w-4 h-4" />
            El pago se procesa de forma segura por PayPal. Nunca almacenamos los datos de tu tarjeta.
        </p>
    @endif

    {{-- ============================================================
         CHECKOUT — desglose real (Plan::grossPrice(), REQ-4.5): el
         cliente paga lo suficiente para que a ZertixPOS le llegue el
         precio de lista completo después de la comisión de PayPal. Sin
         ciclo anual/cupones/asientos extra — ninguno existe en el
         sistema real, no se inventan acá solo porque el mockup los tenía.
    ============================================================ --}}
    @if ($view === 'checkout' && $this->selectedPlan)
        @php
            $plan = $this->selectedPlan;
            $fee = round($plan->grossPrice() - (float) $plan->price, 2);
            $isChangeOfPlan = $this->isChangeOfPlan;
            $isDowngrade = $this->isDowngradeSelection;
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="relative overflow-hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8">
                    <div class="absolute top-0 right-0 w-48 h-48 bg-zertix-primary/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-zertix-primary/10 text-zertix-primary mb-4">
                            <span class="w-1.5 h-1.5 rounded-full bg-zertix-primary"></span>
                            Plan Seleccionado
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-bold text-zertix-secondary">{{ $plan->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1 max-w-md">
                            {{ $usersLine($plan) }}.
                        </p>

                        <div class="h-px w-full bg-gray-100 my-6"></div>

                        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 mb-4 flex items-center gap-2">
                            <x-heroicon-s-check-circle class="w-4 h-4 text-zertix-primary" />
                            Funcionalidades incluidas
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($plan->features ?? [] as $feature)
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                                    <span class="w-8 h-8 rounded-lg bg-zertix-primary/10 flex items-center justify-center text-zertix-primary flex-shrink-0">
                                        <x-heroicon-s-check-circle class="w-4 h-4" />
                                    </span>
                                    <span class="text-sm font-semibold text-gray-700">{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-xl flex items-center gap-3">
                    <x-heroicon-s-shield-check class="w-6 h-6 text-zertix-primary flex-shrink-0" />
                    <div>
                        <span class="text-xs font-bold text-zertix-secondary block">Sin ataduras</span>
                        <span class="text-[11px] text-gray-500">Sin contratos forzosos. Cambiá o cancelá tu plan cuando quieras desde esta misma pantalla.</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 flex flex-col gap-6 lg:sticky lg:top-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-7">
                    <h2 class="text-base font-bold text-zertix-secondary mb-5 flex items-center justify-between gap-2 bg-gray-50 -mx-6 sm:-mx-7 -mt-6 sm:-mt-7 px-6 sm:px-7 py-4 rounded-t-xl">
                        <span class="flex items-center gap-2">
                            <x-heroicon-s-receipt-percent class="w-5 h-5 text-zertix-primary" />
                            Resumen de Facturación
                        </span>
                        <span class="text-xs font-normal text-gray-400">Moneda: USD</span>
                    </h2>

                    @if ($isChangeOfPlan)
                        {{-- REQ-4.7.1 — sin desglose de cobro: PayPal no prorratea
                             (confirmado contra el sandbox real, no hay endpoint que
                             lo haga) y el precio nuevo aplica recién en la próxima
                             renovación, nunca hoy. Mostrar "Total a Pagar" acá sería
                             directamente falso — se le dice al usuario lo que sí es
                             cierto: no se le cobra nada en este momento. --}}
                        <div class="rounded-xl bg-zertix-primary/5 border border-zertix-primary/20 p-4 mb-6 flex items-start gap-3">
                            <x-heroicon-s-information-circle class="w-5 h-5 text-zertix-primary flex-shrink-0 mt-0.5" />
                            <div class="text-sm text-gray-700 leading-relaxed">
                                @if ($isDowngrade)
                                    <strong class="text-zertix-secondary">No se te cobra nada hoy.</strong> sigues con todo lo de {{ $this->currentPlan?->name }} hasta el {{ $this->periodEndsAt?->translatedFormat('d \d\e F, Y') }} — desde esa fecha pasás a {{ $plan->name }} (USD${{ number_format((float) $plan->price, 0) }}/mes).
                                @else
                                    <strong class="text-zertix-secondary">No se te cobra nada hoy.</strong> Las funcionalidades de {{ $plan->name }} quedan disponibles apenas confirmes en PayPal — el cobro de USD${{ number_format((float) $plan->price, 0) }}/mes arranca en tu próxima renovación, el {{ $this->periodEndsAt?->translatedFormat('d \d\e F, Y') }}.
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="space-y-3 text-sm pb-5">
                            <div class="flex justify-between items-center text-gray-500">
                                <span>Subtotal {{ $plan->name }}</span>
                                <span class="font-semibold text-gray-800 font-mono">${{ number_format((float) $plan->price, 2) }} USD</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-500">
                                <span class="flex items-center gap-1">
                                    Comisión de procesamiento (PayPal)
                                    <x-heroicon-s-information-circle class="w-3.5 h-3.5 text-gray-400" title="Tarifa de la pasarela internacional — se suma para que a ZertixPOS le llegue el precio de lista completo" />
                                </span>
                                <span class="font-semibold text-gray-800 font-mono">+${{ number_format($fee, 2) }} USD</span>
                            </div>
                        </div>

                        <div class="pt-4 bg-gray-50 -mx-6 sm:-mx-7 px-6 sm:px-7 py-4 mb-6 rounded-lg">
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs uppercase font-bold tracking-wider text-gray-400">Total a Pagar</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-sm font-extrabold text-gray-800">USD$</span>
                                    <span class="text-3xl font-black text-zertix-secondary tracking-tight">{{ number_format($plan->grossPrice(), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Método de pago — real, no decorativo: PaymentGatewayContract solo
                         tiene una implementación hoy (PayPalGateway), así que no hay
                         nada más que "elegir" acá — se muestra igual porque confirma
                         al usuario a dónde va a ir antes de tocar el botón. --}}
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Método de Pago</label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zertix-primary bg-zertix-primary/10 px-2 py-0.5 rounded-full">Pasarela Exclusiva</span>
                        </div>
                        <div class="p-4 rounded-xl bg-white border border-zertix-secondary/20 shadow-sm flex flex-col gap-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-4 h-4 rounded-full border-2 border-zertix-primary flex items-center justify-center flex-shrink-0">
                                        <span class="w-2 h-2 rounded-full bg-zertix-primary"></span>
                                    </span>
                                    <span class="flex items-center gap-2">
                                        <x-heroicon-s-credit-card class="w-5 h-5 text-zertix-secondary" />
                                        <span class="text-xs font-semibold text-gray-800">PayPal — Checkout Oficial</span>
                                    </span>
                                </div>
                                <x-ui.badge variant="info" size="sm" :dot="false">Verificado</x-ui.badge>
                            </div>
                            <p class="text-xs text-gray-500 pl-7 leading-relaxed">
                                Redirección segura a PayPal para autorizar la suscripción recurrente con saldo PayPal o tarjeta vinculada.
                            </p>
                        </div>
                    </div>

                    <x-ui.button
                        type="button"
                        variant="primary"
                        :fullWidth="true"
                        size="lg"
                        wire:click="subscribe"
                        wire:loading.attr="disabled"
                        wire:target="subscribe"
                        iconLeft="heroicon-s-credit-card"
                    >
                        {{ $isChangeOfPlan ? 'Confirmar cambio con PayPal' : 'Pagar $' . number_format($plan->grossPrice(), 2) . ' USD con PayPal' }}
                    </x-ui.button>

                    @if ($isChangeOfPlan)
                        <p class="mt-3 text-[11px] text-gray-400 text-center leading-relaxed">
                            PayPal te va a pedir que confirmes el cambio — es el mismo tipo de aprobación que hiciste al suscribirte, no un cobro.
                        </p>
                    @endif

                    <div class="mt-5 pt-4 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-[11px] text-gray-400 font-medium">
                        <span class="flex items-center gap-1"><x-heroicon-s-lock-closed class="w-3.5 h-3.5 text-zertix-primary" /> Cifrado SSL 256-bit</span>
                        <span>•</span>
                        <span class="flex items-center gap-1"><x-heroicon-s-arrow-path class="w-3.5 h-3.5 text-zertix-primary" /> Cancelá cuando quieras</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================
         MODAL — cancelar suscripción (REQ-4.7.1). Mismo espíritu que el
         mockup Stitch "Resumen de Suscripción: Cancelar Plan" (caja de
         consecuencias + motivo opcional), con los componentes reales del
         proyecto (<x-modal>, x-ui.button) en vez del modal armado a mano en
         JS puro del mockup.
    ============================================================ --}}
    <x-modal name="cancel-subscription" maxWidth="lg">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-state-error/10 rounded-full flex items-center justify-center flex-shrink-0 text-state-error">
                    <x-heroicon-s-exclamation-triangle class="w-7 h-7" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 leading-tight">¿Cancelar tu suscripción a ZertixPOS?</h2>
                    <p class="text-xs text-gray-500 font-medium">{{ $this->currentPlan?->name }} • Facturación Mensual</p>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5 flex items-center gap-1.5">
                    <x-heroicon-s-information-circle class="w-4 h-4 text-zertix-primary" />
                    Qué pasa si cancelás
                </h3>
                <ul class="space-y-2 text-xs text-gray-600">
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-check-circle class="w-4 h-4 text-zertix-primary flex-shrink-0 mt-0.5" />
                        <span>sigues con acceso completo hasta el final de tu ciclo actual: <strong class="text-gray-900">{{ $this->periodEndsAt?->translatedFormat('d \d\e F, Y') }}</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-check-circle class="w-4 h-4 text-zertix-primary flex-shrink-0 mt-0.5" />
                        <span>No se te va a cobrar de nuevo — el cobro recurrente en PayPal se cancela al confirmar.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-lock-closed class="w-4 h-4 text-state-error flex-shrink-0 mt-0.5" />
                        <span>Al terminar el ciclo, el acceso se pausa — tus datos quedan intactos, no se borra nada.</span>
                    </li>
                </ul>
            </div>

            <div class="mt-4">
                <label for="cancelReason" class="block text-xs font-semibold text-gray-700 mb-1.5">¿Por qué cancelás? (opcional)</label>
                <x-ui.forms.textarea
                    name="cancelReason"
                    id="cancelReason"
                    wire:model="cancelReason"
                    placeholder="Nos ayuda a mejorar — no es obligatorio."
                    :rows="2"
                />
            </div>

            <div class="mt-6 flex flex-col sm:flex-row justify-end items-center gap-3">
                <x-ui.button variant="primary" :fullWidth="true" class="sm:w-auto" x-on:click="$dispatch('close')" iconLeft="heroicon-s-check-circle">
                    Mantener mi suscripción
                </x-ui.button>
                <x-ui.button variant="error" appearance="outline" :fullWidth="true" class="sm:w-auto" wire:click="confirmCancel" iconLeft="heroicon-s-x-circle">
                    Confirmar cancelación
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>
