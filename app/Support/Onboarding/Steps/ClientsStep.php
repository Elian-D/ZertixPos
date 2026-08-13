<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Clients\Client;
use App\Support\Onboarding\OnboardingStepInterface;

class ClientsStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'clients';
    }

    public function title(): string
    {
        return 'Primeros clientes';
    }

    public function description(): string
    {
        return 'Registra al menos un cliente real, además del Consumidor Final de fábrica.';
    }

    public function ctaLabel(): string
    {
        return 'Crear mi primer cliente';
    }

    public function ctaRoute(): string
    {
        return 'clients.create';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    /**
     * "Consumidor Final" viene sembrado por ClientSeeder (core) — no cuenta
     * para marcar el paso como completo por sí solo (REQ-09.5).
     */
    public function isComplete(): bool
    {
        return Client::where('name', '!=', 'Consumidor Final')->exists();
    }
}
