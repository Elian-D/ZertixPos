<?php

namespace App\View\Components\Ui\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FileInput extends Component
{
    public function __construct(
        public string  $label     = '',
        public string  $name      = '',
        public ?string $id        = null,
        public ?string $iconLeft  = 'heroicon-o-cloud-arrow-up',
        public ?string $error     = null,
        public ?string $hint      = null,
        public bool    $required  = false,
        public bool    $disabled  = false,
        public string  $accept    = '*',
        public bool    $multiple  = false,
    ) {
        $this->id = $id ?? $name;
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.forms.file-input');
    }
}
