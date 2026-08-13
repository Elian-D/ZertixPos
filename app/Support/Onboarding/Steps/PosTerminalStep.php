<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Sales\Pos\PosTerminal;
use App\Support\Onboarding\OnboardingStepInterface;

class PosTerminalStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'pos_terminal';
    }

    public function title(): string
    {
        return 'Primer Punto de Venta (TPV)';
    }

    public function description(): string
    {
        return 'El corazón del sistema — crea tu primera terminal para poder vender.';
    }

    public function ctaLabel(): string
    {
        return 'Crear mi primer TPV';
    }

    public function ctaRoute(): string
    {
        return 'sales.pos.terminals.create';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    public function isComplete(): bool
    {
        return PosTerminal::exists();
    }
}
