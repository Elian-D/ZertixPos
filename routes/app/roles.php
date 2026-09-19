<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Movido bajo config/* (Fase 7.9, sidebar) — mismo motivo que users.php:
// Roles/Permisos vive en el dropdown "Configuración" pero su URL no estaba
// bajo app/config. Nombres de ruta roles.*→config.roles.*, URL
// app/roles*→app/config/roles*.
Route::prefix('config/roles')->as('config.roles.')->group(function () {

    Route::get('/', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('index');

    Route::get('/create', [RoleController::class, 'create'])
        ->middleware('permission:roles.create')
        ->name('create');

    Route::post('/', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('store');

    Route::get('/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.edit')
        ->name('edit');

    Route::put('/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.edit')
        ->name('update');

    Route::delete('/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('destroy');

    // permissions.edit/permissions.update (pantalla de "asignar permisos" aparte)
    // eliminadas — REQ-2.7 punto 6: los permisos se seleccionan directo en
    // create/edit, ya gateados por `permission:roles.create`/`roles.edit`
    // más arriba (roles.assign deja de proteger nada real acá).
});
