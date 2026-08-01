<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Contracts\Sales\NcfGeneratorInterface;
use App\Services\Sales\Ncf\LocalNcfGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NcfGeneratorInterface::class, LocalNcfGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();
        \App\Models\Accounting\Receivable::observe(\App\Observers\ReceivableObserver::class);

        
        // Solo checkear si estamos en el panel administrativo o POS
        if (app()->runningInConsole()) return;

        view()->composer('admin.pos.*', function ($view) {
            $settings = \App\Models\Sales\Pos\PosSetting::getSettings();
            if (!$settings->default_walkin_customer_id) {
                session()->now('warning', 'El POS no tiene un cliente por defecto configurado.');
            }
        });

    }

    
}