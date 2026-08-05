<?php

use App\Http\Controllers\Products\CategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->as('products.')->group(function () {

    Route::middleware('permission:configure categories')->group(function () {

        Route::get('categories/eliminados', [CategoryController::class, 'eliminadas'])
            ->name('categories.eliminados');

        Route::resource('categories', CategoryController::class)
            ->parameters(['categories' => 'category'])
            ->names('categories');

        Route::patch('categories/{category}/estado', [CategoryController::class, 'toggleEstado'])
            ->name('categories.toggle');

        Route::patch('categories/{id}/restaurar', [CategoryController::class, 'restaurar'])
            ->name('categories.restaurar');

        Route::delete('categories/{id}/borrar', [CategoryController::class, 'borrarDefinitivo'])
            ->name('categories.borrarDefinitivo');
    });

    Route::middleware('permission:configure units')->group(function () {

        Route::get('units/eliminados', [UnitController::class, 'eliminadas'])
            ->name('units.eliminados');

        Route::resource('units', UnitController::class)
            ->parameters(['units' => 'unit'])
            ->names('units');

        Route::patch('units/{unit}/estado', [UnitController::class, 'toggleEstado'])
            ->name('units.toggle');

        Route::patch('units/{id}/restaurar', [UnitController::class, 'restaurar'])
            ->name('units.restaurar');

        Route::delete('units/{id}/borrar', [UnitController::class, 'borrarDefinitivo'])
            ->name('units.borrarDefinitivo');
    });

    Route::group([], function () {

        Route::get('/', [ProductController::class, 'index'])
            ->middleware('permission:view products')
            ->name('index');

        Route::get('/crear', [ProductController::class, 'create'])
            ->middleware('permission:create products')
            ->name('create');

        Route::post('/', [ProductController::class, 'store'])
            ->middleware('permission:create products')
            ->name('store');

        Route::get('/{product}/editar', [ProductController::class, 'edit'])
            ->middleware('permission:edit products')
            ->name('edit');

        Route::put('/{product}', [ProductController::class, 'update'])
            ->middleware('permission:edit products')
            ->name('update');

        Route::post('/bulk-action', [ProductController::class, 'bulk'])
            ->middleware('permission:edit products')
            ->name('bulk');

        Route::delete('/{product}', [ProductController::class, 'destroy'])
            ->middleware('permission:delete products')
            ->name('destroy');

        Route::get('/eliminados', [ProductController::class, 'eliminadas'])
            ->middleware('permission:restore products')
            ->name('eliminados');

        Route::patch('/{id}/restaurar', [ProductController::class, 'restaurar'])
            ->middleware('permission:restore products')
            ->name('restore');

        Route::delete('/{id}/forzar-eliminacion', [ProductController::class, 'borrarDefinitivo'])
            ->middleware('permission:delete products')
            ->name('borrarDefinitivo');
    });
});
