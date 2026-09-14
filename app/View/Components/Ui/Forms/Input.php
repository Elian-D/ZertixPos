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
        /** Texto plano no editable pegado al input (ej. ".zertixpos.com") — nunca un ícono ni un botón. Ver docs/ui/forms.md. */
        public ?string $addonLeft   = null,
        public ?string $addonRight  = null,
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

    public function hasAddon(): bool
    {
        return $this->addonLeft !== null || $this->addonRight !== null;
    }

    /**
     * Con addon, el borde/radio/fondo/ring de foco los lleva este wrapper
     * (no el `<input>`, ver `inputClasses()`) — patrón "grouped input"
     * estándar: un solo borde compartido entre el addon y el campo, en vez
     * de dos cajas bordeadas pegadas (que se verían como doble borde).
     * "bg-white" no va incondicional por el mismo motivo que en `inputClasses()`.
     */
    public function groupWrapperClasses(): string
    {
        // "relative" es necesario acá (y no lo tenía) — iconLeft/iconRight/el
        // ícono de error se posicionan "absolute" esperando que este wrapper
        // sea su ancestro posicionado; sin esto se posicionaban contra el
        // ancestro "relative" más cercano de la página entera (bug real,
        // reportado: el ícono del subdominio aparecía flotando en la
        // esquina superior izquierda del viewport, lejos del campo).
        $base = 'relative w-full flex items-stretch rounded-lg border transition-colors duration-200 '
              . 'focus-within:ring-1';

        if ($this->error) {
            return trim("{$base} border-state-error bg-state-error/5 focus-within:border-state-error focus-within:ring-state-error/20");
        }

        if ($this->disabled) {
            return trim("{$base} bg-slate-50 border-slate-100");
        }

        return trim("{$base} bg-white border-slate-200 focus-within:border-zertix-primary focus-within:ring-zertix-primary/20");
    }

    public function addonClasses(bool $right = false): string
    {
        $edge = $right ? 'rounded-r-lg border-l' : 'rounded-l-lg border-r';
        $border = $this->error ? 'border-state-error' : 'border-slate-200';

        return "flex items-center px-3 text-sm text-slate-500 bg-slate-50 {$edge} {$border}";
    }

    /**
     * Clases del <input> según estado — estilo "caja" (border+rounded-lg, no
     * underline como el original de Orvian), mismo radio de 8px que
     * x-ui.button/x-ui.badge para consistencia de todo el sistema.
     */
    public function inputClasses(): string
    {
        $pl = $this->iconLeft ? 'pl-10' : ($this->addonLeft ? 'pl-3' : '');
        $pr = ($this->iconRight || $this->error || $this->isPassword()) ? 'pr-10' : ($this->addonRight ? 'pr-3' : '');

        // Con addon, el wrapper (`groupWrapperClasses()`) ya puso borde/radio/fondo/ring
        // — el <input> queda "desnudo" adentro, sin competir por esos estilos.
        if ($this->hasAddon()) {
            $base = 'flex-1 min-w-0 bg-transparent border-0 px-3 py-2.5 text-sm '
                  . 'focus:outline-none focus:ring-0 placeholder-slate-400 '
                  . 'disabled:text-slate-400 disabled:cursor-not-allowed';

            return trim("{$base} {$pl} {$pr}" . ($this->error ? ' text-state-error' : ' text-slate-800'));
        }

        // "bg-white" NO va en $base: si conviviera con "bg-state-error/5" en el
        // branch de error, ambas clases compiten por el mismo background-color y
        // gana la que quede después en la hoja compilada, no la del HTML (mismo
        // bug ya resuelto en Badge/Button esta fase) — cada branch declara su
        // propio fondo completo.
        $base = 'w-full rounded-lg border px-3 py-2.5 text-sm transition-colors duration-200 '
              . 'focus:outline-none focus:ring-1 '
              . 'placeholder-slate-400 '
              . 'disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-100 disabled:cursor-not-allowed';

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
