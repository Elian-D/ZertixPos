@php
    $steps = $this->steps();
    $total = count($steps);
    $doneSteps = collect($steps)->filter(fn ($s) => $s->isComplete())->values();
    $doneCount = $doneSteps->count();
    $current = $this->currentStep();
@endphp

{{-- Livewire exige un único tag raíz siempre presente, aunque no haya nada que
     mostrar (todos los pasos ya completos) — el @if vive DENTRO del div, no
     alrededor, para no dejar el componente sin raíz. --}}
<div>
    @if (! $this->isDone())
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-gray-800">Completa tu configuración</h3>
            <span class="text-xs font-bold text-zertix-primary">{{ $doneCount }}/{{ $total }}</span>
        </div>

        <div class="w-full bg-gray-100 rounded-full h-2 mb-4">
            <div class="bg-zertix-primary h-2 rounded-full transition-all"
                 style="width: {{ $total > 0 ? ($doneCount / $total) * 100 : 0 }}%"></div>
        </div>

        {{-- Pasos ya completados: fila compacta, sin CTA — solo confirman lo
             hecho. Mostrar los 5 pasos a la vez (completados y pendientes por
             igual) no dejaba claro cuál iba primero (reportado probando la
             tarjeta real); acá solo se revela UN paso accionable a la vez. --}}
        @if ($doneCount > 0)
            <ul class="space-y-1.5 mb-4">
                @foreach ($doneSteps as $step)
                    <li class="flex items-center gap-2 text-sm text-gray-400">
                        <x-heroicon-s-check-circle class="w-4 h-4 flex-shrink-0 text-zertix-primary" />
                        <span class="line-through">{{ $step->title() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Único paso accionable: el primero sin completar. Al terminarlo,
             Livewire recalcula currentStep() y este bloque pasa al siguiente
             pendiente solo, sin que el dueño tenga que adivinar el orden. --}}
        @if ($current)
            <div class="flex items-center justify-between gap-3 bg-zertix-primary/5 border border-zertix-primary/20 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-zertix-primary text-white text-xs font-bold flex items-center justify-center">
                        {{ $doneCount + 1 }}
                    </span>
                    <div>
                        <div class="font-bold text-gray-800 text-sm">{{ $current->title() }}</div>
                        <div class="text-xs text-gray-500">{{ $current->description() }}</div>
                    </div>
                </div>

                <a href="{{ route($current->ctaRoute()) }}"
                   class="text-xs font-bold px-4 py-2 rounded-lg bg-zertix-primary text-white hover:bg-zertix-primary-dark shrink-0 whitespace-nowrap">
                    {{ $current->ctaLabel() }}
                </a>
            </div>
        @endif
    </div>
    @endif
</div>
