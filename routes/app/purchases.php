<?php

use App\Http\Controllers\Purchases\VendorController;
use App\Http\Controllers\Sales\Ncf\RncLookupController;
use Illuminate\Support\Facades\Route;

// Compras (v1.5.0 Fase 4): núcleo flexible `purchases`, encendido en todo plan (REQ-4.5).
// Fase 4: proveedores. La Fase 5 suma órdenes de compra y recepciones a este mismo grupo.
Route::prefix('purchases')->as('purchases.')->middleware(['auth', 'module:purchases'])->group(function () {

    // Restaurar y borrar definitivo viven en VendorTable (papelera como pestaña,
    // docs/analisis/politica-soft-deletes.md §6).
    Route::prefix('vendors')->as('vendors.')->controller(VendorController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:vendors.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:vendors.create')->name('create');
        Route::post('/', 'store')->middleware('permission:vendors.create')->name('store');
        Route::get('/{vendor}', 'show')->whereNumber('vendor')->middleware('permission:vendors.view')->name('show');
        Route::get('/{vendor}/edit', 'edit')->whereNumber('vendor')->middleware('permission:vendors.edit')->name('edit');
        Route::put('/{vendor}', 'update')->whereNumber('vendor')->middleware('permission:vendors.edit')->name('update');
        Route::delete('/{vendor}', 'destroy')->whereNumber('vendor')->middleware('permission:vendors.delete')->name('destroy');
    });

    // Lookup DGII (mismo proxy que el TPV): autollena la razón social desde el RNC.
    Route::get('/rnc-lookup', [RncLookupController::class, 'lookup'])
        ->middleware(['permission:vendors.create|vendors.edit', 'throttle:20,1'])
        ->name('rnc-lookup');
});
