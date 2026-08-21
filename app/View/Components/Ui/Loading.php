<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Loading extends Component
{
    public string $sizeClass;

    public function __construct(
        public string $size = 'md',
    ) {
        $this->sizeClass = $this->getSizeClass($size);
    }

    private function getSizeClass(string $size): string
    {
        return match ($size) {
            'xs'    => 'h-3 w-3 border-2',
            'sm'    => 'h-4 w-4 border-2',
            'lg'    => 'h-8 w-8 border-3',
            'xl'    => 'h-12 w-12 border-4',
            default => 'h-6 w-6 border-2', // md
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.loading');
    }
}
