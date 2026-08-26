<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

    <div class="text-center">
        <h2 class="font-black text-lg lg:text-2xl">Registrate</h2>
        <h3 class="text-gray-400 text-xs lg:text-lg">Ingresa tus credenciales para continuar</h3>
    </div>
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Juan Pablo" />
            <x-input-error :messages="$errors->get('name')" class="mt-2"/>
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="ejemplo@gmail.com"/>
            <x-input-error :messages="$errors->get('email')" class="mt-2"/>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" 
                            placeholder="•••••••••" />
                            

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirma contraseña')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" 
                            placeholder="•••••••••"/>

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-2">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zertix-primary-500" href="{{ route('login') }}">
                {{ __('¿Estás registrado?') }}
            </a>
        </div>

        <div class="flex items-center justify-end mt-4">

           <x-primary-button class=" justify-center bg-custom-gradient-2 ms-3 w-full text-center ms-0 hover:text-blue-200">
                {{ __('Register') }}
            </x-primary-button>
        </div>
        <div>
            <p class="mt-6 text-center text-sm text-gray-400">
                © 2025 Suportec Network SRL · Todos los derechos reservados
            </p>
        </div>
    </form>
</x-guest-layout>
