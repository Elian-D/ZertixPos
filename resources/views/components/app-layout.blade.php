<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ sidebarOpen: window.innerWidth >= 640 ? (localStorage.getItem('sidebarOpen') !== null ? localStorage.getItem('sidebarOpen') === 'true' : true) : false }"
      x-init="$watch('sidebarOpen', val => {
          if (window.innerWidth >= 640) localStorage.setItem('sidebarOpen', val);
          setTimeout(() => window.dispatchEvent(new Event('resize-charts')), 310);
      })">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Red de seguridad contra FOUC: la regla [x-cloak] real vive en app.css
             (vía @vite, más abajo), pero esa hoja depende de que termine de
             descargar/parsear — con la tipografía de Google Fonts y @vite
             compitiendo por ser la primera hoja bloqueante, cualquier demora deja
             una ventana donde el sidebar/header (marcados con x-cloak) se pintan
             sin ocultar antes de que Alpine los procese. Esta regla inline es
             síncrona y se aplica en el primer paint, sin depender de ningún
             recurso externo — mismo principio que theme-init.blade.php de Orvian. --}}
        <style>[x-cloak]{display:none!important}</style>

        {{-- Favicon con el isotipo real (Branding ZertixPOS). El media query es
             CSS puro del navegador (prefers-color-scheme) — el navegador elige
             solo según el tema del SO/navegador del usuario, sin depender de que
             la app tenga implementado darkMode en Tailwind (mismo patrón que
             ComponentsTEMP/app.blade.php de Orvian). --}}
        <link rel="icon" href="{{ asset('img/logos/isotipo.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
        <link rel="icon" href="{{ asset('img/logos/isotipo-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        {{-- 1. Directiva de Estilos de Livewire --}}
            @livewireStyles

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body class="font-sans antialiased" x-cloak>

        <div class="flex h-screen overflow-hidden bg-gray-100">

            {{-- Overlay para móvil (drawer completo) — sm:hidden lo oculta en desktop
                 vía CSS, no con un chequeo de window.innerWidth en JS (ese chequeo no
                 era reactivo al resize, quedaba desincronizado hasta el próximo click). --}}
            <div x-show="sidebarOpen" x-cloak
                 @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 sm:hidden">
            </div>

            @include('layouts.sidebar')

            {{-- CONTENIDO PRINCIPAL — sin ml-64/ml-20: el <aside> de x-sidebar.layout ya es un
                 flex-child normal (sm:relative) que empuja el contenido con su propio ancho
                 animado, no un elemento position:fixed que había que compensar a mano. --}}
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

                {{-- HEADER / TOPBAR --}}
                <x-header />

                {{--
                    CONTENIDO VARIABLE — misma estructura que app.blade.php de Orvian (Fase 7):
                    <main> es el único que scrollea (overflow-y-auto, flex-col) para que el header
                    quede siempre fijo arriba; adentro, un div con el padding real del contenido
                    (donde van breadcrumbs de REQ-7.8 antes de $slot) y, como hermano después de
                    ese div, el lugar para <x-ui.footer /> de REQ-7.7 — footer pegado al fondo del
                    scroll, no al fondo de la ventana, igual que en Orvian.
                --}}
                <main class="flex-1 overflow-y-auto custom-scroll flex flex-col">
                    <div class="flex-1 p-4 md:p-6 relative">
                        <x-ui.breadcrumbs />
                        {{ $slot }}
                    </div>
                    <x-ui.footer />
                </main>

            </div>
        </div>

        {{-- Fuera del wrapper .h-screen.overflow-hidden a propósito (igual que Orvian): un solo
             x-ui.toasts para toda la app, no uno por vista. Vivir fuera evita depender de que
             ningún ancestro con transform/filter (que rompería position:fixed) se agregue después
             dentro del wrapper de contenido. --}}
        <x-ui.toasts />

        @stack('scripts')

        {{-- Directiva estándar de Livewire (trae Alpine embebido, arranque único
             garantizado internamente) — reemplaza al @livewireScriptConfig manual
             que causaba el FOUC, ver resources/js/app.js. --}}
        @livewireScripts
    </body>
</html>