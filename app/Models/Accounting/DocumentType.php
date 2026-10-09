<?php

namespace App\Models\Accounting;

use App\Models\Inventory\InventoryCount;
use App\Models\Inventory\InventoryTransfer;
use App\Models\Inventory\InventoryWaste;
use App\Models\Sales\Invoice;
use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Quotes\Quote;
use App\Models\Sales\Returns\SaleReturn;
use App\Models\Sales\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'prefix', 'current_number'];

    /**
     * Códigos que el propio sistema consulta por texto (SaleService, CollectionService...).
     * Cambiar el 'code' de uno de estos rompería esas búsquedas hardcodeadas.
     */
    const SYSTEM_PROTECTED_CODES = ['VTA', 'FAC', 'CXC', 'COT', 'TRN', 'PAG', 'DEV', 'TFS', 'MER', 'TRA'];

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
            'VTA' => Sale::where('document_type_id', $this->id)->exists(),
            'FAC' => Invoice::where('document_type_id', $this->id)->exists(),
            'CXC' => Receivable::where('document_type_id', $this->id)->exists(),
            'COT' => Quote::where('document_type_id', $this->id)->exists(),
            'TRN' => PosSession::where('document_type_id', $this->id)->exists(),
            // ClientCollection no guarda document_type_id; se identifica por el prefijo de su receipt_number.
            'PAG' => ClientCollection::where('receipt_number', 'like', $this->prefix.'-%')->exists(),
            'DEV' => SaleReturn::where('document_type_id', $this->id)->exists(),
            // v1.5.0: cada documento nuevo con HasDocumentNumber se agrega aquí, o su
            // correlativo quedaría editable con documentos ya emitidos (números repetidos).
            'TFS' => InventoryCount::where('document_type_id', $this->id)->exists(),
            'MER' => InventoryWaste::where('document_type_id', $this->id)->exists(),
            'TRA' => InventoryTransfer::where('document_type_id', $this->id)->exists(),
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

    /**
     * Emite el siguiente correlativo del tipo `$code` de forma atómica (fila
     * bloqueada mientras dure la transacción externa, si la hay) — dos documentos
     * creados a la vez nunca reciben el mismo número.
     *
     * @return array{0: self, 1: string} [tipo de documento, número formateado]
     */
    public static function issueNext(string $code): array
    {
        return DB::transaction(function () use ($code) {
            $type = static::where('code', $code)->lockForUpdate()->firstOrFail();
            $number = $type->getNextNumberFormatted();
            $type->increment('current_number');

            return [$type, $number];
        });
    }

    public function scopeWithIndexRelations($query)
    {
        return $query;
    }
}
