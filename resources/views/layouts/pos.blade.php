<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ZertixPOS') }} — Terminal POS</title>

        {{-- Red de seguridad contra FOUC (mismo fix que app-layout.blade.php,
             Fase 7.9) — regla [x-cloak] síncrona, no depende de que @vite
             termine de cargar app.css. --}}
        <style>[x-cloak]{display:none!important}</style>

        <link rel="icon" href="{{ asset('img/logos/isotipo.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
        <link rel="icon" href="{{ asset('img/logos/isotipo-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        {{-- Estilos Core de Livewire --}}
        @livewireStyles

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body class="font-sans antialiased h-full bg-gray-50 text-gray-900 selection:bg-indigo-500 selection:text-white overflow-hidden">
        
        {{-- Contenedor Principal Inmersivo Full-Screen --}}
        <div class="min-h-screen h-screen flex flex-col overflow-hidden relative">
            
            {{-- Renderizado de la página/componente Livewire --}}
            <main class="flex-1 flex flex-col min-h-0 min-w-0 overflow-hidden">
                {{ $slot }}
            </main>

        </div>

        @stack('scripts')
        
        {{-- Directiva estándar de Livewire (ver resources/js/app.js, Fase 7.9) --}}
        @livewireScripts
    </body>
</html>