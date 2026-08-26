<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Movido bajo config/* (Fase 7.9, sidebar): Usuarios vive dentro del dropdown
// "Configuración" en el sidebar, pero su URL no estaba bajo app/config — el
// comodín app/config* de ese dropdown nunca lo atrapaba, así que "Configuración"
// no se resaltaba/auto-abría al visitar estas páginas. Nombres de ruta
// users.*→config.users.*, URL app/users*→app/config/users*.
Route::prefix('config/users')->as('config.users.')->group(function () {

    Route::middleware('permission:users index')
        ->get('/', [UserController::class, 'index'])
        ->name('index');

    Route::middleware('permission:users create')
        ->get('/create', [UserController::class, 'create'])
        ->name('create');

    Route::middleware('permission:users create')
        ->post('/', [UserController::class, 'store'])
        ->name('store');

    Route::middleware('permission:users edit')
        ->get('/{user}/edit', [UserController::class, 'edit'])
        ->name('edit');

    Route::middleware('permission:users edit')
        ->put('/{user}', [UserController::class, 'update'])
        ->name('update');

    Route::middleware('permission:users delete')
        ->delete('/{user}', [UserController::class, 'destroy'])
        ->name('destroy');

    // Editar roles
    Route::middleware('permission:users assign')
        ->get('/{user}/roles', [UserController::class, 'editRoles'])
        ->name('roles.edit');

    // Actualizar roles
    Route::middleware('permission:users assign')
        ->put('/{user}/roles', [UserController::class, 'updateRole'])
        ->name('roles.update');
});
