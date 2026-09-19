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
        /** Opt-in — miniatura del archivo elegido (si es imagen) a la izquierda de la caja, reemplazando iconLeft. Ver docs/ui/forms.md. */
        public bool    $preview   = false,
        /**
         * Opt-in — variante "zona de arrastre" cuadrada, ícono+texto centrados
         * (logo/imagen de perfil), en vez de la caja horizontal tipo campo de
         * texto (`fileName`/`iconLeft` a la izquierda). Con `preview`, el
         * archivo elegido llena el cuadro entero en vez de una miniatura
         * chica. No cambia el look de ningún file-input existente — sigue
         * siendo `false` por defecto. Ver docs/ui/forms.md.
         */
        public bool    $dropzone  = false,
        /**
         * Tamaño de la caja `dropzone` — mismo nombre de escala que
         * `x-ui.button` (`sm`/`md`/`lg`/`xl`, más `xs`). Ignorado si
         * `dropzone` es `false`. Ver `dropzoneSizeClasses()`.
         */
        public string  $size      = 'md',
    ) {
        $this->id = $id ?? $name;
    }

    /**
     * Ancho y alto iguales (cuadrado real, no un `h-*` con `w-full` que
     * termina rectangular según el ancho del contenedor — bug real
     * reportado con el logo del Wizard). `object-cover` en la miniatura
     * (ver file-input.blade.php) hace que la imagen se recorte para llenar
     * el cuadro sin deformarse, sin importar su proporción original.
     */
    public function dropzoneSizeClasses(): string
    {
        return match ($this->size) {
            'xs' => 'w-16 h-16',
            'sm' => 'w-20 h-20',
            'lg' => 'w-36 h-36',
            'xl' => 'w-44 h-44',
            default => 'w-28 h-28', // 'md'
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.forms.file-input');
    }
}
