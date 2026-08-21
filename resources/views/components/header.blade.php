<header>
    <nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
        <div class="px-4 sm:px-6 lg:px-8"> 
            <div class="flex justify-between h-16">
                
                <div class="flex items-center">
                    <button @click="isSidebarOpen = !isSidebarOpen"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                        
                        <svg class="h-6 w-6 transition-transform duration-300" 
                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800 ml-4 hidden sm:block">
                        Dashboard
                    </h1>
                </div>
                
                <div class="flex items-center space-x-4">

                    <x-ui.button href="{{ route('sales.pos.index') }}" variant="primary" :hoverEffect="true" iconLeft="heroicon-o-truck" class="hidden sm:inline-flex">
                        Ir al POS
                    </x-ui.button>

                    <div class="flex items-center">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 transition duration-150 ease-in-out focus:outline-none">
                                    <span class="mr-2 font-bold hidden sm:inline">{{ Auth::user()->name }}</span>
                                    
                                    <div class="w-9 h-9 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center border-2 border-transparent hover:border-indigo-400 transition-colors duration-200">
                                        
                                        {{-- Lógica para AVATAR DE INICIALES --}}
                                        @if (Auth::user()->avatar_url) {{-- Asumiendo que tienes un campo 'avatar_url' para la imagen real --}}
                                            <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                                        @else
                                            {{-- Usamos el método que creaste en el modelo User --}}
                                            <span class="text-sm font-semibold text-gray-600">
                                                {{ Auth::user()->getInitials() }}
                                            </span>
                                        @endif
                                        
                                    </div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')" class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    <span>{{ __('Profile') }}</span>
                                </x-dropdown-link>

                                <div class="border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="flex items-center space-x-2 text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                        <span>{{ __('Log Out') }}</span>
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>