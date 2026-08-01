<?php

namespace App\Services\Sales\Ncf;

use App\Models\Sales\Ncf\NcfSequence;
use App\Models\Sales\Ncf\NcfType;
use Illuminate\Support\Facades\DB;
use Exception;

class NcfSequenceService
{
    /**
     * Registra un nuevo lote de NCF.
     */
    public function create(array $data): NcfSequence
    {
        return DB::transaction(function () use ($data) {
            $type = NcfType::findOrFail($data['ncf_type_id']);

            // 1. Serie y Prefijo
            $data['series'] = $data['series'] ?? $type->prefix;

            // 2. Validación de coherencia de dígitos
            $maxAllowed = $type->is_electronic ? 9999999999 : 99999999;
            if ($data['to'] > $maxAllowed) {
                throw new Exception("El rango superior excede el límite de dígitos permitido para este tipo (" . ($type->is_electronic ? '10' : '8') . ").");
            }

            // 3. Vencimiento Automático (Fin del año siguiente por defecto)
            $data['expiry_date'] = $data['expiry_date'] ?? now()->addYear()->endOfYear()->format('Y-m-d');

            $this->validateOverlap($data);

            $data['current'] = $data['from'] - 1;
            $data['status'] = NcfSequence::STATUS_ACTIVE;

            return NcfSequence::create($data);
        });
    }

    /**
     * Elimina una secuencia si no ha sido utilizada.
     */
    public function delete(NcfSequence $sequence): bool
    {
        if ($sequence->current >= $sequence->from) {
            throw new Exception("No se puede eliminar una secuencia que ya ha emitido comprobantes.");
        }

        return $sequence->delete();
    }

    /**
     * Verifica que no existan conflictos de rangos activos.
     */
    protected function validateOverlap(array $data): void
    {
        $exists = NcfSequence::where('ncf_type_id', $data['ncf_type_id'])
            ->where('status', NcfSequence::STATUS_ACTIVE)
            ->where(function ($query) use ($data) {
                $query->whereBetween('from', [$data['from'], $data['to']])
                      ->orWhereBetween('to', [$data['from'], $data['to']]);
            })
            ->exists();

        if ($exists) {
            throw new Exception("Ya existe un lote activo para este tipo de NCF que se solapa con el rango ingresado.");
        }
    }

    public function updateAlertThreshold(NcfSequence $sequence, int $threshold): bool
    {
        return $sequence->update([
            'alert_threshold' => $threshold
        ]);
    }

    /**
     * Cambiar estado manualmente (Vencer o Agotar).
     */
    public function updateStatus(NcfSequence $sequence, string $status): bool
    {
        return $sequence->update(['status' => $status]);
    }
}