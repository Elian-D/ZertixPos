<header>
    <nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
        <div class="px-4 sm:px-6 lg:px-8"> 
            <div class="flex justify-between h-16">
                
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen"
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

                    <x-ui.button href="{{ route('sales.pos.index') }}" variant="primary" appearance="ghost" iconLeft="heroicon-o-computer-desktop">
                        POS
                    </x-ui.button>

                </div>
            </div>
        </div>
    </nav>
</header>