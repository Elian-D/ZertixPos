<?php

namespace App\Support\Onboarding;

use App\Support\Onboarding\Steps\CategoriesStep;
use App\Support\Onboarding\Steps\ClientsStep;
use App\Support\Onboarding\Steps\NcfStep;
use App\Support\Onboarding\Steps\PosTerminalStep;
use App\Support\Onboarding\Steps\ProductsStep;
use App\Support\Onboarding\Steps\WarehousesStep;

/**
 * Agregar un módulo nuevo al wizard es: crear su clase de paso y sumarla acá
 * — el contenedor (OnboardingChecklist) nunca cambia, solo itera
 * visibleSteps() (REQ-09.1).
 */
class OnboardingStepRegistry
{
    /** @var class-string<OnboardingStepInterface>[] */
    protected static array $steps = [
        CategoriesStep::class,
        ProductsStep::class,
        ClientsStep::class,
        WarehousesStep::class,
        NcfStep::class,
        PosTerminalStep::class, // después de NCF: el TPV se crea con un almacén ya
        // existente y pide un tipo de comprobante (REQ-09.10)
    ];

    /**
     * @return OnboardingStepInterface[]
     */
    public static function visibleSteps(): array
    {
        return collect(static::$steps)
            ->map(fn (string $class) => app($class))
            ->filter(fn (OnboardingStepInterface $step) => $step->requiredModule() === null || module_enabled($step->requiredModule()))
            ->values()
            ->all();
    }
}
