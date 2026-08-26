{{-- Agrupa varios <x-sidebar.dropdown>/<x-sidebar.item> bajo un mismo <x-sidebar.title>. Sin uso actual en app-layout.blade.php (los 6 grupos de módulos ya tienen sus propios <x-sidebar.dropdown>), disponible para cuando se quiera introducir secciones visuales. --}}
<div class="space-y-1">
    {{ $slot }}
</div>
