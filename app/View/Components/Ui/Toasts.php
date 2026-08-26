<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Toasts extends Component
{
    /**
     * @param bool $suppressValidationToast Omite el toast automático de $errors->any() —
     *   úsalo en vistas donde x-ui.forms.* ya muestra el error inline bajo cada campo
     *   (Fase 7.5), para no duplicar el mismo mensaje en dos lugares.
     */
    public function __construct(
        public bool $suppressValidationToast = false,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.ui.toasts');
    }
}
