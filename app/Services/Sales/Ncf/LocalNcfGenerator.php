<?php

namespace App\Services\Sales\Ncf;

use App\Contracts\Sales\NcfGeneratorInterface;
use App\Models\Sales\Sale;
use App\Models\Sales\Ncf\NcfType;
use App\Models\Sales\Ncf\NcfSequence;
use App\Models\Sales\Ncf\NcfLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class LocalNcfGenerator implements NcfGeneratorInterface
{
    /**
     * Genera el NCF o retorna null si el sistema no usa comprobantes.
     */
    public function generate(Sale $sale, int $ncfTypeId): ?string
    {
        $config = general_config();

        // Si el switch principal está apagado, no generamos nada.
        if (!$config->usa_ncf) {
            return null;
        }

        return DB::transaction(function () use ($sale, $ncfTypeId) {
            
            // 1. Bloqueo de fila: Buscamos la secuencia activa con lockForUpdate
            $sequence = NcfSequence::where('ncf_type_id', $ncfTypeId)
                ->where('status', NcfSequence::STATUS_ACTIVE)
                ->where('expiry_date', '>=', now())
                ->lockForUpdate()
                ->first();

            // En modo fiscal, si no hay secuencia, es un error fatal.
            if (!$sequence) {
                throw new Exception("No hay secuencias activas o vigentes para este tipo de comprobante.");
            }

            // 2. Incrementar el contador
            $nextNumber = $sequence->current + 1;

            if ($nextNumber > $sequence->to) {
                // Si llegamos al límite, marcamos como agotada
                $sequence->update(['status' => NcfSequence::STATUS_EXHAUSTED]);
                throw new Exception("La secuencia de comprobantes se ha agotado.");
            }

            // 3. Determinar la longitud de la secuencia (Padding)
            $padding = $sequence->type->is_electronic ? 10 : 8;

            // 4. Formatear el NCF Completo
            $fullNcf = $sequence->series . 
                    str_pad($sequence->type->code, 2, '0', STR_PAD_LEFT) . 
                    str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);

            // 5. Actualizar la secuencia
            $sequence->update(['current' => $nextNumber]);

            // 6. Registrar en el Log de auditoría
            NcfLog::create([
                'full_ncf' => $fullNcf,
                'sale_id' => $sale->id,
                'ncf_type_id' => $ncfTypeId,
                'ncf_sequence_id' => $sequence->id,
                'status' => NcfLog::STATUS_USED,
                'user_id' => Auth::id() ?? $sale->user_id,
            ]);

            return $fullNcf;
        });
    }

    /**
     * Si no usamos NCF, siempre hay "disponibilidad" (porque no se consume nada).
     */
    public function hasAvailability(int $ncfTypeId): bool
    {
        $config = general_config();

        if (!$config->usa_ncf) {
            return true;
        }

        return NcfSequence::where('ncf_type_id', $ncfTypeId)
            ->where('status', NcfSequence::STATUS_ACTIVE)
            ->where('expiry_date', '>=', now())
            ->whereColumn('current', '<', 'to')
            ->exists();
    }
}