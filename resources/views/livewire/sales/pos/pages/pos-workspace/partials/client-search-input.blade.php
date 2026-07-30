{{--
    Input de búsqueda de clientes (por nombre o RNC/Cédula). Compartido entre el modal
    de escritorio y el bottom sheet móvil — el wrapper (padding/borde) queda en cada
    caller porque difiere según el contenedor; esto es solo el ícono + input.

    Props:
    - autofocus (bool): true en el modal de escritorio; false en el bottom sheet móvil
      (aparece con animación de abajo hacia arriba, forzar foco ahí se siente brusco).
--}}
@php $autofocus = $autofocus ?? false; @endphp
<div class="relative">
    <x-heroicon-s-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
    <input type="text" x-model="clientSearch" placeholder="Buscar por nombre o RNC/Cédula…" @if($autofocus) autofocus @endif
           class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-[#58c03f] focus:border-[#58c03f]">
</div>
