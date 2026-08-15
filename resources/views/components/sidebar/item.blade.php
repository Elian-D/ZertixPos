@props([
    'href' => '#',
    'icon' => null,
])

@php
    // Normaliza el path para usar en request()->is()
    $path = parse_url($href, PHP_URL_PATH) ?: $href;
    $path = ltrim($path, '/');
    $isActive = request()->is($path) || request()->is($path.'*') || url()->current() === url($href);
@endphp

<a href="{{ $href }}"
   class="group flex items-center gap-3 p-2 rounded-lg text-sm font-medium
          text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-300 {{ $isActive ? 'bg-indigo-50 text-indigo-600' : '' }}">
    
    {{-- Ícono --}}
    @if($icon)
        <x-dynamic-component :component="$icon"
            class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 flex-shrink-0" />
    @endif

    {{-- Texto --}}
    <span
        x-show="isSidebarOpen || hasHover"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        class="whitespace-nowrap overflow-hidden">
        {{ $slot }}
    </span>
</a>
