<?php

use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->as('catalog.')->group(function () {
    Route::get('/states', [CatalogController::class, 'states'])->name('states');
    Route::get('/tax-types', [CatalogController::class, 'taxTypes'])->name('tax-types');
    Route::get('/client-status', [CatalogController::class, 'clientStatus'])->name('client-status');
});
