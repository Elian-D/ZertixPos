<x-app-layout>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-xl font-medium text-gray-700 mb-6 px-1">
                {{ __('Editar usuario: ') }} <span class="text-zertix-primary-600 font-semibold">{{ $user->name }}</span>
            </h2>

            <form action="{{ route('config.users.update', $user) }}" method="POST" x-data="{}">
                @csrf
                @method('PUT')

                @include('users.partials.form')

                <div class="flex justify-end space-x-4 pt-6 mt-6">
                    <x-ui.button href="{{ route('config.users.index') }}" appearance="ghost" variant="secondary">
                        Cancelar
                    </x-ui.button>

                    <x-ui.button type="submit" variant="primary" iconLeft="heroicon-s-user-plus">
                        Actualizar Usuario
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
