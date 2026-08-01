<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class ToastItem extends Component
{
    public string $borderColor;
    public string $progressColor;
    public string $iconColor;
    public string $lightBgColor;
    public string $icon;

    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public int $duration = 4500
    ) {
        $this->configureAppearance();
    }

    protected function configureAppearance(): void
    {
        switch ($this->type) {
            case 'success':
                $this->borderColor = 'border-emerald-500';
                $this->progressColor = 'bg-emerald-500';
                $this->iconColor = 'text-emerald-600';
                $this->lightBgColor = 'bg-emerald-50';
                $this->icon = 'heroicon-s-check-circle';
                break;
            case 'error':
                $this->borderColor = 'border-red-500';
                $this->progressColor = 'bg-red-500';
                $this->iconColor = 'text-red-600';
                $this->lightBgColor = 'bg-red-50';
                $this->icon = 'heroicon-s-exclamation-triangle';
                break;
            case 'info':
                $this->borderColor = 'border-blue-500';
                $this->progressColor = 'bg-blue-500';
                $this->iconColor = 'text-blue-600';
                $this->lightBgColor = 'bg-blue-50';
                $this->icon = 'heroicon-s-information-circle';
                break;
            default:
                $this->borderColor = 'border-gray-500';
                $this->progressColor = 'bg-gray-500';
                $this->iconColor = 'text-gray-600';
                $this->lightBgColor = 'bg-gray-50';
                $this->icon = 'heroicon-s-bell';
        }
    }

    public function render()
    {
        return view('components.ui.toast-item');
    }
}