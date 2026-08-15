<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:users index')
    ->get('/users', [UserController::class, 'index'])
    ->name('users.index');

Route::middleware('permission:users create')
    ->get('/users/create', [UserController::class, 'create'])
    ->name('users.create');

Route::middleware('permission:users create')
    ->post('/users', [UserController::class, 'store'])
    ->name('users.store');

Route::middleware('permission:users edit')
    ->get('/users/{user}/edit', [UserController::class, 'edit'])
    ->name('users.edit');

Route::middleware('permission:users edit')
    ->put('/users/{user}', [UserController::class, 'update'])
    ->name('users.update');

Route::middleware('permission:users delete')
    ->delete('/users/{user}', [UserController::class, 'destroy'])
    ->name('users.destroy');

// Editar roles
Route::middleware('permission:users assign')
    ->get('/users/{user}/roles', [UserController::class, 'editRoles'])
    ->name('users.roles.edit');

// Actualizar roles
Route::middleware('permission:users assign')
    ->put('/users/{user}/roles', [UserController::class, 'updateRole'])
    ->name('users.roles.update');
