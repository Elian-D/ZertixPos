<?php

namespace App\View\Components\Dashboard;

use Illuminate\View\Component;

class KpiCard extends Component
{
    public function __construct(
        public string $title,
        public string $value,
        public string $icon = 'cube',
        public string $color = 'primary',
        public ?string $secondaryText = null,
        public ?string $trend = null,
        public ?bool $trendUp = true,
        public ?string $href = null // <--- Nuevo parámetro
    ) {}

    public function render()
    {
        return view('components.dashboard.kpi-card');
    }

    // Mapeo de colores para Tailwind
    public function colorClasses(): string
    {
        return match($this->color) {
            'blue'   => 'border-blue-500 text-blue-600 bg-blue-50',
            'red'    => 'border-red-500 text-red-600 bg-red-50',
            'green'  => 'border-green-500 text-green-600 bg-green-50',
            'yellow' => 'border-yellow-500 text-yellow-600 bg-yellow-50',
            default  => 'border-zertix-primary-500 text-zertix-primary-600 bg-zertix-primary-50',
        };
    }
}