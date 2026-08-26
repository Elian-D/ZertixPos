<?php

use App\Http\Controllers\Sales\QuoteController;
use Illuminate\Support\Facades\Route;

// Extraído de sales.php (REQ-3.3), luego movido de sales.quotes.*→clients.quotes.*
// (Fase 7.9, sidebar) — Cotizaciones vive bajo el dropdown "CRM" en el sidebar,
// pero su URL/nombre de ruta seguía bajo "sales", lo que hacía que el comodín
// app/sales* del dropdown "Ventas" también la atrapara (dos grupos resaltados
// a la vez). El flag del módulo (module:sales.quotes) NO cambia de nombre — es
// un feature flag de config/modules.php, un namespace totalmente distinto al
// de rutas/sidebar, coincide de nombre por casualidad.
//
// Cotizaciones es núcleo flexible (REQ-10.4/10.8) — encendido por defecto, pero un
// negocio de venta directa que nunca cotiza puede apagarlo. Con el flag apagado,
// todo el grupo devuelve 404 — incluye approve/cancel/convert sobre cotizaciones
// ya existentes, no solo la creación de nuevas (mismo criterio "se congela" de
// REQ-10.5: si el módulo está pausado, tampoco se debería seguir operando sobre
// lo que ya existe).
Route::middleware(['auth', 'module:sales.quotes'])->prefix('clients/quotes')->as('clients.quotes.')->group(function () {

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
