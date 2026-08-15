@props(['href' => '#'])

<a href="{{ $href }}"
   class="block px-3 py-1.5 text-xs font-medium rounded-md text-gray-600
          hover:bg-gray-100 hover:text-indigo-600 transition
          {{ request()->is(ltrim($href, '/').'*') ? 'bg-gray-100 text-indigo-600' : '' }}">
    
    <span x-show="isSidebarOpen || hasHover"
          x-cloak
          x-transition.opacity>
        {{ $slot }}
    </span>
</a>
