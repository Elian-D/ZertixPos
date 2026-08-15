@props([
    'icon' => null,
    'id' => null,
    'match' => null, // nuevo: patrones CSV o pipe
])

@php
    // Calcula si el dropdown debe estar activo desde el request
    $isDropdownActive = false;
    if ($match) {
        // soporta separadores , o |
        $patterns = preg_split('/[,|]/', $match);
        foreach ($patterns as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (request()->is(ltrim($p, '/'))) { $isDropdownActive = true; break; }
            if (request()->is(ltrim($p, '/').'*')) { $isDropdownActive = true; break; }
        }
    }
@endphp

<div class="w-full">
    
    {{-- BOTÓN PRINCIPAL --}}
    <button @click="if (isSidebarOpen || hasHover) activeDropdown = activeDropdown === '{{ $id }}' ? null : '{{ $id }}'"
        :class="{ 'bg-indigo-50 text-indigo-600': activeDropdown === '{{ $id }}' || {{ $isDropdownActive ? 'true' : 'false' }} }"
        class="flex items-center justify-between w-full p-2 rounded-lg text-sm font-medium
                text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-300">

        <div class="flex items-center gap-3">
            <x-dynamic-component :component="$icon" class="w-5 h-5 text-gray-400"/>
            <span
                x-show="isSidebarOpen || hasHover"
                x-cloak
                x-transition.opacity>
                {{ $slot }}
            </span>
        </div>

        <svg x-show="isSidebarOpen || hasHover"
             x-cloak
             :class="{ 'rotate-90': activeDropdown === '{{ $id }}' || {{ $isDropdownActive ? 'true' : 'false' }} }"
             class="w-4 h-4 text-gray-400 transition-transform duration-200"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 5l7 7-7 7" />
        </svg>
    </button>

    {{-- SUBMENÚ: si $isDropdownActive es true lo mostramos abierto inicialmente --}}
    <div
        x-show="(activeDropdown === '{{ $id }}' || {{ $isDropdownActive ? 'true' : 'false' }}) && (isSidebarOpen || hasHover)"
        x-cloak
        x-transition
        class="pl-10 pr-2 py-1 space-y-1">
        
        {{ $submenu }}

    </div>
</div>
