{{--
    x-ui.alert — aviso en línea (v1.5.0): un estado o una advertencia dentro de una vista
    (documento anulado, supuesto de un conteo, una acción que no se puede deshacer).
    No es un toast: se queda en la página.

    PROPS:
      variant — neutral | info | success | warning | error (default info). `neutral` (gris)
                para avisos informativos que no son un error ni una advertencia: el usuario
                no debe sentir que está haciendo algo mal (ej. "Borrador", recomendaciones).
      icon    — heroicon; default uno por variante. false = sin ícono.
      title   — texto en negrita antes del contenido (opcional).

    SLOT: el mensaje.
--}}
@props([
    'variant' => 'info',
    'icon' => null,
    'title' => null,
])

@php
    $styles = [
        'neutral' => 'border-gray-200 bg-gray-50 text-gray-600',
        'info' => 'border-state-info/20 bg-state-info/5 text-slate-700',
        'success' => 'border-state-success/20 bg-state-success/5 text-slate-700',
        'warning' => 'border-state-warning/30 bg-state-warning/10 text-slate-800',
        'error' => 'border-state-error/20 bg-state-error/5 text-state-error',
    ][$variant] ?? 'border-state-info/20 bg-state-info/5 text-slate-700';

    $iconColor = [
        'neutral' => 'text-gray-400',
        'info' => 'text-state-info',
        'success' => 'text-state-success',
        'warning' => 'text-state-warning',
        'error' => 'text-state-error',
    ][$variant] ?? 'text-state-info';

    $icon ??= [
        'neutral' => 'heroicon-s-information-circle',
        'info' => 'heroicon-s-information-circle',
        'success' => 'heroicon-s-check-circle',
        'warning' => 'heroicon-s-exclamation-triangle',
        'error' => 'heroicon-s-x-circle',
    ][$variant] ?? 'heroicon-s-information-circle';
@endphp

<div {{ $attributes->class(['rounded-xl border px-4 py-3 flex items-start gap-2.5 text-sm', $styles]) }} role="status">
    @if($icon)
        <x-dynamic-component :component="$icon" class="w-5 h-5 shrink-0 {{ $iconColor }}" />
    @endif
    <div class="min-w-0 flex-1 break-words">
        @if($title)
            <strong class="font-semibold">{{ $title }}</strong>
        @endif
        {{ $slot }}
    </div>
</div>
