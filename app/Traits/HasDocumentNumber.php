<?php

namespace App\Traits;

use App\Models\Accounting\DocumentType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Numeración interna por tipo de documento (v1.4.0 REQ-3.19). Al crear el modelo,
 * si no trae número, toma el siguiente correlativo de su DocumentType — así
 * cualquier punto de creación (servicio, Livewire, seeder de demo) queda numerado
 * sin repetir la lógica.
 *
 * El modelo declara:
 *   const DOCUMENT_CODE = 'COT';            // código en document_types
 *   const DOCUMENT_NUMBER_COLUMN = 'number'; // opcional, default 'number'
 */
trait HasDocumentNumber
{
    protected static function bootHasDocumentNumber(): void
    {
        static::creating(function ($model) {
            $column = $model->documentNumberColumn();

            if (! empty($model->{$column})) {
                return;
            }

            [$type, $number] = DocumentType::issueNext(static::DOCUMENT_CODE);
            $model->document_type_id = $type->id;
            $model->{$column} = $number;
        });
    }

    public function documentNumberColumn(): string
    {
        return defined(static::class.'::DOCUMENT_NUMBER_COLUMN') ? static::DOCUMENT_NUMBER_COLUMN : 'number';
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
