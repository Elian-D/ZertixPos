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
        return 'Un producto necesita una categoría antes de poder crearse.';
    }

    public function ctaLabel(): string
    {
        return 'Crear mi primera categoría';
    }

    public function ctaRoute(): string
    {
        return 'products.categories.index';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    public function isComplete(): bool
    {
        return Category::exists();
    }
}
