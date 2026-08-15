<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Sales\Ncf\NcfSequence;
use App\Support\Onboarding\OnboardingStepInterface;

class NcfStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'ncf';
    }

    public function title(): string
    {
        return 'Comprobantes Fiscales (NCF)';
    }

    public function description(): string
    {
        return 'Configura tus secuencias de NCF antes de facturar a crédito fiscal.';
    }

    public function ctaLabel(): string
    {
        return 'Configurar NCF';
    }

    public function ctaRoute(): string
    {
        return 'finance.ncf.dashboard';
    }

    public function requiredModule(): ?string
    {
        return 'sales.ncf';
    }

    public function isComplete(): bool
    {
        return NcfSequence::exists();
    }
}
