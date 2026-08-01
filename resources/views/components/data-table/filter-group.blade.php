@props(['title', 'collapsed' => false])

<div x-data="{ expanded: {{ $collapsed ? 'false' : 'true' }} }" 
     class="border-b border-gray-100 last:border-0">
    
    {{-- Header del grupo --}}
    <button type="button" 
            @click="expanded = !expanded"
            class="w-full flex items-center justify-between py-2.5 text-left group hover:bg-gray-50 transition rounded-lg px-2">
        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
            {{ $title }}
        </span>
        <x-heroicon-s-chevron-down 
            class="w-4 h-4 text-gray-400 transition-transform duration-200"
            x-bind:class="{ 'rotate-180': expanded }" />
    </button>

    {{-- Contenido del grupo con animación suave --}}
    <div x-show="expanded" 
         x-collapse
         x-transition:enter="transition-all ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition-all ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="space-y-3 pb-3 px-2">
        {{ $slot }}
    </div>
</div>