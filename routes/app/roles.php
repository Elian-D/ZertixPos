<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/roles', [RoleController::class, 'index'])
    ->middleware('permission:roles index')
    ->name('roles.index');

Route::get('/roles/create', [RoleController::class, 'create'])
    ->middleware('permission:roles create')
    ->name('roles.create');

Route::post('/roles', [RoleController::class, 'store'])
    ->middleware('permission:roles create')
    ->name('roles.store');

Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
    ->middleware('permission:roles edit')
    ->name('roles.edit');

Route::put('/roles/{role}', [RoleController::class, 'update'])
    ->middleware('permission:roles edit')
    ->name('roles.update');

Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
    ->middleware('permission:roles delete')
    ->name('roles.destroy');

Route::get('/roles/{role}/permissions', [RoleController::class, 'editPermissions'])
    ->middleware('permission:roles assign')
    ->name('roles.permissions.edit');

Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
    ->middleware('permission:roles assign')
    ->name('roles.permissions.update');
