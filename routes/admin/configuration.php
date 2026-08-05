<?php

use App\Http\Controllers\Configuration\ConfiguracionGeneralController;
use App\Http\Controllers\Configuration\DiaSemanaController;
use App\Http\Controllers\Configuration\EstadosClienteController;
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

    Route::middleware('permission:configure client-states')->group(function () {

        Route::get('estados-clientes/eliminados', [EstadosClienteController::class, 'eliminadas'])
            ->name('estados.eliminados');

        Route::resource('estados-clientes', EstadosClienteController::class)
            ->parameters(['estados-clientes' => 'estado'])
            ->names('estados');

        Route::patch('estados-clientes/{estado}/estado', [EstadosClienteController::class, 'toggleEstado'])
            ->name('estados.toggle');

        Route::patch('estados-clientes/{id}/restaurar', [EstadosClienteController::class, 'restaurar'])
            ->name('estados.restaurar');

        Route::delete('estados-clientes/{id}/borrar', [EstadosClienteController::class, 'borrarDefinitivo'])
            ->name('estados.borrarDefinitivo');
    });

    Route::middleware('permission:view configuration')
        ->get('/', fn () => view('configuration.index'))
        ->name('index');

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
});
