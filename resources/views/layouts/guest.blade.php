<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('img/logos/isotipo.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
        <link rel="icon" href="{{ asset('img/logos/isotipo-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased">
        <div class="min-h-screen w-full flex flex-col lg:flex-row">

            {{--
                PANEL IZQUIERDO (REQ-7.11) — split-screen del mockup Stitch "Login
                ZertixPOS - Split Screen View" (proyecto "ZertixPOS ERP System",
                projects/17155070362470545296). Degradado navy con los mismos hex
                exactos que ya son tokens de marca (zertix-secondary.dark → DEFAULT),
                no colores nuevos. Oculto en móvil (hidden lg:flex) — mismo criterio
                que el resto del sistema (sidebar, header) de no cargar contenido
                decorativo en pantallas chicas.
            --}}
            <div class="hidden lg:flex w-full lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden"
                 style="background: linear-gradient(135deg, #0B2E5B 0%, #1E4F8C 100%);">

                {{-- Cuadrícula decorativa (la "cuadrícula atrás" del mockup) — SVG
                     con un <pattern> de líneas de 40x40px, blanco al 5% de opacidad,
                     tal cual el mockup original. --}}
                <div class="absolute inset-0 pointer-events-none opacity-5">
                    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="login-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1.5" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#login-grid)" />
                    </svg>
                </div>

                {{-- Blobs difuminados decorativos, mismos del mockup --}}
                <div class="absolute -bottom-64 -left-64 w-96 h-96 rounded-full bg-zertix-primary mix-blend-overlay opacity-20 blur-3xl"></div>
                <div class="absolute top-1/4 -right-32 w-64 h-64 rounded-full bg-white mix-blend-overlay opacity-10 blur-3xl"></div>

                {{-- Logo — variante "dark" (sin navy) porque el navy del logo se
                     perdería contra este mismo fondo navy. --}}
                <a href="/" class="relative z-10 inline-block w-fit">
                    <x-ui.application-logo type="full" dark class="h-10 w-auto" />
                </a>

                <div class="relative z-10 max-w-lg mt-24 mb-auto">
                    <h2 class="text-white font-bold text-[36px] leading-[44px] mb-6">
                        Gestiona tu negocio en un solo lugar
                    </h2>
                    <p class="text-white/70 text-base leading-relaxed mb-12">
                        Punto de venta, control de inventario y contabilidad integrada en una sola plataforma diseñada para acelerar tus ventas.
                    </p>

                    <div class="flex items-start gap-8">
                        <div class="flex flex-col items-start gap-2">
                            <div class="size-12 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm text-zertix-primary">
                                <x-heroicon-s-banknotes class="w-6 h-6" />
                            </div>
                            <span class="text-white text-sm font-medium">Ventas ágiles</span>
                        </div>
                        <div class="flex flex-col items-start gap-2">
                            <div class="size-12 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm text-zertix-primary">
                                <x-heroicon-s-cube class="w-6 h-6" />
                            </div>
                            <span class="text-white text-sm font-medium">Inventario real</span>
                        </div>
                        <div class="flex flex-col items-start gap-2">
                            <div class="size-12 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm text-zertix-primary">
                                <x-heroicon-s-calculator class="w-6 h-6" />
                            </div>
                            <span class="text-white text-sm font-medium">Finanzas</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 text-white/50 text-sm">
                    © {{ now()->year }} Zertix Technologies. Todos los derechos reservados.
                </div>
            </div>

            {{-- PANEL DERECHO — fondo neutro claro (slate-50, el mismo neutro que
                 usa el resto del sistema) en vez del bg-gray-100 genérico previo. --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-slate-50 relative min-h-screen">

                {{-- Logo móvil (oculto en desktop, donde ya está en el panel izquierdo) —
                     centrado horizontal, no pegado a la izquierda (REQ-7.11, ajuste). --}}
                <a href="/" class="lg:hidden flex items-center justify-center absolute top-8 left-1/2 -translate-x-1/2">
                    <x-ui.application-logo type="full" class="h-11 w-auto" />
                </a>

                <div class="w-full max-w-[420px] flex flex-col gap-5 px-8 py-8 sm:px-12 bg-white shadow-sm border border-slate-200 rounded-2xl">
                    {{ $slot }}
                </div>

                <div class="absolute bottom-8 text-slate-400 text-xs lg:hidden text-center">
                    © {{ now()->year }} ZertixPOS.
                </div>
            </div>
        </div>

        @stack('scripts')
        
        {{-- Directiva estándar de Livewire (ver resources/js/app.js, Fase 7.9) --}}
        @livewireScripts
    </body>
</html>
