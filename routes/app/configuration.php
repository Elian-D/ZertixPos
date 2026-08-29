<?php

use App\Http\Controllers\Accounting\DocumentTypeController;
use App\Http\Controllers\Configuration\CatalogsController;
use App\Http\Controllers\Configuration\ConfiguracionGeneralController;
use App\Http\Controllers\Configuration\TipoPagoController;
use Illuminate\Support\Facades\Route;

Route::prefix('config')->as('configuration.')->group(function () {

    Route::middleware('permission:config.general')->group(function () {

        Route::get('general', [ConfiguracionGeneralController::class, 'edit'])
            ->name('general.edit');

        Route::put('general', [ConfiguracionGeneralController::class, 'update'])
            ->name('general.update');

        Route::get('catalogs', [CatalogsController::class, 'index'])
            ->name('catalogs.index');
    });

    // Catálogo fijo de 2 filas (FAC/PAG) que el sistema sembró y sabe usar — sin
    // create/destroy/papelera, lo único legítimo es ajustar el correlativo (REQ-1.7).
    Route::middleware(['auth'])->group(function () {

        Route::get('document-types', [DocumentTypeController::class, 'index'])
            ->middleware('permission:document_types.view')
            ->name('document_types.index');

        Route::get('document-types/{document_type}/edit', [DocumentTypeController::class, 'edit'])
            ->middleware('permission:document_types.edit')
            ->name('document_types.edit');

        Route::put('document-types/{document_type}', [DocumentTypeController::class, 'update'])
            ->middleware('permission:document_types.edit')
            ->name('document_types.update');
    });

    Route::middleware('permission:config.payment_types')->group(function () {

        Route::get('tipo-pagos/eliminados', [TipoPagoController::class, 'eliminadas'])
            ->name('pagos.eliminados');

        Route::resource('tipo-pagos', TipoPagoController::class)
            ->parameters(['tipo-pagos' => 'tipoPago'])
            ->names('pagos');

        Route::patch('tipo-pagos/{tipoPago}/estado', [TipoPagoController::class, 'toggleEstado'])
            ->name('pagos.toggle');

        Route::patch('tipo-pagos/{id}/restaurar', [TipoPagoController::class, 'restaurar'])
            ->name('pagos.restaurar');

        Route::delete('tipo-pagos/{id}/borrar', [TipoPagoController::class, 'borrarDefinitivo'])
            ->name('pagos.borrarDefinitivo');
    });

    // Quinta pantalla de Configuración (REQ-10.6) — activar/desactivar módulos
    // satélite/flexibles. Vista delgada + componente Livewire, mismo patrón que
    // sales.quotes.create (resources/views/sales/quotes/create.blade.php).
    Route::middleware('permission:config.modules')
        ->get('features', fn () => view('configuration.features'))
        ->name('features');
});
