<?php

use App\Http\Controllers\Sales\QuoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('quotes')->name('quotes.')->group(function () {
    
    // Listado principal (DataTables / AJAX)
    Route::get('/', [QuoteController::class, 'index'])
        ->middleware('permission:view quotes')
        ->name('index');
        
    // Creación (Livewire Builder)
    Route::get('/create', [QuoteController::class, 'create'])
        ->middleware('permission:create quotes')
        ->name('create');

    Route::post('/', [QuoteController::class, 'store'])
        ->middleware('permission:create quotes')
        ->name('store');

    // Preview (Vista previa en iframe)
    Route::get('/{quote}/preview', [QuoteController::class, 'preview'])
        ->middleware('permission:view quotes')
        ->name('preview');

    // Detalle (Vista previa antes de imprimir o convertir)
    Route::get('/{quote}', [QuoteController::class, 'show'])
        ->middleware('permission:view quotes')
        ->name('show');

    // Edición (Solo para estado Borrador)
    Route::get('/{quote}/edit', [QuoteController::class, 'edit'])
        ->middleware('permission:edit quotes')
        ->name('edit');

    Route::put('/{quote}', [QuoteController::class, 'update'])
        ->middleware('permission:edit quotes')
        ->name('update');

    // --- ACCIONES DE NEGOCIO (Cambios de estado) ---
    
    // Marcar como aprobada (lista para el cliente)
    Route::patch('/{quote}/approve', [QuoteController::class, 'approve'])
        ->middleware('permission:convert quotes')
        ->name('approve');

    // Cancelar (por error o decisión del cliente)
    Route::patch('/{quote}/cancel', [QuoteController::class, 'cancel'])
        ->middleware('permission:cancel quotes')
        ->name('cancel');

    // Convertir en venta final
    Route::post('/{quote}/convert', [QuoteController::class, 'convert'])
        ->middleware('permission:convert quotes')
        ->name('convert');

    // --- IMPRESIÓN ---
    
    Route::get('/{quote}/print/{format?}', [QuoteController::class, 'print'])
        ->middleware('permission:view quotes')
        ->name('print');
});