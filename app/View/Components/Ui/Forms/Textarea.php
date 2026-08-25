<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Textarea extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $placeholder = '',
        public int     $rows        = 3,
        public ?string $error       = null,
        public ?string $hint        = null,
        public bool    $required    = false,
        public bool    $disabled    = false,
        public bool    $readonly    = false,
        public bool    $resize      = false,
    ) {
        $this->id = $id ?: $name;
    }

    public function textareaClasses(): string
    {
        $resize = $this->resize ? 'resize-y' : 'resize-none';

        // "bg-white" NO va en $base — mismo motivo que en Input.php (evita que
        // compita con "bg-state-error/5" en el branch de error).
        $base = "w-full rounded-lg border px-3 py-2.5 text-sm "
              . "{$resize} transition-colors duration-200 focus:outline-none focus:ring-1 "
              . "placeholder-slate-400 "
              . "disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-100 disabled:cursor-not-allowed";

        if ($this->error) {
            return "{$base} border-state-error bg-state-error/5 text-state-error focus:border-state-error focus:ring-state-error/20";
        }

        return "{$base} bg-white border-slate-200 text-slate-800 focus:border-zertix-primary focus:ring-zertix-primary/20";
    }

    public function render()
    {
        return view('components.ui.forms.textarea');
    }
}
