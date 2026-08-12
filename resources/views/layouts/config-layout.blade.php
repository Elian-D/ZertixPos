<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Configuración | {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="font-sans antialiased"
    x-data="{
        isSidebarOpen: false,
        updateSidebarState() {
            this.isSidebarOpen = window.innerWidth >= 640;
        }
    }"
    x-init="
        updateSidebarState();
        window.addEventListener('resize', () => updateSidebarState());
    "
>
<div class="flex min-h-screen bg-gray-100">

{{-- SIDEBAR CONFIGURACIÓN --}}
<x-sidebar.layout>

    {{-- ===== SISTEMA ===== --}}
    <x-sidebar.group>
        <x-sidebar.title>Sistema</x-sidebar.title>

        <x-sidebar.item
            href="/dashboard"
            icon="heroicon-s-arrow-left">
            Volver al sistema
        </x-sidebar.item>
    </x-sidebar.group>
    
    {{-- ===== GENERAL ===== --}}
    <x-sidebar.group>
        <x-sidebar.title>General</x-sidebar.title>

        <x-sidebar.item
            href="{{ route('configuration.index') }}"
            icon="heroicon-s-cog-6-tooth">
            Panel de configuración
        </x-sidebar.item>

        <x-sidebar.item
            href="{{ route('configuration.general.edit') }}"
            icon="heroicon-s-adjustments-horizontal">
            Datos generales
        </x-sidebar.item>
    </x-sidebar.group>

    {{-- ===== FINANZAS ===== --}}
    <x-sidebar.group>
        <x-sidebar.title>Finanzas</x-sidebar.title>

        <x-sidebar.item
            href="{{ route('configuration.pagos.index') }}"
            icon="heroicon-s-credit-card">
            Métodos de pago
        </x-sidebar.item>
    </x-sidebar.group>

    {{-- ===== CALENDARIO / OPERACIÓN ===== --}}
    <x-sidebar.group>
        <x-sidebar.title>Operación</x-sidebar.title>

        <x-sidebar.item
            href="{{ route('configuration.dias.index') }}"
            icon="heroicon-s-calendar-days">
            Días laborables
        </x-sidebar.item>
    </x-sidebar.group>

</x-sidebar.layout>


    {{-- CONTENIDO --}}
    <div class="flex-1 flex flex-col transition-all duration-300 ml-0"
        :class="{
            'sm:ml-64': isSidebarOpen,
            'sm:ml-20': !isSidebarOpen
        }">

        <x-header />

        <main class="p-6">
            {{ $slot }}
        </main>

    </div>
</div>
</body>
</html>
