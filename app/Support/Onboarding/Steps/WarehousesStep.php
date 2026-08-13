<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Inventory\Warehouse;
use App\Support\Onboarding\OnboardingStepInterface;

class WarehousesStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'warehouses';
    }

    public function title(): string
    {
        return 'Almacenes';
    }

    public function description(): string
    {
        return 'Configura el almacén desde donde vas a despachar tus ventas.';
    }

    public function ctaLabel(): string
    {
        return 'Revisar mis almacenes';
    }

    public function ctaRoute(): string
    {
        return 'inventory.warehouses.index';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    /**
     * Sin seeder core de almacenes desde REQ-07.13 (se movió a zertix:seed-demo)
     * — una instalación real arranca en cero, así que basta con que exista uno
     * solo para dar el paso por completo (REQ-09.6).
     */
    public function isComplete(): bool
    {
        return Warehouse::exists();
    }
}
