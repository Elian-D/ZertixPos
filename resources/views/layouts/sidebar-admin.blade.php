{{--
    SIDEBAR — Panel de Súper Admin (Fase 5, REQ-5). Envuelto en el mismo
    <x-sidebar.layout> que resources/views/layouts/sidebar.blade.php (mismo
    colapsar/expandir, menú de usuario y logout) — solo cambia qué items
    trae adentro. El mockup de Stitch trae 4 items (Tenants/Planes/Uso y
    Límites/Configuración Global); solo "Tenants" tiene un REQ real detrás
    (ver docs/features/v1.3.0.md §Fase 5, nota de alcance) — los otros 3 se
    agregan en una fase futura, no ahora.
--}}
<x-sidebar.layout>
    <x-sidebar.item href="{{ route('admin.tenants.index') }}" icon="heroicon-s-building-office-2">
        Tenants
    </x-sidebar.item>
</x-sidebar.layout>
