<?php

namespace App\Services\Accounting\DocumentType;

use App\Models\Accounting\DocumentType;
use Illuminate\Support\Facades\DB;

class DocumentTypeService
{
    /**
     * Crea un nuevo tipo de documento.
     */
    public function create(array $data): DocumentType
    {
        return DB::transaction(function () use ($data) {
            return DocumentType::create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'prefix' => strtoupper($data['prefix'] ?? $data['code']),
                'current_number' => $data['current_number'] ?? 0,
            ]);
        });
    }

    /**
     * Actualiza un tipo de documento existente.
     */
    public function update(DocumentType $type, array $data): DocumentType
    {
        return DB::transaction(function () use ($type, $data) {
            $type->update($data);

            return $type;
        });
    }

    /**
     * Genera y reserva el siguiente número para un documento.
     * Este método se llamará cuando se cree una Factura, Pago, etc.
     */
    public function getNextNumber(DocumentType $type): string
    {
        return DB::transaction(function () use ($type) {
            $formattedNumber = $type->getNextNumberFormatted();

            // Incrementamos el contador en la base de datos
            $type->increment('current_number');

            return $formattedNumber;
        });
    }
}
