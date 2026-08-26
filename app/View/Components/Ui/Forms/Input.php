<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Input extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $type        = 'text',
        public string  $placeholder = '',
        public ?string $iconLeft    = null,
        public ?string $iconRight   = null,
        public ?string $error       = null,
        public ?string $hint        = null,
        public bool    $required    = false,
        public bool    $disabled    = false,
        public bool    $readonly    = false,
    ) {
        $this->id = $id ?: $name;
    }

    /**
     * REQ-7.11: todo `type="password"` renderizado por este componente trae el
     * toggle mostrar/ocultar de fábrica — no es un prop opt-in porque no hay
     * ningún caso real en el sistema donde un campo de contraseña deba
     * ocultar la opción de revelarla. El icono derecho estático (`iconRight`)
     * se ignora en este caso: el slot derecho lo ocupa el botón del toggle.
     */
    public function isPassword(): bool
    {
        return $this->type === 'password';
    }

    /**
     * Clases del <input> según estado — estilo "caja" (border+rounded-lg, no
     * underline como el original de Orvian), mismo radio de 8px que
     * x-ui.button/x-ui.badge para consistencia de todo el sistema.
     */
    public function inputClasses(): string
    {
        // "bg-white" NO va en $base: si conviviera con "bg-state-error/5" en el
        // branch de error, ambas clases compiten por el mismo background-color y
        // gana la que quede después en la hoja compilada, no la del HTML (mismo
        // bug ya resuelto en Badge/Button esta fase) — cada branch declara su
        // propio fondo completo.
        $base = 'w-full rounded-lg border px-3 py-2.5 text-sm transition-colors duration-200 '
              . 'focus:outline-none focus:ring-1 '
              . 'placeholder-slate-400 '
              . 'disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-100 disabled:cursor-not-allowed';

        $pl = $this->iconLeft ? 'pl-10' : '';
        $pr = ($this->iconRight || $this->error || $this->isPassword()) ? 'pr-10' : '';

        if ($this->error) {
            return trim("{$base} {$pl} {$pr} border-state-error bg-state-error/5 text-state-error focus:border-state-error focus:ring-state-error/20");
        }

        return trim("{$base} {$pl} {$pr} bg-white border-slate-200 text-slate-800 focus:border-zertix-primary focus:ring-zertix-primary/20");
    }

    /**
     * Clases base para los wrappers de iconos.
     */
    public function iconWrapClasses(bool $right = false): string
    {
        $side = $right ? 'right-3' : 'left-3';
        return "absolute {$side} top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors duration-200";
    }

    /**
     * Clases de color para los iconos según estado.
     */
    public function iconColorClasses(): string
    {
        return $this->error
            ? 'text-state-error'
            : 'text-slate-400 group-focus-within:text-zertix-primary';
    }

    public function render()
    {
        return view('components.ui.forms.input');
    }
}
