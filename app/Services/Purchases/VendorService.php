<?php

namespace App\Services\Purchases;

use App\Models\Purchases\Vendor;
use DomainException;

/**
 * Proveedores (v1.5.0 REQ-4.1). Catálogo Categoría A: papelera y restaurar; el borrado
 * definitivo solo si no tiene CxP ni gastos.
 */
class VendorService
{
    public function create(array $data): Vendor
    {
        return Vendor::create($this->clean($data));
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($this->clean($data));

        return $vendor;
    }

    public function forceDelete(Vendor $vendor): void
    {
        if ($vendor->hasDocuments()) {
            throw new DomainException("{$vendor->display_name} tiene cuentas por pagar o gastos: queda en la papelera para conservar el historial.");
        }

        $vendor->forceDelete();
    }

    /** Sin crédito no hay días de crédito. */
    private function clean(array $data): array
    {
        if (($data['payment_condition'] ?? Vendor::CONDITION_CASH) !== Vendor::CONDITION_CREDIT) {
            $data['credit_days'] = 0;
        }

        return $data;
    }
}
