<?php

namespace App\Services\Client;

use App\Enums\TaxIdentifierType;
use App\Models\Accounting\AccountingAccount;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province; // Importante

class ClientCatalogService
{
    public function getForFilters(): array
    {
        return [
            'states' => Province::ordered()->select('id', 'name')->get(),

            'taxIdentifierTypes' => collect(TaxIdentifierType::cases())
                ->map(fn (TaxIdentifierType $type) => ['value' => $type->value, 'label' => $type->label()]),

            // Opciones estáticas para los nuevos filtros de deuda
            'debtOptions' => [
                'yes' => 'Con Saldo Pendiente',
                'no' => 'Sin Deuda',
            ],
        ];
    }

    public function getForForm(): array
    {
        return [
            'types' => [
                'individual' => 'Persona Física',
                'company' => 'Empresa / Jurídica',
            ],
            'states' => Province::ordered()->select('id', 'name')->get(),

            // Precargado completo (~158 filas) — filtrado cascada provincia->municipio
            // se hace en Alpine.js del lado del cliente, sin request AJAX (ver Fase 6.9).
            'municipalities' => Municipality::select('id', 'name', 'province_id')->orderBy('name')->get(),

            'taxIdentifierTypes' => collect(TaxIdentifierType::cases())
                ->map(fn (TaxIdentifierType $type) => ['value' => $type->value, 'label' => $type->label()]),

            // Filtramos por el nodo de Activos Corrientes -> Cuentas por Cobrar
            'accountingAccounts' => AccountingAccount::where('is_selectable', true)
                ->where('code', 'like', '1.1.02%') // Ajusta según tu plan de cuentas
                ->select('id', 'code', 'name')
                ->orderBy('code')
                ->get(),
        ];
    }
}
