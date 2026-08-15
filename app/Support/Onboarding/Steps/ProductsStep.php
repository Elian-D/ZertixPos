<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Products\Product;
use App\Support\Onboarding\OnboardingStepInterface;

class ProductsStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'products';
    }

    public function title(): string
    {
        return 'Primeros productos';
    }

    public function description(): string
    {
        return 'Carga al menos un producto para poder empezar a vender.';
    }

    public function ctaLabel(): string
    {
        return 'Crear mi primer producto';
    }

    public function ctaRoute(): string
    {
        return 'inventory.products.create';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    public function isComplete(): bool
    {
        return Product::exists();
    }
}
