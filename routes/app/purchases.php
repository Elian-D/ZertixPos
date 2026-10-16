<?php

use App\Http\Controllers\Purchases\PurchaseOrderController;
use App\Http\Controllers\Purchases\PurchaseReceptionController;
use App\Http\Controllers\Purchases\VendorController;
use App\Http\Controllers\Sales\Ncf\RncLookupController;
use Illuminate\Support\Facades\Route;

// Compras (v1.5.0 Fases 4 y 5): núcleo flexible `purchases`, encendido en todo plan (REQ-4.5).
// Proveedores, órdenes de compra y recepciones.
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

    // Órdenes de compra (REQ-5.1). No se borran: se cancelan (el número ya se emitió).
    Route::prefix('orders')->as('orders.')->controller(PurchaseOrderController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:purchase_orders.view')->name('index');
        Route::get('/create', 'create')->middleware('permission:purchase_orders.create')->name('create');
        Route::post('/', 'store')->middleware('permission:purchase_orders.create')->name('store');
        Route::get('/{order}', 'show')->whereNumber('order')->middleware('permission:purchase_orders.view')->name('show');
        Route::get('/{order}/edit', 'edit')->whereNumber('order')->middleware('permission:purchase_orders.create')->name('edit');
        Route::put('/{order}', 'update')->whereNumber('order')->middleware('permission:purchase_orders.create')->name('update');
        Route::patch('/{order}/approve', 'approve')->whereNumber('order')->middleware('permission:purchase_orders.approve')->name('approve');
        Route::patch('/{order}/sent', 'markSent')->whereNumber('order')->middleware('permission:purchase_orders.create')->name('sent');
        Route::patch('/{order}/cancel', 'cancel')->whereNumber('order')->middleware('permission:purchase_orders.cancel')->name('cancel');
        Route::get('/{order}/pdf', 'pdf')->whereNumber('order')->middleware('permission:purchase_orders.view')->name('pdf');
    });

    // Recepciones (REQ-5.2): se reciben desde la orden; no se editan ni se anulan.
    Route::controller(PurchaseReceptionController::class)->group(function () {
        Route::get('/orders/{order}/receive', 'create')->whereNumber('order')->middleware('permission:purchase_receptions.create')->name('orders.receive.form');
        Route::post('/orders/{order}/receive', 'store')->whereNumber('order')->middleware('permission:purchase_receptions.create')->name('orders.receive');

        Route::prefix('receptions')->as('receptions.')->group(function () {
            Route::get('/', 'index')->middleware('permission:purchase_orders.view')->name('index');
            Route::get('/{reception}', 'show')->whereNumber('reception')->middleware('permission:purchase_orders.view')->name('show');
            Route::get('/{reception}/pdf', 'pdf')->whereNumber('reception')->middleware('permission:purchase_orders.view')->name('pdf');
        });
    });

    // Lookup DGII (mismo proxy que el TPV): autollena la razón social desde el RNC.
    Route::get('/rnc-lookup', [RncLookupController::class, 'lookup'])
        ->middleware(['permission:vendors.create|vendors.edit', 'throttle:20,1'])
        ->name('rnc-lookup');
});
