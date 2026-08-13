<?php

use App\Http\Controllers\Accounting\DocumentTypeController;
use App\Http\Controllers\Configuration\ConfiguracionGeneralController;
use App\Http\Controllers\Configuration\DiaSemanaController;
use App\Http\Controllers\Configuration\TipoPagoController;
use Illuminate\Support\Facades\Route;

Route::prefix('config')->as('configuration.')->group(function () {

    Route::middleware('permission:configure general data')->group(function () {

        Route::get('general', [ConfiguracionGeneralController::class, 'edit'])
            ->name('general.edit');

        Route::put('general', [ConfiguracionGeneralController::class, 'update'])
            ->name('general.update');
    });

    Route::middleware('permission:configure dias-semana')->group(function () {

        Route::get('dias-semana', [DiaSemanaController::class, 'index'])
            ->name('dias.index');

        Route::patch('dias-semana/{diaSemana}/estado', [DiaSemanaController::class, 'toggleEstado'])
            ->name('dias.toggle');
    });

    // Catálogo del sistema completo (Facturas, Pagos, futura Nota de Crédito), no algo
    // específico de Contabilidad — movido desde routes/admin/accounting.php (REQ-10.3).
    // Vista/controlador se quedan donde están (App\Http\Controllers\Accounting\...,
    // resources/views/accounting/document_types/*); solo cambian ruta y nombre de ruta.
    Route::middleware(['auth'])->group(function () {

        Route::get('document-types/eliminados', [DocumentTypeController::class, 'eliminadas'])
            ->name('document_types.eliminados');

        Route::get('document-types', [DocumentTypeController::class, 'index'])
            ->middleware('permission:view document types')
            ->name('document_types.index');

        Route::get('document-types/create', [DocumentTypeController::class, 'create'])
            ->middleware('permission:create document types')
            ->name('document_types.create');

        Route::post('document-types', [DocumentTypeController::class, 'store'])
            ->middleware('permission:create document types')
            ->name('document_types.store');

        Route::get('document-types/{document_type}/edit', [DocumentTypeController::class, 'edit'])
            ->middleware('permission:edit document types')
            ->name('document_types.edit');

        Route::put('document-types/{document_type}', [DocumentTypeController::class, 'update'])
            ->middleware('permission:edit document types')
            ->name('document_types.update');

        Route::delete('document-types/{document_type}', [DocumentTypeController::class, 'destroy'])
            ->middleware('permission:delete document types')
            ->name('document_types.destroy');

        Route::patch('document-types/{id}/restaurar', [DocumentTypeController::class, 'restaurar'])
            ->name('document_types.restaurar');

        Route::delete('document-types/{id}/borrar', [DocumentTypeController::class, 'borrarDefinitivo'])
            ->name('document_types.borrarDefinitivo');
    });

    Route::middleware('permission:configure payments')->group(function () {

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
    Route::middleware('permission:configure system modules')
        ->get('features', fn () => view('configuration.features'))
        ->name('features');
});
