@props(['route', 'title' => 'Plantilla Base', 'format' => '.xlsx'])

<a href="{{ $route }}" class="flex items-center p-4 border rounded-xl hover:bg-zertix-primary-50 transition-all duration-200 border-zertix-primary-100 group shadow-sm hover:shadow-md">
    <div class="bg-zertix-primary-100 p-2 rounded-lg group-hover:bg-white transition-colors">
        <x-heroicon-s-document-text class="w-8 h-8 text-zertix-primary-600" />
    </div>
    <div class="ml-4">
        <span class="block font-bold text-zertix-primary-900 text-sm">{{ $title }}</span>
        <span class="text-xs text-zertix-primary-500 font-medium">Descargar formato {{ $format }}</span>
    </div>
</a>