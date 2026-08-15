<aside x-data="{ 
        hasHover: false,
        activeDropdown: null
            }"
    @mouseover="if (window.innerWidth >= 640 && !isSidebarOpen) hasHover = true"
    @mouseleave="if (window.innerWidth >= 640 && !isSidebarOpen) hasHover = false"
    x-cloak

    class="bg-white shadow-md border-r border-gray-100 transition-all duration-300 ease-in-out z-40"
    
    :class="{
        // ANCHOS
        'w-64': isSidebarOpen || hasHover || (window.innerWidth < 640),
        'w-20': !isSidebarOpen && !hasHover && (window.innerWidth >= 640),

        // POSICIONAMIENTO
        'fixed inset-y-0 left-0 z-50': window.innerWidth < 640,
        'fixed inset-y-0 left-0 z-10 sm:block': window.innerWidth >= 640,

        // MOVIL → si está cerrado, que esté fuera de pantalla
        '-translate-x-full': !isSidebarOpen && window.innerWidth < 640,
        'translate-x-0': isSidebarOpen
    }"
>
    
    {{-- ESTRUCTURA INTERNA: Utilizamos Flexbox para ordenar Header y Contenido --}}
    <div class="flex flex-col h-full"> 

        <div class="flex items-center gap-3 p-5 border-b border-gray-100 flex-shrink-0">
            <x-fas-chart-line class="w-5 h-5 text-indigo-500 flex-shrink-0" />

            <span x-show="isSidebarOpen || hasHover"
                  x-transition
                  class="font-bold text-gray-800 tracking-tight whitespace-nowrap">
                Gestión <span class="text-indigo-600">Ventas</span>
            </span>
        </div>


        <nav class="p-4 space-y-1.5 flex-1 overflow-y-auto custom-scroll">
            {{ $slot }}
        </nav>
        
    </div> 

</aside>