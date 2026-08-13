<?php

namespace App\Enums;

enum TaxIdentifierType: string
{
    case RNC = 'RNC';
    case CEDULA = 'CEDULA';
    case PASAPORTE = 'PASAPORTE';

    public function label(): string
    {
        return match ($this) {
            self::RNC => 'RNC',
            self::CEDULA => 'Cédula',
            self::PASAPORTE => 'Pasaporte',
        };
    }
}
