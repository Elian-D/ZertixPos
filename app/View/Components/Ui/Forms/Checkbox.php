<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Checkbox extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $value       = '1',
        public bool    $checked     = false,
        public ?string $description = null,
        public ?string $error       = null,
        public bool    $disabled    = false,
    ) {
        $this->id = $id ?: $name;
    }

    public function render()
    {
        return view('components.ui.forms.checkbox');
    }
}
