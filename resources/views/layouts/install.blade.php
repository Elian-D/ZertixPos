<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ZertixPOS') }} — Instalación</title>

        <link rel="icon" href="{{ asset('img/logos/isotipo.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
        <link rel="icon" href="{{ asset('img/logos/isotipo-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        @livewireStyles

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased h-full bg-gray-50 text-gray-900">
        {{ $slot }}

        {{-- Directiva estándar de Livewire (ver resources/js/app.js, Fase 7.9) --}}
        @livewireScripts
    </body>
</html>
