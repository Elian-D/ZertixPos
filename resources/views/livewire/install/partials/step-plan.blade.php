@php
    $planCount = $this->plans->count();
    $usersLine = function ($plan) {
        return is_null($plan->users_limit)
            ? 'Usuarios ilimitados — sumá a todo tu equipo'
            : ($plan->users_limit === 1
                ? 'Un usuario con acceso al sistema'
                : "Hasta {$plan->users_limit} usuarios con acceso al sistema");
    };
    // El plan del medio (o el más cercano) es el recomendado — mismo criterio
    // visual del mockup (PyME entre Emprendedor y Pro). Es independiente de
    // cuál esté seleccionado: la insignia "MÁS POPULAR" no se mueve al elegir
    // otro plan, es posicionamiento de marketing, no estado de selección.
    $highlightIndex = intdiv($planCount, 2);
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-10">
    <h1 class="text-2xl font-bold text-gray-900 text-center">Seleccione su Plan</h1>
    <p class="mt-2 text-sm text-gray-500 text-center max-w-md mx-auto">
        Elija la solución de ZertixPOS que mejor se adapte al crecimiento y volumen de su empresa.
    </p>

    <div x-data="{
            active: {{ $highlightIndex }},
            scrollTo(i) {
                this.active = i;
                const el = this.$refs['card-' + i];
                if (el) el.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            },
            onScroll(e) {
                const cards = [...e.target.children];
                const center = e.target.scrollLeft + e.target.clientWidth / 2;
                let closest = 0, min = Infinity;
                cards.forEach((c, i) => {
                    const d = Math.abs((c.offsetLeft + c.offsetWidth / 2) - center);
                    if (d < min) { min = d; closest = i; }
                });
                this.active = closest;
            }
         }"
         class="mt-8">

        {{-- pt-5: el badge "MÁS POPULAR" sobresale -top-3 por encima de la tarjeta —
             overflow-x-auto sin esto clipea igual el eje Y en mobile (comportamiento
             del CSS: un solo eje distinto de 'visible' fuerza 'auto' en el otro), así
             que sin este padding el badge se cortaba en el carrusel horizontal. --}}
        <div @scroll.debounce.100ms="onScroll"
             class="flex md:grid md:grid-cols-{{ min($planCount, 3) }} gap-6 overflow-x-auto snap-x snap-mandatory pt-5 pb-4 md:pt-0 md:pb-0 md:overflow-visible -mx-2 px-2">
            @foreach ($this->plans as $index => $plan)
                @php
                    $isHighlighted = $index === $highlightIndex;
                    $isSelected = $planId === $plan->id;
                @endphp
                <div x-ref="card-{{ $index }}" wire:click="$set('planId', {{ $plan->id }})" @click="scrollTo({{ $index }})"
                     @class([
                        'relative flex-shrink-0 w-[85%] sm:w-[70%] md:w-auto snap-center rounded-2xl p-6 flex flex-col cursor-pointer transition-all',
                        'bg-zertix-primary text-white shadow-xl scale-[1.02] border-2 border-zertix-primary ring-2 ring-zertix-primary ring-offset-2' => $isSelected,
                        'bg-white text-gray-900 border-2 border-gray-200 hover:border-zertix-primary hover:shadow-lg' => ! $isSelected,
                     ])>
                    @if ($isHighlighted)
                        <span @class([
                            'absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-bold px-3 py-1 rounded-full shadow',
                            'bg-white text-zertix-primary' => $isSelected,
                            'bg-zertix-primary text-white' => ! $isSelected,
                        ])>
                            MÁS POPULAR
                        </span>
                    @endif

                    <div class="flex items-center justify-between mb-4">
                        <span @class([
                            'w-10 h-10 rounded-xl flex items-center justify-center',
                            'bg-white/20' => $isSelected,
                            'bg-gray-100' => ! $isSelected,
                        ])>
                            <x-heroicon-s-building-storefront @class(['w-5 h-5', 'text-white' => $isSelected, 'text-gray-500' => ! $isSelected])/>
                        </span>

                        {{-- Círculo → check cuando está seleccionado: única señal de estado, sin ambigüedad --}}
                        @if ($isSelected)
                            <x-heroicon-s-check-circle class="w-6 h-6 text-white flex-shrink-0" />
                        @else
                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 flex-shrink-0"></div>
                        @endif
                    </div>

                    <h3 class="text-lg font-bold">{{ $plan->name }}</h3>

                    {{-- Precio: el elemento tipográficamente más grande de la tarjeta --}}
                    <p class="mt-1 mb-1">
                        @if ($plan->price !== null)
                            <span class="text-3xl font-black">USD${{ number_format($plan->price, 0) }}</span>
                            <span @class(['text-sm font-normal', 'text-white/70' => $isSelected, 'text-gray-400' => ! $isSelected])>/mes</span>
                        @else
                            <span class="text-3xl font-black">A cotizar</span>
                        @endif
                    </p>

                    {{-- Límite de usuarios del plan (REQ-05.6) — explícito, no una feature más --}}
                    <p @class(['flex items-center gap-1.5 text-xs font-semibold mb-4', 'text-white/90' => $isSelected, 'text-gray-500' => ! $isSelected])>
                        <x-heroicon-s-user-group class="w-4 h-4 flex-shrink-0" />
                        {{ $usersLine($plan) }}
                    </p>

                    {{-- Copy público real (zertixpos.com) — nunca claves internas de módulo --}}
                    <ul class="space-y-2.5 flex-1">
                        @if ($index > 0)
                            <li class="flex items-start gap-2 text-sm font-bold">
                                <x-heroicon-s-plus-circle @class(['w-4 h-4 flex-shrink-0 mt-0.5', 'text-white' => $isSelected, 'text-zertix-primary' => ! $isSelected])/>
                                Todo lo de {{ $this->plans[$index - 1]->name }}, más:
                            </li>
                        @endif
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex items-start gap-2 text-sm font-medium">
                                <x-heroicon-s-check-circle @class(['w-4 h-4 flex-shrink-0 mt-0.5', 'text-white' => $isSelected, 'text-zertix-primary' => ! $isSelected])/>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <button type="button"
                        @class([
                            'mt-6 w-full text-center font-bold text-xs uppercase tracking-wide py-3 rounded-xl transition-colors',
                            'bg-white text-zertix-primary hover:bg-gray-50' => $isSelected,
                            'border border-zertix-secondary text-zertix-secondary hover:bg-zertix-secondary hover:text-white' => ! $isSelected,
                        ])>
                        {{ $isSelected ? '✓ Seleccionado' : 'Seleccionar' }}
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Dots: solo mobile --}}
        <div class="flex md:hidden justify-center gap-1.5 mt-2">
            @foreach ($this->plans as $index => $plan)
                <button type="button" @click="scrollTo({{ $index }})"
                    :class="active === {{ $index }} ? 'bg-zertix-primary w-4' : 'bg-gray-300 w-1.5'"
                    class="h-1.5 rounded-full transition-all"></button>
            @endforeach
        </div>
    </div>

    @error('planId') <p class="mt-4 text-xs text-red-600 text-center">{{ $message }}</p> @enderror

    <div class="mt-8 pt-6 border-t border-gray-100 grid grid-cols-2 gap-3 sm:flex sm:items-center sm:justify-between">
        <button type="button" wire:click="prevStep"
            class="border border-gray-200 sm:border-0 rounded-xl sm:rounded-none py-3 sm:py-0 text-sm font-semibold text-gray-500 hover:text-gray-700 flex items-center justify-center sm:justify-start gap-1">
            <x-heroicon-s-arrow-left class="w-4 h-4" /> Atrás
        </button>
        {{-- nextStep() acá valida y solo AVANZA a la revisión — no crea nada
             todavía. Lo único que persiste es "Comenzar ahora" en esa pantalla. --}}
        <button type="button" wire:click="nextStep"
            class="bg-zertix-primary hover:bg-zertix-primary-dark text-white font-bold py-3.5 px-4 sm:px-8 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm sm:text-base">
            Revisar Instalación
            <x-heroicon-s-arrow-right class="w-4 h-4 flex-shrink-0" />
        </button>
    </div>
</div>
