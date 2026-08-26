<h3 x-show="sidebarOpen" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-2"
    x-transition:enter-end="opacity-100 translate-x-0"
    class="mt-6 mb-2 px-4 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">
    {{ $slot }}
</h3>
<div x-show="!sidebarOpen" x-cloak class="h-px bg-slate-100 my-4 mx-4"></div>
