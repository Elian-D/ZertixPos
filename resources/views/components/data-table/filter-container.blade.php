@props(['formId'])

<div>
    <form id="{{ $formId }}" method="GET" x-on:submit.prevent class="space-y-4">
        {{-- Layout dinámico: En móvil apilado, en tablet/pc en línea --}}
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            {{ $slot }}
        </div>

        {{-- Contenedor de Chips (solo se muestra si hay filtros) --}}
        <div id="active-filters" class="flex flex-wrap items-center gap-2 empty:hidden border-t border-gray-50 pt-3">
            {{-- Chips dinámicos --}}
        </div>
    </form>
</div>