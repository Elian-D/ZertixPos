{{--
    Tarjeta del Centro de Configuración (REQ-7.4) — componente local, no
    x-ui.*: solo lo usa esta página, no es un primitivo reusado en todo el
    sistema (ver docs/ui/*.md, que cubre botón/badge/forms, no composición
    de página). Mismo criterio que resources/views/configuration/catalogs/
    index.blade.php (ya reemplazada) tenía con sus <a> a mano, solo que
    ahora se centraliza para no repetir el markup 8 veces.
--}}
@props([
    'href',
    'icon',
    'iconClass' => 'bg-slate-100 text-slate-600',
    'lockIcon' => false,
    'title',
    'description',
    'count' => null,
    'badge' => null,
    'actionLabel' => null,
])

<a href="{{ $href }}" class="group bg-white border border-slate-200 rounded-2xl p-5 flex items-center gap-4 shadow-sm hover:shadow-md hover:border-zertix-primary/40 transition-all relative overflow-hidden">

    @if($badge)
        <span class="absolute top-0 right-0 bg-purple-100 text-purple-700 text-[10px] font-bold px-2 py-1 rounded-bl-xl">
            {{ $badge }}
        </span>
    @endif

    <div class="w-12 h-12 rounded-lg {{ $iconClass }} flex items-center justify-center flex-shrink-0 relative">
        <x-dynamic-component :component="$icon" class="w-6 h-6" />
        @if($lockIcon)
            <x-heroicon-s-lock-closed class="w-3.5 h-3.5 text-zertix-secondary absolute -bottom-0.5 -right-0.5 bg-white rounded-full p-0.5" />
        @endif
    </div>

    <div class="flex-1 min-w-0 flex items-center gap-2">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-800 truncate">{{ $title }}</h3>
            <p class="text-xs text-slate-400 truncate">{{ $description }}</p>
        </div>
        @if($count !== null)
            <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-full ml-auto flex-shrink-0">
                {{ $count }}
            </span>
        @endif
    </div>

    @if($actionLabel)
        <span class="text-xs font-semibold text-zertix-primary group-hover:underline flex-shrink-0">{{ $actionLabel }}</span>
    @else
        <x-heroicon-s-arrow-right class="w-4 h-4 text-slate-300 group-hover:text-zertix-primary group-hover:translate-x-0.5 transition-all flex-shrink-0" />
    @endif
</a>
