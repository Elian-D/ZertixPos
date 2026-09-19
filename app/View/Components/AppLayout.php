<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * REQ-7.8 — título de página del `<head>` (`<title>`), distinto del
     * `<h1>` visible que arma `x-ui.page-header :title="..."` dentro del
     * contenido. Null por defecto: las vistas que todavía no lo pasan (fuera
     * del alcance de este barrido) caen al nombre de la app solo, sin romper.
     */
    public function __construct(
        public ?string $title = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('components.app-layout');
    }
}
