<?php

namespace App\Support\Onboarding;

/**
 * Cada paso apunta a la vista REAL del módulo (ctaRoute) — nunca a una ruta
 * propia del wizard. isComplete() se calcula contra el dato real, nunca contra
 * un flag guardado, para que el paso se marque hecho aunque el dueño haya
 * creado el dato por fuera de la tarjeta (REQ-09.1/09.3).
 */
interface OnboardingStepInterface
{
    public function key(): string;

    public function title(): string;

    public function description(): string;

    public function ctaLabel(): string;

    public function ctaRoute(): string;

    public function requiredModule(): ?string;

    public function isComplete(): bool;
}
