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

    /**
     * Bug real encontrado probando el checklist con inventory.tracking apagado
     * (REQ-10.9): el paso seguía pidiéndose igual porque OnboardingStepRegistry ya
     * sabía filtrar pasos por módulo SATÉLITE (ver NcfStep), pero Almacenes se
     * construyó en la Fase 9 cuando Inventario todavía era base fijo — nunca se
     * actualizó al pasar a núcleo flexible en la Fase 10. Un negocio 100%
     * servicios que apaga Inventario ya no debería ver este paso.
     */
    public function requiredModule(): ?string
    {
        return 'inventory.tracking';
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
