<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    public function render(): View|Closure|string
    {
        return view('components.ui.breadcrumbs');
    }
}
