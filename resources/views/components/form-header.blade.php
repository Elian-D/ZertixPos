<div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b flex justify-between items-center">
    <div>
        <h2 class="text-xl font-bold text-gray-800">{{ $title }}</h2>
        @if($subtitle)
            <p class="text-xs text-gray-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>

    @if($backRoute)
        <a href="{{ $backRoute }}"
           class="p-2 bg-white border rounded-lg text-gray-400 hover:text-zertix-primary-600 transition shadow-sm">
            <x-heroicon-s-x-mark class="w-6 h-6"/>
        </a>
    @endif
</div>
