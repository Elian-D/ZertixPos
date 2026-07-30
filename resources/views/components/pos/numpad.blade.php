{{--
    Numpad táctil reutilizable (Fase 7.6). Componente puramente de presentación:
    no declara su propio x-data, así que los clicks evalúan expresiones Alpine
    directamente contra el scope del padre.

    Sin uso activo por ahora (el checkout de efectivo pasó a tecleo real + botones
    de denominación, y la pantalla de lock dedicada se eliminó — el Lobby es el
    único punto de PIN). Se deja el componente listo para un futuro modo AIO
    100% táctil sin teclado físico.

    Props:
    - digit: nombre del método del padre a invocar con el dígito pulsado, ej. "addNumber"
    - clear: nombre del método del padre para limpiar el valor, ej. "clear"
    - backspace: nombre del método del padre para borrar el último dígito, ej. "backspace"
    - dark: si true, usa la paleta oscura; si false, paleta clara
    - compact: si true, botones reducidos para pantallas de baja altura (uso con teclado/mouse);
      si false, tamaño táctil completo (AIO)
--}}
@props([
    'digit' => 'addNumber',
    'clear' => 'clear',
    'backspace' => 'backspace',
    'dark' => false,
    'compact' => false,
])

@php
    $height = $compact ? 'h-9' : 'h-16';
    $rounded = $compact ? 'rounded-lg' : 'rounded-2xl';
    $textSize = $compact ? 'text-sm' : 'text-2xl';
    $gap = $compact ? 'gap-1.5' : 'gap-4';
    $iconSize = $compact ? 'w-4 h-4' : 'w-6 h-6';

    $btnBase = $dark
        ? "{$height} {$rounded} bg-white/5 border border-white/5 {$textSize} font-bold text-white hover:bg-white/10 active:scale-95 transition-all"
        : "{$height} {$rounded} bg-gray-50 border border-gray-200 {$textSize} font-bold text-gray-800 hover:bg-gray-100 active:scale-95 transition-all";

    $clearBtn = $dark
        ? "{$height} {$rounded} bg-red-500/10 border border-red-500/20 text-red-400 font-bold {$textSize} hover:bg-red-500/20 active:scale-95 transition-all"
        : "{$height} {$rounded} bg-red-50 border border-red-200 text-red-500 font-bold {$textSize} hover:bg-red-100 active:scale-95 transition-all";

    $backBtn = $dark
        ? "{$height} {$rounded} bg-white/5 border border-white/5 flex items-center justify-center text-white hover:bg-white/10 active:scale-95 transition-all"
        : "{$height} {$rounded} bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100 active:scale-95 transition-all";
@endphp

<div {{ $attributes->merge(['class' => "grid grid-cols-3 {$gap}"]) }}>
    <template x-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]" :key="n">
        <button type="button" @click="{{ $digit }}(n)" class="{{ $btnBase }}">
            <span x-text="n"></span>
        </button>
    </template>

    <button type="button" @click="{{ $clear }}()" class="{{ $clearBtn }}">C</button>

    <button type="button" @click="{{ $digit }}(0)" class="{{ $btnBase }}">0</button>

    <button type="button" @click="{{ $backspace }}()" class="{{ $backBtn }}">
        <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414A2 2 0 0010.828 19h6.344a2 2 0 002-2V7a2 2 0 00-2-2h-6.344a2 2 0 00-1.414.586L3 12z" />
        </svg>
    </button>
</div>
