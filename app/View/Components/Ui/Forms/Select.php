<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Select extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $placeholder = 'Seleccionar...',
        public ?string $iconLeft    = null,
        public ?string $error       = null,
        public ?string $hint        = null,
        public bool    $required    = false,
        public bool    $disabled    = false,
    ) {
        $this->id = $id ?: $name;
    }

    public function selectClasses(): string
    {
        // "bg-white" NO va en $base — mismo motivo que en Input.php (evita que
        // compita con "bg-state-error/5" en el branch de error).
        // "bg-none" es necesario porque @tailwindcss/forms (estrategia "base") ya
        // inyecta su propia flecha vía background-image en TODO <select> del
        // sistema — sin esto se ven dos flechas superpuestas (la del plugin +
        // el heroicon-chevron-down de este componente).
        $base = 'w-full rounded-lg border px-3 py-2.5 text-sm '
              . 'appearance-none bg-none cursor-pointer transition-colors duration-200 '
              . 'focus:outline-none focus:ring-1 pr-10 '
              . 'disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-100 disabled:cursor-not-allowed';

        $pl = $this->iconLeft ? 'pl-10' : '';

        if ($this->error) {
            return trim("{$base} {$pl} border-state-error bg-state-error/5 text-state-error focus:border-state-error focus:ring-state-error/20");
        }

        return trim("{$base} {$pl} bg-white border-slate-200 text-slate-800 focus:border-zertix-primary focus:ring-zertix-primary/20 [&>option]:text-slate-800");
    }

    public function iconWrapClasses(bool $right = false): string
    {
        $side = $right ? 'right-3' : 'left-3';
        return "absolute {$side} top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors duration-200";
    }

    public function iconColorClasses(): string
    {
        return $this->error
            ? 'text-state-error'
            : 'text-slate-400 group-focus-within:text-zertix-primary';
    }

    public function render()
    {
        return view('components.ui.forms.select');
    }
}
