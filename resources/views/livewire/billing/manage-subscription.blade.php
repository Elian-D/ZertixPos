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

    $status = $this->currentSubscription?->status;
    $statusVariant = match ($status) {
        'active' => 'success',
        'trialing' => 'info',
        'pending' => 'warning',
        'past_due' => 'error',
        'paused' => 'warning',
        'cancelled' => 'slate',
        default => 'slate',
    };
    $statusLabel = match ($status) {
        'active' => 'Activa',
        'trialing' => 'En trial',
        'pending' => 'Esperando confirmación de PayPal',
        'past_due' => 'Vencida',
        'paused' => 'Pausada',
        'cancelled' => 'Cancelada',
        default => 'Sin suscripción',
    };
@endphp

<div class="min-h-screen bg-slate-50 flex flex-col">
    <header class="max-w-[1200px] w-full mx-auto px-6 py-6 flex items-center justify-between">
        <x-ui.application-logo type="full" class="h-8 w-auto" />

        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-zertix-secondary hover:underline flex items-center gap-1">
            <x-heroicon-s-arrow-left class="w-4 h-4" /> Volver al panel
        </a>
    </header>

    <main class="max-w-[1200px] w-full mx-auto px-6 py-8 flex flex-col gap-10 flex-1">
        {{-- Estado actual --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl font-bold text-zertix-secondary">
                        {{ $this->currentPlan?->name ?? 'Sin plan asignado' }}
                    </h1>
                    <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                </div>

                @if ($this->currentSubscription?->current_period_ends_at)
                    <p class="text-sm text-slate-500">
                        @if (in_array($status, ['active', 'trialing']))
                            Tu próximo cobro es el {{ $this->currentSubscription->current_period_ends_at->format('d/m/Y') }}
                        @else
                            Venció el {{ $this->currentSubscription->current_period_ends_at->format('d/m/Y') }}
                        @endif
                    </p>
                @endif
            </div>
        </div>

        {{-- Planes --}}
        <div class="flex flex-col items-center gap-8">
            <div class="flex flex-col items-center gap-2">
                <h2 class="text-2xl font-bold text-zertix-secondary text-center">Planes y Suscripción</h2>
                <p class="text-sm text-slate-500 text-center max-w-md">
                    Elegí el plan ideal para tu negocio. Cambiá cuando quieras.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-{{ min($planCount, 3) }} gap-6 w-full">
                @foreach ($plans as $index => $plan)
                    @php
                        $isHighlighted = $index === $highlightIndex;
                        $isCurrent = $this->currentPlan?->id === $plan->id;
                    @endphp
                    <div @class([
                        'relative bg-white rounded-2xl p-6 flex flex-col gap-4 border',
                        'border-2 border-zertix-primary shadow-md' => $isHighlighted,
                        'border-slate-200' => ! $isHighlighted,
                    ])>
                        @if ($isHighlighted)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-zertix-primary text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wide shadow">
                                Más popular
                            </span>
                        @endif

                        <div class="flex flex-col gap-1">
                            <span @class(['text-xs uppercase tracking-wide font-bold', 'text-zertix-primary' => $isHighlighted, 'text-slate-400' => ! $isHighlighted])>
                                {{ $plan->name }}
                            </span>
                            <p class="mt-1">
                                <span class="text-3xl font-black text-zertix-secondary">USD${{ number_format((float) $plan->price, 0) }}</span>
                                <span class="text-sm text-slate-400">/mes</span>
                            </p>
                        </div>

                        {{-- Límite de usuarios del plan (REQ-05.6) — explícito, no una feature más --}}
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                            <x-heroicon-s-user-group class="w-4 h-4 flex-shrink-0" />
                            {{ $usersLine($plan) }}
                        </p>

                        <div class="h-px w-full bg-slate-100"></div>

                        {{-- Copy público real (zertixpos.com) — nunca claves internas de módulo --}}
                        <ul class="space-y-2.5 flex-1">
                            @if ($index > 0)
                                <li class="flex items-start gap-2 text-sm font-bold text-zertix-secondary">
                                    <x-heroicon-s-plus-circle class="w-4 h-4 flex-shrink-0 mt-0.5 text-zertix-primary" />
                                    Todo lo de {{ $plans[$index - 1]->name }}, más:
                                </li>
                            @endif
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="flex items-start gap-2 text-sm text-slate-600">
                                    <x-heroicon-s-check-circle class="w-4 h-4 flex-shrink-0 mt-0.5 text-zertix-primary" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        @if ($isCurrent)
                            <x-ui.button variant="secondary" appearance="outline" :fullWidth="true" disabled>
                                Plan actual
                            </x-ui.button>
                        @else
                            <x-ui.button
                                variant="primary"
                                :fullWidth="true"
                                wire:click="subscribe({{ $plan->id }})"
                                wire:loading.attr="disabled"
                                wire:target="subscribe({{ $plan->id }})"
                            >
                                Cambiar a este plan
                            </x-ui.button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if ($errorMessage)
            <div class="max-w-lg mx-auto w-full bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 text-center">
                {{ $errorMessage }}
            </div>
        @endif

        <p class="flex items-center justify-center gap-2 text-sm text-slate-400 py-2">
            <x-heroicon-s-lock-closed class="w-4 h-4" />
            Pago procesado de forma segura por PayPal. Nunca almacenamos los datos de tu tarjeta.
        </p>
    </main>

    <footer class="max-w-[1200px] w-full mx-auto px-6 py-8 border-t border-slate-200 text-center text-sm text-slate-400">
        © {{ now()->year }} ZertixPOS. Todos los derechos reservados.
    </footer>
</div>
