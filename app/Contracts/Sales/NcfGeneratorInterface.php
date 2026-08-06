<?php

namespace App\Contracts\Sales;

use App\Models\Sales\Sale;

interface NcfGeneratorInterface
{
    /**
     * Genera el siguiente número de comprobante para una venta.
     * Si el sistema no está en modo fiscal (módulo sales.ncf apagado), retornará null.
     *
     * * @param Sale $sale La instancia de la venta
     * @param  int  $ncfTypeId  El ID del tipo de comprobante solicitado
     * @return string|null El NCF completo (ej: B0100000005) o null
     */
    public function generate(Sale $sale, int $ncfTypeId): ?string;

    /**
     * Valida si hay disponibilidad para un tipo de comprobante.
     * Si el módulo sales.ncf está apagado, siempre retornará true (no se requiere secuencia).
     */
    public function hasAvailability(int $ncfTypeId): bool;
}
