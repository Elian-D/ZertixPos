<?php

use App\Http\Controllers\Sales\Ncf\NcfDashboardController;
use App\Http\Controllers\Sales\SalesDashboardController;
use Illuminate\Support\Facades\Route;

// routes/admin/SalesRoutesConfig.php
Route::prefix('sales')->as('sales.')->group(function () {
    
    require __DIR__ . '/sales/sales.php';
    require __DIR__ . '/sales/invoices.php';
    require __DIR__ . '/sales/ncf.php';
    require __DIR__ . '/sales/pos.php';
    require __DIR__ . '/sales/quotes.php';

    Route::get('/dashboard', SalesDashboardController::class)->name('dashboard');
    Route::get('/ncf/dashboard', NcfDashboardController::class)->name('ncf.dashboard');
});