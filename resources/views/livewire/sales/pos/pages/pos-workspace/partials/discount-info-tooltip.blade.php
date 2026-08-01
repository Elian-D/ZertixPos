{{--
    Explica la Regla de Exclusión del descuento global (11.2.5): que un ítem con descuento
    propio no recibe también una porción del descuento global. Un solo markup cubre los dos
    dispositivos sin duplicar nada — hover abre/cierra en desktop, tap alcanza porque @click
    también dispara con touch, y @click.outside cierra al tocar fuera en móvil.
--}}
<span class="relative inline-flex" x-data="{ open: false }" @click.outside="open = false">
    <button type="button"
            @mouseenter="open = true" @mouseleave="open = false"
            @click="open = !open"
            class="w-3.5 h-3.5 flex items-center justify-center rounded-full bg-gray-200 text-gray-500 text-[9px] font-bold leading-none hover:bg-gray-300 transition-colors"
            aria-label="¿Cómo aplica el descuento global?">
        i
    </button>
    <div x-show="open" x-cloak x-transition
         class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 w-44 p-2 bg-gray-900 text-white text-[10px] leading-snug rounded-lg shadow-lg text-center">
        Aplica únicamente a productos sin descuento individual.
        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
    </div>
</span>
