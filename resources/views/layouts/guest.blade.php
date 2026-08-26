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
    <body class="font-sans text-gray-900 antialiased">
        <div class="w-full min-h-screen flex flex-col md:flex-row justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="hidden lg:flex flex-col justify-center items-center gap-7 w-2/5 p-10 h-screen bg-custom-gradient-1 ">
                <div class="w-fit p-4 rounded-2xl bg-custom-gradient-2">
                    <a href="/">
                        <x-fas-chart-line class="w-8 h-8 fill-current text-white" />
                    </a>
                </div>
                <h2 class="font-bold text-2xl text-white">Gestión Ventas</h2>
                <p class="text-center text-blue-100">Administra ventas, clientes y rutas en un solo panel, simple y seguro.</p>
                <div class="flex gap-4">
                    <span class="flex gap-1 items-center text-blue-100 font-base font-semibold"> 
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        Ventas
                    </span>
                    <span class="flex gap-1 items-center text-blue-100 font-base font-semibold"> 
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        Clientes
                    </span>
                    <span class="flex gap-1 items-center text-blue-100 font-base font-semibold"> 
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        Reportes
                    </span>
                    <span class="flex gap-1 items-center text-blue-100 font-base font-semibold"> 
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        Rutas
                    </span>
                </div>
            </div>
            
            <div class="w-full p-6 flex items-center justify-center lg:w-3/5">
                <div class="flex flex-col gap-5 w-full px-10 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg sm:w-3/5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>


