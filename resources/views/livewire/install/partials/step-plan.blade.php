@php
    $usersLine = function ($plan) {
        return is_null($plan->users_limit)
            ? 'Usuarios ilimitados — sumá a todo tu equipo'
            : ($plan->users_limit === 1
                ? 'Un usuario con acceso al sistema'
                : "Hasta {$plan->users_limit} usuarios con acceso al sistema");
    };
    // REQ-4.4/4.5 — el plan recomendado según el tipo de negocio elegido en
    // el paso Tipo de Negocio (getRecommendedPlanProperty()). Independiente
    // de cuál esté seleccionado: la insignia no se mueve al elegir otro
    // plan, es una recomendación, no estado de selección.
    $recommendedPlan = $this->recommendedPlan;
@endphp

{{-- Título/subtítulo viven en install-wizard.blade.php (fuera de la card) —
     acá el subtítulo dinámico ("Basado en tu modelo de negocio...") también
     se arma ahí. Grid simple (rediseño 2026-09-05, mockup "Instalación Paso
     4"): 1 columna hasta 1024px, 3 columnas desde ahí — reemplaza el
     carrusel horizontal con scroll/dots que había antes, que en mobile
     escondía 2 de los 3 planes hasta hacer swipe. --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 lg:p-10">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch pt-4">
        @foreach ($this->plans as $index => $plan)
            @php
                $isRecommended = $recommendedPlan?->id === $plan->id;
                $isSelected = $planId === $plan->id;
            @endphp
            <div wire:click="$set('planId', {{ $plan->id }})"
                 @class([
                    'relative flex flex-col justify-between rounded-xl p-6 cursor-pointer transition-all duration-150',
                    'bg-white shadow-lg ring-2 ring-zertix-primary' => $isSelected,
                    'bg-gray-50 hover:bg-gray-100 hover:shadow-md' => ! $isSelected,
                 ])>
                @if ($isRecommended)
                    {{-- Corto a propósito (2026-09-05, corrección tras ver el
                         badge desbordar la tarjeta con tipos de negocio de
                         nombre largo) — el "para qué tipo de negocio" ya está
                         en el subtítulo de arriba (install-wizard.blade.php:
                         "Basado en tu modelo de negocio (X)..."), repetirlo acá
                         era redundante y era justo lo que rompía el ancho. --}}
                    <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap bg-zertix-primary text-white font-bold text-[11px] tracking-wider uppercase px-3.5 py-1 rounded-full shadow-sm flex items-center gap-1.5">
                        <x-heroicon-s-sparkles class="w-3 h-3" />
                        {{ $this->businessTypeLabel ? 'Recomendado para ti' : 'Más Popular' }}
                    </span>
                @endif

                <div>
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <span @class(['text-xs font-bold uppercase tracking-wider', 'text-zertix-primary' => $isSelected, 'text-gray-500' => ! $isSelected])>
                            {{ [0 => 'Básico', 1 => 'Crecimiento'][$index] ?? 'Operación Total' }}
                        </span>
                        @if ($isSelected)
                            <x-heroicon-s-check-circle class="w-5 h-5 text-zertix-primary" />
                        @else
                            <x-heroicon-s-building-storefront class="w-5 h-5 text-gray-400" />
                        @endif
                    </div>

                    <h3 class="text-xl font-bold text-zertix-secondary mb-1">{{ $plan->name }}</h3>
                    <p class="text-xs text-gray-500 mb-6 min-h-[32px]">{{ $usersLine($plan) }}</p>

                    <div class="flex items-baseline gap-1 mb-6 pb-6 border-b border-gray-200">
                        @if ($plan->price !== null)
                            <span class="text-3xl sm:text-4xl font-extrabold text-zertix-secondary tracking-tight">USD${{ number_format($plan->price, 0) }}</span>
                            <span class="text-xs text-gray-500 font-medium">/ mes</span>
                        @else
                            <span class="text-3xl font-extrabold text-zertix-secondary">A cotizar</span>
                        @endif
                    </div>

                    <ul class="space-y-3 mb-2 text-xs text-gray-700">
                        @if ($index > 0)
                            <li class="flex items-start gap-2 font-bold text-zertix-secondary">
                                <x-heroicon-s-plus-circle class="w-4 h-4 flex-shrink-0 mt-0.5 text-zertix-primary" />
                                Todo lo de {{ $this->plans[$index - 1]->name }}, más:
                            </li>
                        @endif
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex items-start gap-2.5">
                                <x-heroicon-s-check-circle class="w-4 h-4 flex-shrink-0 mt-0.5 text-zertix-primary" />
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <x-ui.button
                    type="button"
                    :variant="$isSelected ? 'primary' : 'secondary'"
                    :appearance="$isSelected ? 'solid' : 'outline'"
                    :fullWidth="true"
                    class="mt-6"
                    :iconLeft="$isSelected ? 'heroicon-s-check' : null"
                >
                    {{ $isSelected ? 'Plan Seleccionado' : 'Elegir ' . $plan->name }}
                </x-ui.button>
            </div>
        @endforeach
    </div>

    @error('planId') <p class="mt-4 text-xs text-red-600 text-center">{{ $message }}</p> @enderror

    {{-- Aviso, no bloqueo (2026-09-05, análisis vs. el "diagnóstico" de Alegra:
         se descartó ocultar los otros planes — ver docs/features/v1.3.0.md
         §4.5). Banner a nivel de página, no metido dentro de cada tarjeta
         (corrección sobre el primer intento: quedaba incómodo ahí adentro) —
         solo aparece cuando el plan que el usuario YA ELIGIÓ no cubre lo
         sugerido por su tipo de negocio, no antes de elegir ninguno. El
         usuario sigue pudiendo quedarse con este plan igual; solo se le dice
         qué se queda sin activar en vez de que pase en silencio
         (missingModulesFor()). --}}
    @if ($planId && $this->selectedPlan && $this->missingModulesFor($this->selectedPlan) !== [])
        <div class="mt-6 rounded-xl bg-amber-50 border border-amber-200 p-4 flex items-start gap-3">
            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
            <p class="text-xs text-amber-800 leading-relaxed">
                <span class="font-semibold">{{ $this->selectedPlan->name }}</span> no incluye
                {{ implode(', ', $this->missingModulesFor($this->selectedPlan)) }} — funcionalidades que tu tipo de negocio suele necesitar.
                Podés activarlas subiendo de plan más adelante.
            </p>
        </div>
    @endif

    <div class="mt-8 pt-6 border-t border-gray-100 grid grid-cols-2 gap-3 sm:flex sm:items-center sm:justify-between">
        <x-ui.button type="button" variant="secondary" appearance="ghost" wire:click="prevStep" iconLeft="heroicon-s-arrow-left">
            Atrás: Tipo de Negocio
        </x-ui.button>
        {{-- nextStep() acá valida y solo AVANZA a la revisión — no crea nada
             todavía. Lo único que persiste es "Comenzar ahora" en esa pantalla. --}}
        <x-ui.button type="button" variant="primary" wire:click="nextStep" iconRight="heroicon-s-arrow-right">
            Revisar Instalación
        </x-ui.button>
    </div>
</div>
