<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso denegado</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-5">

    <div class="max-w-md w-full text-center bg-white p-10 rounded-xl shadow-2xl border-t-4 border-red-600">
        
        <div class="mb-6">
            {{-- Ícono de Advertencia --}}
            <svg class="mx-auto w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.398 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>

        {{-- Código de Error --}}
        <h1 class="text-7xl font-extrabold text-red-600 tracking-wider">403</h1>
        
        {{-- Título --}}
        <h2 class="text-3xl font-semibold text-gray-800 mb-4">Acceso Denegado (Forbidden)</h2>
        
        {{-- Mensaje --}}
        <p class="text-gray-600 mb-8">
            Lo sentimos, pero no tienes los permisos necesarios para ver el contenido de esta página.
            Por favor, contacta al administrador del sistema si crees que esto es un error.
        </p>

        {{-- BOTÓN PARA REGRESAR A LA URL ANTERIOR --}}
        <button onclick="history.back()"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-zertix-primary-600 hover:bg-zertix-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zertix-primary-500 transition duration-150 ease-in-out">
            
            {{-- Ícono de flecha (Opcional) --}}
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            
            Regresar a la Página Anterior
        </button>

        {{-- Enlace opcional a la página principal --}}
        <a href="/" class="block mt-4 text-sm text-zertix-primary-500 hover:text-zertix-primary-700">Ir a la página principal</a>
    </div>

</body>
</html>