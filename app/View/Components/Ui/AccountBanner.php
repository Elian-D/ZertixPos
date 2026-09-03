<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * REQ-3.10, v1.3.0 Fase 3 — sin props: calcula su propio estado leyendo
 * `tenant()`/`Subscription` (mismo patrón que `x-ui.breadcrumbs`, que lee
 * `request()` en vez de recibir props). La lógica vive en el Blade
 * (`components/ui/account-banner.blade.php`), esta clase solo resuelve la vista.
 */
class AccountBanner extends Component
{
    public function render(): View|Closure|string
    {
        return view('components.ui.account-banner');
    }
}
