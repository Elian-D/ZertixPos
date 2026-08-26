<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Radio extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $value       = '',
        public bool    $checked     = false,
        public ?string $description = null,
        public bool    $disabled    = false,
    ) {
        $this->id = $id ?: ($name . '_' . $value);
    }

    public function render()
    {
        return view('components.ui.forms.radio');
    }
}
