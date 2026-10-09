{{--
    x-ui.confirm-modal — confirmación de una acción que no es eliminar (v1.5.0): aplicar
    una toma física, cancelar un borrador, aprobar una orden, enviar una transferencia.
    Para eliminar o anular un registro sigue siendo x-ui.confirm-deletion-modal.

    Se abre con $dispatch('open-modal', '<name>').

    PROPS:
      name          — nombre del modal (obligatorio).
      title         — pregunta en el encabezado (ej. '¿Aplicar toma física?').
      description   — explicación de lo que pasa al confirmar (texto plano).
      route         — acción del <form method="POST">.
      method        — POST | PUT | PATCH | DELETE (default POST).
      confirmLabel  — texto del botón (default 'Confirmar').
      variant       — primary | error | warning (color del ícono y del botón; default primary).
      icon          — heroicon del encabezado (default uno por variante).
      show          — abre el modal al cargar (ej. :show="$errors->has('void_reason')" para que
                      un error de validación de un campo del slot no quede escondido).

    SLOT (opcional): detalle extra bajo la descripción (ej. un x-ui.alert con lo que cambia).
--}}
@props([
    'name',
    'title',
    'description' => null,
    'route',
    'method' => 'POST',
    'confirmLabel' => 'Confirmar',
    'variant' => 'primary',
    'icon' => null,
    'show' => false,
])

@php
    $iconBox = [
        'primary' => 'bg-zertix-primary/10 text-zertix-primary',
        'warning' => 'bg-state-warning/10 text-state-warning',
        'error' => 'bg-state-error/10 text-state-error',
    ][$variant] ?? 'bg-zertix-primary/10 text-zertix-primary';

    $icon ??= [
        'primary' => 'heroicon-s-check-circle',
        'warning' => 'heroicon-s-exclamation-triangle',
        'error' => 'heroicon-s-x-circle',
    ][$variant] ?? 'heroicon-s-check-circle';
@endphp

<x-modal :name="$name" maxWidth="md" :show="$show">
    <form method="POST" action="{{ $route }}" class="p-6">
        @csrf
        @method($method)

        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $iconBox }}">
                <x-dynamic-component :component="$icon" class="w-7 h-7" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900 leading-tight">{{ $title }}</h2>
        </div>

        @if($description)
            <p class="mt-4 text-sm text-gray-600 leading-relaxed">{{ $description }}</p>
        @endif

        @if($slot->isNotEmpty())
            <div class="mt-4">{{ $slot }}</div>
        @endif

        <div class="mt-8 flex justify-end items-center gap-3">
            <x-ui.button appearance="ghost" variant="secondary" x-on:click="$dispatch('close')">Volver</x-ui.button>
            <x-ui.button type="submit" :variant="$variant">{{ $confirmLabel }}</x-ui.button>
        </div>
    </form>
</x-modal>
