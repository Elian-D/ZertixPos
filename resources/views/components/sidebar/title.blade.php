<h3 class="mt-4 mb-1 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400"
    x-show="isSidebarOpen || hasHover"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-2"
    x-transition:enter-end="opacity-100 translate-x-0">
    {{ $slot }}
</h3>