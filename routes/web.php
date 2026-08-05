<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'permission:view dashboard'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin/geo.php';

// Rutas administrativas (panel) — antes vivía en RouteServiceProvider::boot()
Route::middleware(['web', 'auth'])
    ->prefix('admin')
    ->group(function () {
        foreach (glob(base_path('routes/admin/*.php')) as $routeFile) {
            require $routeFile;
        }
    });

