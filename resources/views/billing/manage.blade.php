<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ZertixPOS') }} — Plan y Suscripción</title>

        <link rel="icon" href="{{ asset('img/logos/isotipo.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
        <link rel="icon" href="{{ asset('img/logos/isotipo-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

        @livewireStyles

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    {{--
        REQ-3.11, v1.3.0 Fase 3 — chrome propio (header/footer con logo real),
        no `x-app-layout` (sidebar completo no aplica acá, mismo criterio que
        `layouts/install.blade.php`) ni `x-guest-layout` (su panel dividido es
        angosto — no entran las 3 tarjetas de plan lado a lado).
    --}}
    <body class="font-sans antialiased h-full bg-slate-50 text-slate-900">
        <livewire:billing.manage-subscription />

        @livewireScripts
    </body>
</html>
