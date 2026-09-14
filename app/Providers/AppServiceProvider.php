<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Contracts\Sales\NcfGeneratorInterface;
use App\Services\Sales\Ncf\LocalNcfGenerator;
use App\Contracts\Billing\PaymentGatewayContract;
use App\Services\Billing\PayPalGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NcfGeneratorInterface::class, LocalNcfGenerator::class);
        $this->app->bind(PaymentGatewayContract::class, PayPalGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sistema de paginación propio (REQ-0.1) — reemplaza el Tailwind
        // por defecto de Laravel. Usado tanto por Livewire (DataTable::paginationView())
        // como por paginación de controladores normales fuera de Livewire.
        Paginator::defaultView('pagination.zertix-compact');
        Paginator::defaultSimpleView('pagination.zertix-compact');

        // REQ-4.1, v1.3.0 Fase 4 — ver config/livewire.php:
        // temporary_file_upload.middleware. Por IP, nunca por Auth::user()
        // (esa ruta corre igual en central sin tenant que dentro de uno).
        RateLimiter::for('livewire-uploads', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

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