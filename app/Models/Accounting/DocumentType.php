<?php

namespace App\Models\Accounting;

use App\Models\Sales\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'prefix', 'current_number'];

    /**
     * Códigos que el propio sistema consulta por texto (SaleService, CollectionService...).
     * Cambiar el 'code' de uno de estos rompería esas búsquedas hardcodeadas.
     */
    const SYSTEM_PROTECTED_CODES = ['FAC', 'PAG'];

    protected static function booted()
    {
        static::saving(function ($type) {
            // Generar código único (ej: FAC) si está vacío
            if (empty($type->code)) {
                $type->code = self::makeCode($type->name);
            }
            // El prefijo suele ser igual al código en documentos contables
            if (empty($type->prefix)) {
                $type->prefix = $type->code;
            }
        });
    }

    public static function makeCode(string $name): string
    {
        // Toma las primeras 3 letras del nombre, sin caracteres especiales
        return strtoupper(
            substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)
        );
    }

    /* ===========================
     |  LÓGICA DE NEGOCIO
     =========================== */

    public function isSystemProtected(): bool
    {
        return in_array($this->code, self::SYSTEM_PROTECTED_CODES, true);
    }

    /**
     * Indica si ya se emitió al menos un documento con este tipo — a partir de ahí
     * el correlativo (current_number) deja de ser editable, para no comprometer la
     * integridad de la numeración y de los NCF ya emitidos.
     */
    public function hasIssuedDocuments(): bool
    {
        return match ($this->code) {
            'FAC' => Sale::where('document_type_id', $this->id)->exists(),
            // ClientCollection no guarda document_type_id; se identifica por el prefijo de su receipt_number.
            'PAG' => ClientCollection::where('receipt_number', 'like', $this->prefix.'-%')->exists(),
            default => false,
        };
    }

    /**
     * Genera el siguiente número formateado (Ej: FAC-000001)
     * No actualiza la base de datos, solo retorna el string.
     */
    public function getNextNumberFormatted(): string
    {
        $next = $this->current_number + 1;

        return sprintf(
            '%s-%06d',
            strtoupper($this->prefix),
            $next
        );
    }

    public function scopeWithIndexRelations($query)
    {
        return $query;
    }
}
