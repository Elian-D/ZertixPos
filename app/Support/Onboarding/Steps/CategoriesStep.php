<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Products\Category;
use App\Support\Onboarding\OnboardingStepInterface;

class CategoriesStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'categories';
    }

    public function title(): string
    {
        return 'Primera categoría';
    }

    public function description(): string
    {
        return 'La principal agrupa (ej. Agua) y los productos van en sus subcategorías (ej. Botellones).';
    }

    public function ctaLabel(): string
    {
        return 'Crear mi primera categoría';
    }

    public function ctaRoute(): string
    {
        return 'configuration.categories.create';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    public function isComplete(): bool
    {
        // Completo cuando hay al menos una principal con una subcategoría (v1.5.1 REQ-2.6).
        return Category::whereNotNull('parent_id')->whereHas('parent')->exists();
    }
}
