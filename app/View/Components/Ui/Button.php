<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string  $variant     = 'primary',
        public string  $appearance  = 'solid', // solid, outline, ghost — no se llama "type" para no colisionar con el atributo HTML type="submit"/"button" (ver docs/ui/buttons.md)
        public string  $size        = 'md',
        public ?string $iconLeft    = null,
        public ?string $iconRight   = null,
        public ?string $icon        = null,
        public ?string $hex         = null,    // Color hexadecimal arbitrario
        public ?string $href        = null,    // Si presente → renderiza <a>
        public bool    $disabled    = false,
        public bool    $fullWidth   = false,
        public bool    $hoverEffect = false,
        public ?string $disabledWhen = null, // expresión Alpine cruda (ej. "isSubmitDisabled") que se OR-ea con el guard de doble-submit — para botones type="submit" cuyo disabled depende de estado reactivo externo, no solo del propio envío
    ) {}

    // ── Tag dinámico ───────────────────────────────────────────────

    /**
     * El componente decide si renderiza <button> o <a>.
     * Si se pasa href se convierte en enlace, manteniendo el mismo aspecto.
     */
    public function tag(): string
    {
        return $this->href ? 'a' : 'button';
    }

    // ── Contraste hexadecimal ──────────────────────────────────────

    /**
     * Calcula si un color hexadecimal es "claro" u "oscuro"
     * usando la fórmula de luminancia relativa (W3C).
     * Devuelve true si el color es claro → el texto debe ser oscuro.
     */
    public function isLightHex(string $hex): bool
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Luminancia perceptual (YIQ)
        $yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;

        return $yiq >= 128;
    }

    /**
     * Devuelve los estilos inline para el modo hex.
     * solid  → fondo sólido con contraste automático
     * outline → fondo semitransparente con texto/borde del color dado
     */
    public function hexStyles(): string
    {
        if (!$this->hex) return '';

        $hex = $this->hex;

        if ($this->appearance === 'solid') {
            $textColor = $this->isLightHex($hex) ? '#1e293b' : '#ffffff';
            return "background-color: {$hex}; color: {$textColor}; border-color: transparent;";
        }

        // outline
        return "background-color: {$hex}1a; color: {$hex}; border-color: {$hex}33;";
    }

    // ── Clases CSS ─────────────────────────────────────────────────

    public function getButtonClasses(bool $isIconOnly): string
    {
        $base = 'inline-flex items-center justify-center font-semibold rounded-lg '
              . 'transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
              . 'disabled:opacity-60 disabled:cursor-not-allowed';

        $sizes = $isIconOnly ? [
            'sm' => 'w-7 h-7 text-xs',
            'md' => 'w-9 h-9 text-sm',
            'lg' => 'w-11 h-11 text-base',
            'xl' => 'w-14 h-14 text-lg',
        ] : [
            'sm' => 'px-2 py-1 text-xs gap-1.5',
            'md' => 'px-4 py-2 text-sm gap-2',
            'lg' => 'px-5 py-2.5 text-base gap-2.5',
            'xl' => 'px-10 py-5 text-lg gap-3',
        ];

        $variants = [
            'primary'   => [
                'solid'   => 'bg-zertix-primary text-white hover:opacity-90 focus:ring-zertix-primary/50',
                'outline' => 'border-2 border-zertix-primary text-zertix-primary hover:bg-zertix-primary/5 focus:ring-zertix-primary/50',
                'ghost'   => 'text-zertix-primary hover:bg-zertix-primary/10 focus:ring-zertix-primary/30',
            ],
            'secondary' => [
                'solid'   => 'bg-zertix-secondary text-white hover:opacity-90 focus:ring-zertix-secondary/50',
                'outline' => 'border-2 border-zertix-secondary text-zertix-secondary hover:bg-zertix-secondary/5 focus:ring-zertix-secondary/50',
                'ghost'   => 'text-zertix-secondary hover:bg-zertix-secondary/10 focus:ring-zertix-secondary/30',
            ],
            'success'   => [
                'solid'   => 'bg-state-success text-white hover:opacity-90 focus:ring-state-success/50',
                'outline' => 'border-2 border-state-success text-state-success bg-state-success/10 hover:bg-state-success/20',
                'ghost'   => 'text-state-success hover:bg-state-success/10 focus:ring-state-success/30',
            ],
            'warning'   => [
                'solid'   => 'bg-state-warning text-white hover:opacity-90 focus:ring-state-warning/50',
                'outline' => 'border-2 border-state-warning text-state-warning bg-state-warning/10 hover:bg-state-warning/20',
                'ghost'   => 'text-state-warning hover:bg-state-warning/10 focus:ring-state-warning/30',
            ],
            'info'      => [
                'solid'   => 'bg-state-info text-white hover:opacity-90 focus:ring-state-info/50',
                'outline' => 'border-2 border-state-info text-state-info bg-state-info/10 hover:bg-state-info/20',
                'ghost'   => 'text-state-info hover:bg-state-info/10 focus:ring-state-info/30',
            ],
            'error'     => [
                'solid'   => 'bg-state-error text-white hover:opacity-90 focus:ring-state-error/50',
                'outline' => 'border-2 border-state-error text-state-error bg-state-error/10 hover:bg-state-error/20',
                'ghost'   => 'text-state-error hover:bg-state-error/10 focus:ring-state-error/30',
            ],
            'link'      => [
                'solid'   => 'text-zertix-secondary hover:text-zertix-primary p-0',
                'outline' => 'text-zertix-secondary border-b border-zertix-secondary/30 hover:border-zertix-primary hover:text-zertix-primary p-0 rounded-none bg-transparent',
                'ghost'   => 'text-zertix-secondary hover:text-zertix-primary p-0',
            ],
        ];

        $classes = [$base, $sizes[$this->size] ?? $sizes['md']];

        // Si hay hex, las clases de variante se omiten — los estilos van inline
        if (!$this->hex) {
            $classes[] = $variants[$this->variant][$this->appearance]
                ?? $variants['primary']['solid'];
        }

        if ($this->fullWidth && !$isIconOnly) {
            $classes[] = 'w-full';
        }

        if ($this->hoverEffect && !$this->disabled) {
            $classes[] = 'hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zertix-primary/20';
        }

        return implode(' ', $classes);
    }

    public function render()
    {
        return view('components.ui.button');
    }
}
