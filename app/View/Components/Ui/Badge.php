<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class Badge extends Component
{
    public function __construct(
        public string $variant = 'info',  // primary, success, warning, error, info, slate
        public bool $dot = true,          // Mostrar el punto indicador (ignorado si se pasa $icon)
        public string $size = 'md',       // sm, md
        public ?string $hex = null,       // Color hexadecimal personalizado (ej. #FF5733)
        public ?string $icon = null,      // Heroicon a mostrar en vez del punto (ej. heroicon-s-check-circle)
    ) {}

    public function getBadgeClasses(): string
    {
        // Si hay color hex, solo retornar clases base sin colores
        if ($this->hex) {
            return $this->getBaseClasses();
        }

        $base = $this->getBaseClasses();
        $variants = $this->getVariantClasses();

        return implode(' ', [$base, $variants[$this->variant] ?? $variants['info']]);
    }

    private function getBaseClasses(): string
    {
        $base = "inline-flex items-center font-semibold rounded-full border transition-colors duration-200";

        $sizes = [
            'sm' => 'px-2.5 py-0.5 text-[9px] gap-1.5',
            'md' => 'px-4 py-1.5 text-xs gap-2',
        ];

        return implode(' ', [$base, $sizes[$this->size]]);
    }

    private function getVariantClasses(): array
    {
        return [
            'primary' => 'bg-zertix-primary/10 dark:bg-zertix-primary/20 text-zertix-primary border-zertix-primary',
            'success' => 'bg-state-success/10 dark:bg-state-success/20 text-state-success border-state-success',
            'warning' => 'bg-state-warning/10 dark:bg-state-warning/20 text-state-warning border-state-warning',
            'error'   => 'bg-state-error/10 dark:bg-state-error/20 text-state-error border-state-error',
            'info'    => 'bg-state-info/10 dark:bg-state-info/20 text-state-info border-state-info',
            'slate'   => 'bg-slate-500/10 dark:bg-slate-500/20 text-slate-600 dark:text-slate-400 border-slate-500',
        ];
    }

    public function getDotClasses(): string
    {
        // Si hay color hex, no usar clases (se aplicará inline)
        if ($this->hex) {
            return "w-2 h-2 rounded-full";
        }

        $dots = [
            'primary' => 'bg-zertix-primary',
            'success' => 'bg-state-success',
            'warning' => 'bg-state-warning',
            'error'   => 'bg-state-error',
            'info'    => 'bg-state-info',
            'slate'   => 'bg-slate-500',
        ];

        return "w-2 h-2 rounded-full " . ($dots[$this->variant] ?? $dots['info']);
    }

    /**
     * Clases del ícono indicador (alternativa a $dot) — mismo color que el punto,
     * tamaño ajustado al $size del badge.
     */
    public function getIconClasses(): string
    {
        $sizes = [
            'sm' => 'w-3 h-3',
            'md' => 'w-3.5 h-3.5',
        ];
        $size = $sizes[$this->size] ?? $sizes['md'];

        if ($this->hex) {
            return $size;
        }

        $colors = [
            'primary' => 'text-zertix-primary',
            'success' => 'text-state-success',
            'warning' => 'text-state-warning',
            'error'   => 'text-state-error',
            'info'    => 'text-state-info',
            'slate'   => 'text-slate-600 dark:text-slate-400',
        ];

        return $size . ' ' . ($colors[$this->variant] ?? $colors['info']);
    }

    public function getCustomStyles(): string
    {
        if (!$this->hex) {
            return '';
        }

        // Normalizar el hex (asegurar que tenga #)
        $hex = str_starts_with($this->hex, '#') ? $this->hex : '#' . $this->hex;

        // Generar estilos inline con opacidades semánticas — borde sólido (sin
        // sufijo de opacidad), igual que las variantes fijas, para que el badge
        // defina bien su silueta en vez de un borde translúcido lavado.
        return sprintf(
            'background-color: %s1a; color: %s; border-color: %s;',
            $hex,
            $hex,
            $hex
        );
    }

    public function getDotStyles(): string
    {
        if (!$this->hex) {
            return '';
        }

        $hex = str_starts_with($this->hex, '#') ? $this->hex : '#' . $this->hex;
        return sprintf('background-color: %s;', $hex);
    }

    public function getIconStyles(): string
    {
        if (!$this->hex) {
            return '';
        }

        $hex = str_starts_with($this->hex, '#') ? $this->hex : '#' . $this->hex;
        return sprintf('color: %s;', $hex);
    }

    public function render()
    {
        return view('components.ui.badge');
    }
}
