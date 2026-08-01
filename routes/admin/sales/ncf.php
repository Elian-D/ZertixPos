<?php

use App\Http\Controllers\Sales\Ncf\NcfSequenceController;
use App\Http\Controllers\Sales\Ncf\NcfLogController;
use App\Http\Controllers\Sales\Ncf\NcfTypeController;
use App\Models\Configuration\ConfiguracionGeneral; // Importante
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:manage ncf sequences'])->group(function () {
    
    // En lugar de un middleware Closure, validamos antes de definir el grupo
    // Si la configuración fiscal está apagada, estas rutas ni siquiera se registran 
    // o puedes envolverlas en una condición:
    
    if (general_config()?->usa_ncf) {
        
        Route::prefix('ncf')->name('ncf.')->group(function () {
            
            // Dashboard y otras rutas
            Route::get('/dashboard', function() { /* tu controller */ })->name('dashboard');

            Route::prefix('sequences')->name('sequences.')->group(function () {
                Route::get('/', [NcfSequenceController::class, 'index'])->name('index');
                Route::post('/', [NcfSequenceController::class, 'store'])->name('store');
                Route::delete('/{sequence}', [NcfSequenceController::class, 'destroy'])->name('destroy');
                Route::patch('/{sequence}/threshold', [NcfSequenceController::class, 'updateThreshold'])->name('update-threshold');
                Route::patch('/{sequence}/extend', [NcfSequenceController::class, 'extend'])->name('extend');
            });

            Route::group(['prefix' => 'logs', 'as' => 'logs.'], function () {
                Route::get('/', [NcfLogController::class, 'index'])->name('index');
                Route::get('/export/excel', [NcfLogController::class, 'exportExcel'])->name('export.excel');
                Route::get('/export/txt', [NcfLogController::class, 'exportTxt'])->name('export.txt');
            });

            Route::group(['prefix' => 'types', 'as' => 'types.'], function () {
                Route::get('/', [NcfTypeController::class, 'index'])->name('index');
                Route::post('/', [NcfTypeController::class, 'store'])->name('store');
                Route::put('/{ncfType}', [NcfTypeController::class, 'update'])->name('update');
            });
        });
    } else {
        // Opcional: Ruta fallback si intentan entrar y está desactivado
        Route::any('ncf/{any?}', function () {
            return redirect('/admin/config')->with('error', 'La gestión fiscal está desactivada en la configuración general.');
        })->where('any', '.*');
    }
});