<?php

namespace App\Livewire\Onboarding;

use App\Support\Onboarding\OnboardingStepInterface;
use App\Support\Onboarding\OnboardingStepRegistry;
use Livewire\Component;

class OnboardingChecklist extends Component
{
    /**
     * @return OnboardingStepInterface[]
     */
    public function steps(): array
    {
        return OnboardingStepRegistry::visibleSteps();
    }

    public function isDone(): bool
    {
        return collect($this->steps())->every(fn (OnboardingStepInterface $step) => $step->isComplete());
    }

    /**
     * Primer paso sin completar, en el orden del registry — es el único que se
     * muestra como accionable. Mostrar los 5 a la vez no deja claro cuál va
     * primero (reportado probando la tarjeta real); revelar uno a la vez sí.
     */
    public function currentStep(): ?OnboardingStepInterface
    {
        return collect($this->steps())->first(fn (OnboardingStepInterface $step) => ! $step->isComplete());
    }

    public function render()
    {
        return view('livewire.onboarding.onboarding-checklist');
    }
}
