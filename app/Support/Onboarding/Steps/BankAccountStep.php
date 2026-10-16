<?php

namespace App\Support\Onboarding\Steps;

use App\Models\Accounting\FinancialAccount;
use App\Services\Accounting\Treasury\CustomerPaymentService;
use App\Support\Onboarding\OnboardingStepInterface;

/**
 * Cuenta de banco de los pagos que no son efectivo (v1.5.0 REQ-3.6). Hecho cuando el POS
 * tiene una cuenta de banco por defecto, o cuando no hay métodos de pago activos que no
 * sean efectivo (un negocio que solo cobra en efectivo no necesita banco).
 */
class BankAccountStep implements OnboardingStepInterface
{
    public function key(): string
    {
        return 'bank_account';
    }

    public function title(): string
    {
        return 'Cuenta de banco';
    }

    public function description(): string
    {
        return 'Elige a qué cuenta entran las ventas con tarjeta, transferencia o depósito.';
    }

    public function ctaLabel(): string
    {
        return $this->hasBankAccount() ? 'Elegir cuenta' : 'Crear cuenta de banco';
    }

    /** Sin cuenta de banco, a crearla; con alguna, a elegirla en la configuración del POS. */
    public function ctaRoute(): string
    {
        return $this->hasBankAccount() ? 'sales.pos.settings.edit' : 'finance.treasury.create';
    }

    public function requiredModule(): ?string
    {
        return null;
    }

    public function isComplete(): bool
    {
        return ! app(CustomerPaymentService::class)->missingDefaultAccount();
    }

    private function hasBankAccount(): bool
    {
        return FinancialAccount::active()->where('type', FinancialAccount::TYPE_BANK)->exists();
    }
}
