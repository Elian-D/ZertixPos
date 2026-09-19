{{--
    Envoltorio único e incondicional a propósito — Livewire exige un solo
    elemento raíz por componente y le pega wire:id/wire:snapshot al PRIMER
    tag que encuentra al compilar. Con @if/@else como raíz directa (sin este
    <div> de afuera), ese primer tag terminaba siendo el <div> interno
    ("w-full mx-auto...") en vez del real — Livewire perdía el snapshot del
    componente en cada carga (bug real, reproducido: "Snapshot missing on
    Livewire component", ninguna interacción — wire:model.live, x-model,
    file upload — funcionaba después). La pantalla de progreso (REQ-4.6) es
    pantalla completa de verdad (fixed inset-0 en su propio partial) — no
    encaja dentro del card/step-indicator del resto del wizard, por eso el
    @if elige entre los dos ANTES del wrapper visual normal, no dentro del
    slot de "CONTENIDO DEL PASO" de más abajo.
--}}
<div>
@if ($provisioning)
    @include('livewire.install.partials.step-provisioning')
@else
<div class="min-h-screen bg-gray-50 py-10 px-4 flex flex-col">
    <div class="w-full mx-auto flex-1 flex flex-col items-center" style="max-width: {{ $this->stepMeta['maxWidth'] }}">

        {{-- LOGO real, pequeño arriba de todo — el pill de abajo reemplaza al
             stepper de íconos+etiquetas del primer borrador (rediseño
             2026-09-05, mockups Stitch "Instalación Paso N": ahí no hay
             logo gráfico, solo el pill — se conserva el logo real por
             pedido explícito del usuario, encima en vez de en lugar del pill). --}}
        <x-ui.application-logo class="h-10 w-auto mb-4" />

        {{-- PILL DE PASO — dinámico ("Paso {{ $step+1 }} de 5"), reemplaza el
             stepper de 5 íconos+etiquetas+líneas: ese diseño no escala bien
             (mockup real usa un badge chico, no un stepper de ancho fijo) y
             no se leía bien en mobile con 5 pasos. El punto verde parpadea
             (animate-pulse) para dar sensación de "progreso en vivo", igual
             que el mockup. --}}
        <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white border border-gray-200 shadow-sm mb-4">
            <span class="w-2.5 h-2.5 rounded-full bg-zertix-primary animate-pulse"></span>
            <span class="text-xs font-semibold tracking-wide text-zertix-secondary">ZertixPOS Enterprise</span>
            <span class="text-gray-300">•</span>
            <span class="text-xs font-medium text-gray-500">Paso {{ $step + 1 }} de 5</span>
        </div>

        @php
            // "Siguiente" entra desde la derecha, "Atrás" desde la izquierda
            // (InstallWizard::$stepDirection) — antes ambos usaban la misma
            // animación y "Atrás" se sentía al revés.
            $carouselClass = $stepDirection === 'backward' ? 'animate-carousel-in-reverse' : 'animate-carousel-in';
        @endphp

        {{-- TÍTULO/SUBTÍTULO — fuera de la card (antes vivían dentro de cada
             partial, mezclados con el formulario; el mockup los pone afuera,
             centrados, para que la card sea puramente "contenido", no
             "contenido + encabezado"). El paso Plan arma su propio subtítulo
             dinámico (tipo de negocio + plan recomendado) porque depende de
             datos que STEP_META no tiene.

             `wire:key` propio (antes no tenía — el texto cambiaba en el
             mismo nodo sin re-disparar ninguna animación).

             Rediseño "carrusel" (2026-09-05): se probó `x-transition:enter`/
             `x-transition:leave` acá — no funcionó, ni siquiera la entrada.
             `x-transition` solo se dispara cuando ALPINE mismo coordina la
             inserción/remoción del nodo (`x-show`/`x-if`); acá quien
             agrega/quita el nodo es Livewire (por `wire:key`), sin pasar por
             ese mecanismo, así que Alpine nunca ve el cambio. Se volvió a la
             clase `animate-*` puesta directo en `class`: el navegador
             dispara un `@keyframes` solo por insertar el nodo en el DOM, sin
             depender de Alpine — mismo mecanismo (confirmado funcionando)
             que ya usaba `animate-fade-slide-in` antes de este rediseño,
             solo que ahora la animación es horizontal (`carousel-in`, ver
             tailwind.config.js). Sin salida real: Livewire destruye el nodo
             viejo en el mismo instante que inserta el nuevo, no hay forma de
             animar una salida sin mantener los dos nodos en el DOM a la vez
             (fuera de alcance de este ajuste). --}}
        <div
            class="text-center mb-8 max-w-lg {{ $carouselClass }}"
            wire:key="step-heading-{{ $step }}"
        >
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-zertix-secondary">{{ $this->stepMeta['title'] }}</h1>
            @if ($step === 3)
                <p class="mt-2 text-sm text-gray-500">
                    @if ($this->businessTypeLabel)
                        Basado en tu modelo de negocio (<span class="font-medium text-gray-700">{{ $this->businessTypeLabel }}</span>), te sugerimos el <span class="font-semibold text-zertix-primary">{{ $this->recommendedPlan?->name }}</span>.
                    @else
                        Elegí la solución de ZertixPOS que mejor se adapte al crecimiento y volumen de tu empresa.
                    @endif
                </p>
            @elseif ($this->stepMeta['subtitle'])
                <p class="mt-2 text-sm text-gray-500">{{ $this->stepMeta['subtitle'] }}</p>
            @endif
        </div>

        {{-- CONTENIDO DEL PASO — wire:key distinto por paso: Livewire reemplaza
             el nodo entero en cada cambio de $step (no lo muta), así que la
             animación se re-dispara sola en cada paso por simple inserción en
             el DOM. `$carouselClass` (arriba) en vez de una clase fija —
             rediseño "carrusel", 2026-09-05, ver ese comentario sobre por qué
             NO es `x-transition`. --}}
        <div
            class="w-full {{ $carouselClass }}"
            wire:key="step-panel-{{ $step }}"
        >
            @if ($step === 0)
                @include('livewire.install.partials.step-admin')
            @elseif ($step === 1)
                @include('livewire.install.partials.step-empresa')
            @elseif ($step === 2)
                @include('livewire.install.partials.step-tipo-negocio')
            @elseif ($step === 3)
                @include('livewire.install.partials.step-plan')
            @else
                @include('livewire.install.partials.step-finalizar')
            @endif
        </div>
    </div>
</div>
@endif
</div>
